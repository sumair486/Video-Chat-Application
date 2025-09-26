<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Maximum file size in bytes (50MB)
     */
    const MAX_FILE_SIZE = 52428800;

    /**
     * Allowed file types
     */
   const ALLOWED_MIME_TYPES = [
    // Images
    'image/jpeg',
    'image/jpg', 
    'image/png',
    'image/gif',
    'image/webp',
    'image/bmp',
    'image/svg+xml',
    
    // Videos
    'video/mp4',
    'video/avi',
    'video/mov',
    'video/wmv',
    'video/flv',
    'video/webm',
    'video/mkv',
    'video/quicktime', // Add this
    
    // Audio - ADD THESE MISSING TYPES
    'audio/mp3',
    'audio/mpeg',      // This is critical for MP3 files
    'audio/wav',
    'audio/wave',      // Alternative wav type
    'audio/ogg',
    'audio/aac',
    'audio/flac',
    'audio/m4a',
    'audio/x-m4a',     // Alternative m4a type
    
    // Documents
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'text/csv',
    
    // Archives
    'application/zip',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
    
    // Other
    'application/json',
    'application/xml',
];

    /**
     * Display the chat interface with user selection
     */
    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        $chattingWith = null;
        $messages = collect();

        // Get the user we're chatting with (if specified)
        if ($request->has('with')) {
            $withUserId = $request->get('with');
            $chattingWith = User::find($withUserId);
            
            if ($chattingWith && $chattingWith->id !== $currentUser->id) {
                // Get messages between current user and the selected user
                $messages = Message::betweenUsers($currentUser->id, $chattingWith->id)
                    ->with(['user', 'receiver'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Mark messages as read
                Message::where('receiver_id', $currentUser->id)
                    ->where('user_id', $chattingWith->id)
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => now()
                    ]);
            }
        }

        // Get all users except current user for the user list
        $users = User::where('id', '!=', $currentUser->id)
            ->withCount([
                'sentMessages as unread_messages_count' => function ($query) use ($currentUser) {
                    $query->where('receiver_id', $currentUser->id)->where('is_read', false);
                }
            ])
            ->get();

        return view('chat.index', compact('messages', 'users', 'chattingWith'));
    }

    /**
     * Store a new message (text or file)
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        
        // Don't allow sending message to self
        if ($currentUser->id == $request->receiver_id) {
            return response()->json(['error' => 'Cannot send message to yourself'], 400);
        }

        // Validate based on whether it's a file upload or text message
        if ($request->hasFile('file')) {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'file' => [
                    'required',
                    'file',
                    'max:' . (self::MAX_FILE_SIZE / 1024), // Convert to KB for Laravel validation
                    function ($attribute, $value, $fail) {
                        if (!in_array($value->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                            $fail('The file type is not allowed.');
                        }
                    }
                ],
                'message' => 'nullable|string|max:500' // Optional caption/message
            ]);

            return $this->storeFileMessage($request);
        } else {
            $request->validate([
                'message' => 'required|string|max:1000',
                'receiver_id' => 'required|exists:users,id'
            ]);

            return $this->storeTextMessage($request);
        }
    }

    /**
     * Store a text message
     */
    private function storeTextMessage(Request $request): JsonResponse
    {
        $currentUser = auth()->user();

        $message = Message::create([
            'user_id' => $currentUser->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'type' => 'text'
        ]);

        // Load relationships
        $message->load(['user', 'receiver']);

        // Broadcast the message
        broadcast(new MessageSent($message));

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Store a file message
     */
    private function storeFileMessage(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        $file = $request->file('file');

        try {
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            
            // Store file in chat_files directory
            $filePath = $file->storeAs('chat_files', $fileName, 'public');
            
            if (!$filePath) {
                return response()->json(['error' => 'Failed to upload file'], 500);
            }

            // Determine message type based on MIME type
            $mimeType = $file->getMimeType();
            $messageType = Message::detectMessageType($mimeType);

            // Create message record
            $message = Message::create([
                'user_id' => $currentUser->id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message ?? '', // Optional caption
                'type' => $messageType,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $mimeType,
                'file_size' => $file->getSize(),
                'original_name' => $originalName
            ]);

            // Load relationships
            $message->load(['user', 'receiver']);

            // Broadcast the message
            broadcast(new MessageSent($message));

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            
            // Clean up uploaded file if message creation failed
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return response()->json(['error' => 'Failed to send file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download a file attachment
     */
    public function downloadFile(Message $message): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $currentUser = auth()->user();
        
        // Check if user has permission to download this file
        if ($message->user_id !== $currentUser->id && $message->receiver_id !== $currentUser->id) {
            abort(403, 'Unauthorized access to file');
        }

        if (!$message->file_path || !Storage::disk('public')->exists($message->file_path)) {
            abort(404, 'File not found');
        }

        $filePath = Storage::disk('public')->path($message->file_path);
        
        return response()->download($filePath, $message->original_name ?? $message->file_name);
    }

    /**
     * Get file info for preview
     */
    public function fileInfo(Message $message): JsonResponse
    {
        $currentUser = auth()->user();
        
        // Check if user has permission to access this file
        if ($message->user_id !== $currentUser->id && $message->receiver_id !== $currentUser->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$message->is_file_message) {
            return response()->json(['error' => 'Message is not a file'], 400);
        }

        return response()->json([
            'id' => $message->id,
            'original_name' => $message->original_name,
            'file_size' => $message->formatted_file_size,
            'file_type' => $message->file_type,
            'message_type' => $message->type,
            'file_url' => $message->file_url,
            'file_icon' => $message->getFileIcon(),
            'is_image' => $message->isImage(),
            'is_video' => $message->isVideo(),
            'is_audio' => $message->isAudio(),
            'is_document' => $message->isDocument(),
            'created_at' => $message->created_at->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get conversation between current user and another user
     */
    public function conversation(User $user): JsonResponse
    {
        $currentUser = auth()->user();
        
        if ($user->id === $currentUser->id) {
            return response()->json(['error' => 'Invalid user'], 400);
        }

        $messages = Message::betweenUsers($currentUser->id, $user->id)
            ->with(['user', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        Message::where('receiver_id', $currentUser->id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'messages' => $messages,
            'user' => $user
        ]);
    }

    /**
     * Get unread message count for current user
     */
    public function unreadCount(): JsonResponse
    {
        $count = Message::unreadForUser(auth()->id())->count();
        
        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(User $user): JsonResponse
    {
        $currentUser = auth()->user();
        
        // Get unread messages from this user
        $messages = Message::where('receiver_id', $currentUser->id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->get();

        $messageIds = $messages->pluck('id')->toArray();

        if (!empty($messageIds)) {
            // Mark messages as read
            Message::whereIn('id', $messageIds)->update([
                'is_read' => true,
                'read_at' => now()
            ]);

            // Broadcast read status to the sender
            broadcast(new \App\Events\MessageRead($messageIds, $currentUser->id, $user->id));
        }

        return response()->json(['success' => true, 'marked_count' => count($messageIds)]);
    }

    /**
     * Delete a message (for sender only)
     */
    public function deleteMessage(Message $message): JsonResponse
    {
        $currentUser = auth()->user();
        
        // Only sender can delete their own messages
        if ($message->user_id !== $currentUser->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete associated file if exists
        if ($message->file_path && Storage::disk('public')->exists($message->file_path)) {
            Storage::disk('public')->delete($message->file_path);
        }

        $message->delete();

        return response()->json(['success' => true, 'message' => 'Message deleted']);
    }

    /**
     * Get allowed file types for frontend validation
     */
    public function getAllowedFileTypes(): JsonResponse
    {
        return response()->json([
            'max_file_size' => self::MAX_FILE_SIZE,
            'max_file_size_mb' => round(self::MAX_FILE_SIZE / 1048576, 1),
            'allowed_types' => self::ALLOWED_MIME_TYPES,
            'categories' => [
                'images' => array_filter(self::ALLOWED_MIME_TYPES, fn($type) => str_starts_with($type, 'image/')),
                'videos' => array_filter(self::ALLOWED_MIME_TYPES, fn($type) => str_starts_with($type, 'video/')),
                'audio' => array_filter(self::ALLOWED_MIME_TYPES, fn($type) => str_starts_with($type, 'audio/')),
                'documents' => array_filter(self::ALLOWED_MIME_TYPES, fn($type) => str_starts_with($type, 'application/') || str_starts_with($type, 'text/')),
            ]
        ]);
    }
}