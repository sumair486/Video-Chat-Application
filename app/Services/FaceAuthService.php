<?php

namespace App\Services;

use App\Models\User;
use App\Models\FaceAuthLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class FaceAuthService
{
    const MAX_AUTH_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 30; // minutes
    const CONFIDENCE_THRESHOLD = 0.6; // Face-api.js confidence threshold
    const MAX_DESCRIPTORS = 5; // Store multiple face descriptors for better accuracy

    /**
     * Enroll user's face for authentication
     */
    public function enrollUserFace(User $user, array $faceDescriptors, string $imageData): array
    {
        try {
            // Validate face descriptors
            if (!$this->validateFaceDescriptors($faceDescriptors)) {
                throw new \Exception('Invalid face descriptors provided');
            }

            // Save face image
            $imagePath = $this->saveFaceImage($user->id, $imageData);

            // Process and store face descriptors
            $existingDescriptors = $user->face_descriptors ? json_decode($user->face_descriptors, true) : [];
            $newDescriptors = array_merge($existingDescriptors, $faceDescriptors);

            // Keep only the latest descriptors (up to MAX_DESCRIPTORS)
            if (count($newDescriptors) > self::MAX_DESCRIPTORS) {
                $newDescriptors = array_slice($newDescriptors, -self::MAX_DESCRIPTORS);
            }

            // Update user record
            $user->update([
                'face_descriptors' => json_encode($newDescriptors),
                'face_image_path' => $imagePath,
                'face_auth_enabled' => true,
                'face_enrolled_at' => now(),
                'face_auth_attempts' => 0,
                'face_auth_locked_until' => null
            ]);

            // Log enrollment
            $this->logFaceAuth($user, 'enrollment', true, null, [
                'descriptors_count' => count($newDescriptors),
                'image_path' => $imagePath
            ]);

            return [
                'success' => true,
                'message' => 'Face authentication enrolled successfully',
                'descriptors_count' => count($newDescriptors)
            ];

        } catch (\Exception $e) {
            $this->logFaceAuth($user, 'enrollment', false, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify user's face for authentication
     */
    public function verifyUserFace(User $user, array $faceDescriptors, float $confidence = null): array
    {
        try {
            // Check if user has face auth enabled
            if (!$user->face_auth_enabled || !$user->face_descriptors) {
                throw new \Exception('Face authentication not enabled for this user');
            }

            // Check if user is locked out
            if ($this->isUserLockedOut($user)) {
                $lockoutMinutes = $user->face_auth_locked_until->diffInMinutes(now());
                throw new \Exception("Account locked due to too many failed attempts. Try again in {$lockoutMinutes} minutes.");
            }

            // Validate incoming descriptors
            if (!$this->validateFaceDescriptors($faceDescriptors)) {
                throw new \Exception('Invalid face descriptors provided');
            }

            // Get stored face descriptors
            $storedDescriptors = json_decode($user->face_descriptors, true);
            
            // Calculate similarity with stored descriptors
            $bestMatch = $this->findBestMatch($faceDescriptors, $storedDescriptors);
            
            // Use provided confidence or calculated similarity
            $finalConfidence = $confidence ?? $bestMatch['similarity'];

            $success = $finalConfidence >= self::CONFIDENCE_THRESHOLD;

            if ($success) {
                // Reset failed attempts on successful verification
                $user->update([
                    'face_auth_attempts' => 0,
                    'face_auth_locked_until' => null
                ]);

                $this->logFaceAuth($user, 'verification', true, null, [
                    'confidence' => $finalConfidence,
                    'threshold' => self::CONFIDENCE_THRESHOLD,
                    'best_match_index' => $bestMatch['index']
                ]);

                return [
                    'success' => true,
                    'confidence' => $finalConfidence,
                    'message' => 'Face verification successful'
                ];
            } else {
                // Increment failed attempts
                $attempts = $user->face_auth_attempts + 1;
                $lockoutUntil = null;

                if ($attempts >= self::MAX_AUTH_ATTEMPTS) {
                    $lockoutUntil = now()->addMinutes(self::LOCKOUT_DURATION);
                }

                $user->update([
                    'face_auth_attempts' => $attempts,
                    'face_auth_locked_until' => $lockoutUntil
                ]);

                $this->logFaceAuth($user, 'verification', false, 'Low confidence match', [
                    'confidence' => $finalConfidence,
                    'threshold' => self::CONFIDENCE_THRESHOLD,
                    'attempts' => $attempts,
                    'locked_until' => $lockoutUntil
                ]);

                $remainingAttempts = self::MAX_AUTH_ATTEMPTS - $attempts;
                throw new \Exception("Face verification failed. {$remainingAttempts} attempts remaining.");
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate user using face recognition
     */
    public function authenticateWithFace(string $email, array $faceDescriptors, float $confidence = null): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                throw new \Exception('User not found');
            }

            $result = $this->verifyUserFace($user, $faceDescriptors, $confidence);

            if ($result['success']) {
                $this->logFaceAuth($user, 'login', true, null, [
                    'confidence' => $result['confidence']
                ]);

                return [
                    'success' => true,
                    'user' => $user,
                    'confidence' => $result['confidence'],
                    'message' => 'Authentication successful'
                ];
            } else {
                $this->logFaceAuth($user, 'login', false, $result['error']);
                return $result;
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Save face image to storage
     */
    private function saveFaceImage(int $userId, string $imageData): string
    {
        // Remove data URL prefix if present
        if (strpos($imageData, 'data:image/') === 0) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }

        $imageData = base64_decode($imageData);
        
        if (!$imageData) {
            throw new \Exception('Invalid image data');
        }

        $filename = "face_images/user_{$userId}_" . time() . '.jpg';
        
        if (!Storage::disk('private')->put($filename, $imageData)) {
            throw new \Exception('Failed to save face image');
        }

        return $filename;
    }

    /**
     * Validate face descriptors array
     */
    private function validateFaceDescriptors(array $descriptors): bool
    {
        if (empty($descriptors)) {
            return false;
        }

        // Each descriptor should be an array of 128 float values (face-api.js standard)
        foreach ($descriptors as $descriptor) {
            if (!is_array($descriptor) || count($descriptor) !== 128) {
                return false;
            }

            foreach ($descriptor as $value) {
                if (!is_numeric($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Find best matching descriptor
     */
    private function findBestMatch(array $inputDescriptors, array $storedDescriptors): array
    {
        $bestSimilarity = 0;
        $bestIndex = -1;

        foreach ($storedDescriptors as $storedIndex => $storedDescriptor) {
            foreach ($inputDescriptors as $inputDescriptor) {
                $similarity = $this->calculateCosineSimilarity($inputDescriptor, $storedDescriptor);
                
                if ($similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestIndex = $storedIndex;
                }
            }
        }

        return [
            'similarity' => $bestSimilarity,
            'index' => $bestIndex
        ];
    }

    /**
     * Calculate cosine similarity between two descriptors
     */
    private function calculateCosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0;
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Check if user is locked out
     */
    private function isUserLockedOut(User $user): bool
    {
        return $user->face_auth_locked_until && now()->lt($user->face_auth_locked_until);
    }

    /**
     * Log face authentication attempt
     */
    private function logFaceAuth(User $user, string $type, bool $success, ?string $failureReason = null, ?array $faceData = null): void
    {
        FaceAuthLog::create([
            'user_id' => $user->id,
            'attempt_type' => $type,
            'success' => $success,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'face_data' => $faceData ? json_encode($faceData) : null,
            'failure_reason' => $failureReason
        ]);
    }

    /**
     * Get user's face authentication stats
     */
    public function getUserFaceAuthStats(User $user): array
    {
        $stats = FaceAuthLog::where('user_id', $user->id)
            ->selectRaw('
                attempt_type,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_attempts,
                MAX(created_at) as last_attempt
            ')
            ->groupBy('attempt_type')
            ->get()
            ->keyBy('attempt_type');

        return [
            'face_auth_enabled' => $user->face_auth_enabled,
            'face_enrolled_at' => $user->face_enrolled_at,
            'current_attempts' => $user->face_auth_attempts,
            'locked_until' => $user->face_auth_locked_until,
            'stats' => $stats->toArray()
        ];
    }

    /**
     * Disable face authentication for user
     */
    public function disableFaceAuth(User $user): bool
    {
        $user->update([
            'face_descriptors' => null,
            'face_image_path' => null,
            'face_auth_enabled' => false,
            'face_enrolled_at' => null,
            'face_auth_attempts' => 0,
            'face_auth_locked_until' => null
        ]);

        // Delete stored face image
        if ($user->face_image_path) {
            Storage::disk('private')->delete($user->face_image_path);
        }

        $this->logFaceAuth($user, 'enrollment', true, 'Face auth disabled');

        return true;
    }
}