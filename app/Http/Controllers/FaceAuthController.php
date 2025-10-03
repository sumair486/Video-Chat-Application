<?php

namespace App\Http\Controllers;

use App\Services\FaceAuthService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class FaceAuthController extends Controller
{
    protected FaceAuthService $faceAuthService;

    public function __construct(FaceAuthService $faceAuthService)
    {
        $this->faceAuthService = $faceAuthService;
    }

    /**
     * Show face enrollment page
     */
    public function showEnrollment(): View
    {
        $user = Auth::user();
        $stats = $this->faceAuthService->getUserFaceAuthStats($user);
        
        return view('face-auth.enroll', compact('stats'));
    }

    /**
     * Show face login page
     */
    public function showFaceLogin(): View
    {
        return view('face-auth.login');
    }

    /**
     * Enroll user's face
     */
    public function enrollFace(Request $request): JsonResponse
    {
        $request->validate([
            'face_descriptors' => 'required|array|min:1',
            'face_descriptors.*' => 'required|array|size:128',
            'image_data' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = Auth::user();

        // Verify password before enrolling face
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid password. Please enter your current password to enable face authentication.'
            ], 401);
        }

        $result = $this->faceAuthService->enrollUserFace(
            $user,
            $request->face_descriptors,
            $request->image_data
        );

        return response()->json($result);
    }

    /**
     * Verify face for current user
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $request->validate([
            'face_descriptors' => 'required|array|min:1',
            'face_descriptors.*' => 'required|array|size:128',
            'confidence' => 'sometimes|numeric|min:0|max:1'
        ]);

        $user = Auth::user();
        
        $result = $this->faceAuthService->verifyUserFace(
            $user,
            $request->face_descriptors,
            $request->confidence
        );

        return response()->json($result);
    }

    /**
     * Authenticate user with face recognition
     */
    public function authenticateWithFace(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'face_descriptors' => 'required|array|min:1',
            'face_descriptors.*' => 'required|array|size:128',
            'confidence' => 'sometimes|numeric|min:0|max:1'
        ]);

        // Rate limiting
        $key = 'face_auth_' . $request->ip();
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 5) {
            return response()->json([
                'success' => false,
                'error' => 'Too many authentication attempts. Please try again later.'
            ], 429);
        }

        cache()->put($key, $attempts + 1, now()->addMinutes(15));

        $result = $this->faceAuthService->authenticateWithFace(
            $request->email,
            $request->face_descriptors,
            $request->confidence
        );

        if ($result['success']) {
            // Clear rate limiting on successful login
            cache()->forget($key);
            
            // Log the user in
            Auth::login($result['user']);
            
            return response()->json([
                'success' => true,
                'message' => 'Face authentication successful',
                'confidence' => $result['confidence'],
                'redirect' => route('chat.index')
            ]);
        }

        return response()->json($result, 401);
    }

    /**
     * Register new user with face authentication
     */
    public function registerWithFace(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'face_descriptors' => 'required|array|min:1',
            'face_descriptors.*' => 'required|array|size:128',
            'image_data' => 'required|string'
        ]);

        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Enroll face
            $faceResult = $this->faceAuthService->enrollUserFace(
                $user,
                $request->face_descriptors,
                $request->image_data
            );

            if (!$faceResult['success']) {
                // Delete user if face enrollment fails
                $user->delete();
                throw new \Exception($faceResult['error']);
            }

            // Log the user in
            Auth::login($user);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful with face authentication enabled',
                'redirect' => route('chat.index')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disable face authentication
     */
    public function disableFaceAuth(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = Auth::user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid password'
            ], 401);
        }

        $result = $this->faceAuthService->disableFaceAuth($user);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Face authentication disabled successfully' : 'Failed to disable face authentication'
        ]);
    }

    /**
     * Get user's face authentication statistics
     */
    public function getFaceAuthStats(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->faceAuthService->getUserFaceAuthStats($user);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Test face detection (for debugging)
     */
    public function testFaceDetection(Request $request): JsonResponse
    {
        $request->validate([
            'image_data' => 'required|string'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Face detection test endpoint - implement face detection logic here',
            'image_size' => strlen($request->image_data)
        ]);
    }
}