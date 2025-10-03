@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-shield"></i>
                        Face Authentication Setup
                    </h4>
                </div>
                
                <div class="card-body p-4">
                    @if($stats['face_auth_enabled'])
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Face authentication is already enabled for your account.
                            <small class="d-block mt-1">
                                Enrolled on: {{ $stats['face_enrolled_at']->format('M j, Y \a\t g:i A') }}
                            </small>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Set up face authentication to login quickly and securely using your camera.
                        </div>
                    @endif

                    <!-- Face Enrollment Section -->
                    <div class="face-enrollment-section">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Video Preview -->
                                <div class="video-container mb-3" style="max-width: 500px; margin: 0 auto;">
                                    <video id="video" autoplay muted playsinline class="face-video"></video>
                                    <canvas id="canvas" style="display: none;"></canvas>
                                    
                                    <!-- Face Detection Overlay -->
                                    <div class="face-overlay" id="faceOverlay">
                                        <div class="face-detection-box" id="detectionBox" style="display: none;"></div>
                                    </div>
                                    
                                    <!-- Status Messages -->
                                    <div class="face-status" id="faceStatus">
                                        <div class="status-icon">
                                            <i class="fas fa-camera" id="statusIcon"></i>
                                        </div>
                                        <div class="status-message" id="statusMessage">Starting camera...</div>
                                    </div>
                                </div>

                                <!-- Capture Progress -->
                                <div class="capture-progress mb-3" id="captureProgress" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small font-weight-bold">Capture Progress</span>
                                        <span class="small" id="captureCount">0/5</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" id="progressBar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Enrollment Instructions -->
                                <div class="enrollment-instructions">
                                    <h5>Setup Instructions:</h5>
                                    <ol class="setup-steps">
                                        <li>Position your face in the camera view</li>
                                        <li>Ensure good lighting on your face</li>
                                        <li>Look directly at the camera</li>
                                        <li>Keep your face steady during capture</li>
                                        <li>We'll capture multiple angles for accuracy</li>
                                    </ol>
                                </div>

                                <!-- Enrollment Form -->
                                <form id="enrollmentForm" class="enrollment-form" style="display: none;">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock"></i>
                                            Confirm Your Password
                                        </label>
                                        <input type="password" id="password" name="password" 
                                               class="form-control" required 
                                               placeholder="Enter your current password">
                                        <small class="form-text text-muted">
                                            Required for security verification
                                        </small>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" id="enrollBtn" class="btn btn-success" disabled>
                                            <i class="fas fa-user-plus"></i>
                                            Enable Face Authentication
                                        </button>
                                    </div>
                                </form>

                                <!-- Disable Face Auth -->
                                @if($stats['face_auth_enabled'])
                                    <div class="disable-section mt-4">
                                        <h5 class="text-danger">Disable Face Authentication</h5>
                                        <form id="disableFaceAuth">
                                            @csrf
                                            <div class="form-group mb-3">
                                                <input type="password" id="disablePassword" name="password" 
                                                       class="form-control" required 
                                                       placeholder="Enter your password to disable">
                                            </div>
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-user-times"></i>
                                                Disable Face Authentication
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="enrollment-actions mt-4">
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" id="startCaptureBtn" class="btn btn-primary" disabled>
                                    <i class="fas fa-camera"></i>
                                    Start Face Capture
                                </button>
                                
                                <button type="button" id="retakeBtn" class="btn btn-secondary" style="display: none;">
                                    <i class="fas fa-redo"></i>
                                    Retake
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    @if($stats['face_auth_enabled'] && !empty($stats['stats']))
                        <div class="face-stats mt-4">
                            <h5>Authentication Statistics</h5>
                            <div class="row">
                                @foreach($stats['stats'] as $type => $stat)
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-value">{{ $stat['successful_attempts'] }}/{{ $stat['total_attempts'] }}</div>
                                            <div class="stat-label">{{ ucfirst(str_replace('_', ' ', $type)) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.video-container {
    position: relative;
    background: #000;
    border-radius: 15px;
    overflow: hidden;
    aspect-ratio: 4/3;
}

.face-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.face-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.face-detection-box {
    position: absolute;
    border: 3px solid #00ff00;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
    transition: all 0.3s ease;
}

.face-detection-box.capturing {
    border-color: #ff6b6b;
    box-shadow: 0 0 15px rgba(255, 107, 107, 0.8);
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.face-status {
    position: absolute;
    bottom: 15px;
    left: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-icon.success { color: #28a745; }
.status-icon.warning { color: #ffc107; }
.status-icon.error { color: #dc3545; }

.setup-steps {
    padding-left: 1.2rem;
}

.setup-steps li {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.enrollment-form {
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 20px;
    background: #f8f9fa;
}

.enrollment-actions {
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
}

.stat-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #495057;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
}

.disable-section {
    border: 2px dashed #dc3545;
    border-radius: 10px;
    padding: 20px;
    background: #fff5f5;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const startCaptureBtn = document.getElementById('startCaptureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const enrollBtn = document.getElementById('enrollBtn');
    const enrollmentForm = document.getElementById('enrollmentForm');
    const disableFaceAuthForm = document.getElementById('disableFaceAuth');
    const captureProgress = document.getElementById('captureProgress');
    const progressBar = document.getElementById('progressBar');
    const captureCount = document.getElementById('captureCount');
    const statusIcon = document.getElementById('statusIcon');
    const statusMessage = document.getElementById('statusMessage');
    const detectionBox = document.getElementById('detectionBox');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    let isModelLoaded = false;
    let capturedDescriptors = [];
    let capturedImageData = null;
    const requiredCaptures = 5;

    // Update status display
    const updateStatus = (message, type = 'info') => {
        statusMessage.textContent = message;
        statusIcon.className = `fas ${getStatusIcon(type)} status-icon ${type}`;
    };

    const getStatusIcon = (type) => {
        switch(type) {
            case 'success': return 'fa-check-circle';
            case 'warning': return 'fa-exclamation-triangle';
            case 'error': return 'fa-times-circle';
            default: return 'fa-camera';
        }
    };

    // Initialize camera
    const initCamera = async () => {
        try {
            updateStatus('Requesting camera access...', 'warning');
            
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            });
            
            video.srcObject = stream;
            
            video.onloadedmetadata = () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                updateStatus('Camera ready. Loading AI models...', 'warning');
                loadModels();
            };
            
        } catch (err) {
            console.error('Camera error:', err);
            updateStatus('Camera access denied. Please enable camera.', 'error');
        }
    };

    // Load Face-API models
    const loadModels = async () => {
        try {
            updateStatus('Loading AI models...', 'warning');
            
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('/models')
            ]);
            
            isModelLoaded = true;
            updateStatus('Ready for face enrollment', 'success');
            startCaptureBtn.disabled = false;
            
        } catch (err) {
            console.error('Model loading error:', err);
            updateStatus('Failed to load AI models. Please refresh.', 'error');
        }
    };

    // Capture multiple face samples
    const startFaceCapture = async () => {
        if (!isModelLoaded) return;

        capturedDescriptors = [];
        capturedImageData = null;
        captureProgress.style.display = 'block';
        startCaptureBtn.disabled = true;
        
        updateStatus('Position your face and stay still...', 'warning');
        
        // Wait a moment for user to position
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        for (let i = 0; i < requiredCaptures; i++) {
            try {
                updateStatus(`Capturing face sample ${i + 1}/${requiredCaptures}...`, 'warning');
                
                const detection = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    throw new Error('No face detected. Please ensure your face is visible.');
                }

                // Show detection box with capture animation
                const box = detection.detection.box;
                const videoRect = video.getBoundingClientRect();
                const scaleX = videoRect.width / video.videoWidth;
                const scaleY = videoRect.height / video.videoHeight;
                
                detectionBox.style.display = 'block';
                detectionBox.style.left = (box.x * scaleX) + 'px';
                detectionBox.style.top = (box.y * scaleY) + 'px';
                detectionBox.style.width = (box.width * scaleX) + 'px';
                detectionBox.style.height = (box.height * scaleY) + 'px';
                detectionBox.className = 'face-detection-box capturing';

                // Store descriptor
                capturedDescriptors.push(Array.from(detection.descriptor));

                // Capture image data on first capture
                if (i === 0) {
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);
                    capturedImageData = canvas.toDataURL('image/jpeg', 0.8);
                }

                // Update progress
                const progress = ((i + 1) / requiredCaptures) * 100;
                progressBar.style.width = progress + '%';
                captureCount.textContent = `${i + 1}/${requiredCaptures}`;

                // Wait between captures
                await new Promise(resolve => setTimeout(resolve, 1000));
                
            } catch (err) {
                console.error('Capture error:', err);
                updateStatus('Capture failed: ' + err.message, 'error');
                startCaptureBtn.disabled = false;
                captureProgress.style.display = 'none';
                return;
            }
        }

        // Capture completed
        detectionBox.className = 'face-detection-box';
        updateStatus(`Successfully captured ${requiredCaptures} face samples!`, 'success');
        enrollmentForm.style.display = 'block';
        enrollBtn.disabled = false;
        retakeBtn.style.display = 'inline-block';
    };

    // Handle enrollment form submission
    enrollmentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const password = document.getElementById('password').value;
        
        if (!password) {
            alert('Please enter your password');
            return;
        }
        
        if (!capturedDescriptors.length || !capturedImageData) {
            alert('Please capture your face samples first');
            return;
        }

        try {
            enrollBtn.disabled = true;
            enrollBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';
            updateStatus('Enrolling face authentication...', 'warning');

            const response = await fetch('/face-auth/enroll', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    face_descriptors: capturedDescriptors,
                    image_data: capturedImageData,
                    password: password
                })
            });

            const data = await response.json();

            if (data.success) {
                updateStatus('Face authentication enrolled successfully!', 'success');
                alert('Face authentication has been enabled for your account. You can now login using your face.');
                location.reload();
            } else {
                throw new Error(data.error || 'Enrollment failed');
            }

        } catch (err) {
            console.error('Enrollment error:', err);
            updateStatus('Enrollment failed', 'error');
            alert('Enrollment failed: ' + err.message);
        } finally {
            enrollBtn.disabled = false;
            enrollBtn.innerHTML = '<i class="fas fa-user-plus"></i> Enable Face Authentication';
        }
    });

    // Handle disable face auth
    if (disableFaceAuthForm) {
        disableFaceAuthForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to disable face authentication?')) {
                return;
            }
            
            const password = document.getElementById('disablePassword').value;
            
            if (!password) {
                alert('Please enter your password');
                return;
            }

            try {
                const response = await fetch('/face-auth/disable', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ password: password })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Face authentication has been disabled.');
                    location.reload();
                } else {
                    throw new Error(data.error || 'Failed to disable face authentication');
                }

            } catch (err) {
                console.error('Disable error:', err);
                alert('Failed to disable: ' + err.message);
            }
        });
    }

    // Event listeners
    startCaptureBtn.addEventListener('click', startFaceCapture);
    
    retakeBtn.addEventListener('click', () => {
        capturedDescriptors = [];
        capturedImageData = null;
        captureProgress.style.display = 'none';
        enrollmentForm.style.display = 'none';
        enrollBtn.disabled = true;
        retakeBtn.style.display = 'none';
        startCaptureBtn.disabled = false;
        detectionBox.style.display = 'none';
        progressBar.style.width = '0%';
        captureCount.textContent = '0/5';
        updateStatus('Ready for face enrollment', 'success');
    });

    // Initialize
    initCamera();
});
</script>
@endsection