<x-guest-layout>
<div class="face-auth-container">
    <div class="face-auth-card">
        <div class="text-center mb-4">
            <h2 class="face-auth-title">Face Authentication Login</h2>
            <p class="face-auth-subtitle">Look at the camera and click "Authenticate" to log in</p>
        </div>

        <div class="face-auth-content">
            <!-- Video Preview -->
            <div class="video-container">
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

            <!-- Authentication Form -->
            <form id="faceAuthForm" class="face-auth-form">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="Enter your email address">
                </div>

                <div class="form-actions">
                    <button type="button" id="detectFaceBtn" class="btn btn-secondary" disabled>
                        <i class="fas fa-search"></i> Detect Face
                    </button>
                    
                    <button type="submit" id="authenticateBtn" class="btn btn-primary" disabled>
                        <i class="fas fa-unlock"></i> Authenticate
                    </button>
                </div>

                <div class="confidence-display" id="confidenceDisplay" style="display: none;">
                    <div class="confidence-label">Detection Confidence:</div>
                    <div class="confidence-bar">
                        <div class="confidence-fill" id="confidenceFill"></div>
                        <span class="confidence-text" id="confidenceText">0%</span>
                    </div>
                </div>
            </form>

            <!-- Alternative Login -->
            <div class="alternative-login">
                <div class="divider">
                    <span>or</span>
                </div>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-key"></i> Login with Password
                </a>
            </div>

            <!-- Registration Link -->
            <div class="register-link">
                <p>Don't have an account? 
                    <a href="{{ route('register') }}">Register here</a> or 
                    <a href="{{ route('face-auth.register') }}">Register with Face ID</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.face-auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
}

.face-auth-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    padding: 40px;
    width: 100%;
    max-width: 500px;
}

.face-auth-title {
    color: #333;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.face-auth-subtitle {
    color: #666;
    font-size: 16px;
    margin-bottom: 0;
}

.video-container {
    position: relative;
    background: #000;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 24px;
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

.face-detection-box.detecting {
    border-color: #ffaa00;
    box-shadow: 0 0 10px rgba(255, 170, 0, 0.5);
}

.face-detection-box.verified {
    border-color: #00ff00;
    box-shadow: 0 0 15px rgba(0, 255, 0, 0.8);
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

.status-icon {
    font-size: 18px;
}

.status-icon.success { color: #00ff00; }
.status-icon.warning { color: #ffaa00; }
.status-icon.error { color: #ff4444; }

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    justify-content: center;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f8f9fa;
    color: #495057;
    border: 2px solid #e1e5e9;
}

.btn-secondary:hover:not(:disabled) {
    background: #e9ecef;
}

.btn-outline-secondary {
    background: transparent;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-outline-secondary:hover {
    background: #667eea;
    color: white;
}

.confidence-display {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.confidence-label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.confidence-bar {
    position: relative;
    background: #e1e5e9;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff4444 0%, #ffaa00 50%, #00ff00 100%);
    width: 0%;
    transition: width 0.3s ease;
}

.confidence-text {
    position: absolute;
    top: -25px;
    right: 0;
    font-size: 12px;
    font-weight: 600;
    color: #333;
}

.alternative-login {
    margin-top: 30px;
    text-align: center;
}

.divider {
    position: relative;
    margin: 20px 0;
    text-align: center;
}

.divider:before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e1e5e9;
}

.divider span {
    background: white;
    padding: 0 15px;
    color: #666;
    font-size: 14px;
}

.register-link {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.register-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.register-link a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .face-auth-card {
        padding: 20px;
        margin: 10px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .face-auth-title {
        font-size: 24px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const detectFaceBtn = document.getElementById('detectFaceBtn');
    const authenticateBtn = document.getElementById('authenticateBtn');
    const faceAuthForm = document.getElementById('faceAuthForm');
    const statusIcon = document.getElementById('statusIcon');
    const statusMessage = document.getElementById('statusMessage');
    const confidenceDisplay = document.getElementById('confidenceDisplay');
    const confidenceFill = document.getElementById('confidenceFill');
    const confidenceText = document.getElementById('confidenceText');
    const detectionBox = document.getElementById('detectionBox');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    let currentDetection = null;
    let faceDescriptors = [];
    let isModelLoaded = false;

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
            updateStatus('Ready for face detection', 'success');
            detectFaceBtn.disabled = false;
            
        } catch (err) {
            console.error('Model loading error:', err);
            updateStatus('Failed to load AI models. Please refresh.', 'error');
        }
    };

    // Detect face in video
    const detectFace = async () => {
        if (!isModelLoaded || !video.videoWidth) {
            updateStatus('Please wait for initialization', 'warning');
            return;
        }

        try {
            updateStatus('Detecting face...', 'warning');
            detectFaceBtn.disabled = true;
            
            // Detect face
            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                updateStatus('No face detected. Please center your face.', 'error');
                detectionBox.style.display = 'none';
                detectFaceBtn.disabled = false;
                return;
            }

            // Show detection box
            const box = detection.detection.box;
            const videoRect = video.getBoundingClientRect();
            const scaleX = videoRect.width / video.videoWidth;
            const scaleY = videoRect.height / video.videoHeight;
            
            detectionBox.style.display = 'block';
            detectionBox.style.left = (box.x * scaleX) + 'px';
            detectionBox.style.top = (box.y * scaleY) + 'px';
            detectionBox.style.width = (box.width * scaleX) + 'px';
            detectionBox.style.height = (box.height * scaleY) + 'px';
            detectionBox.className = 'face-detection-box verified';

            // Store detection results
            currentDetection = detection;
            faceDescriptors = [Array.from(detection.descriptor)];

            // Show confidence
            const confidence = Math.round((1 - detection.detection.score) * 100);
            confidenceDisplay.style.display = 'block';
            confidenceFill.style.width = confidence + '%';
            confidenceText.textContent = confidence + '%';

            updateStatus('Face detected successfully!', 'success');
            authenticateBtn.disabled = false;
            
        } catch (err) {
            console.error('Face detection error:', err);
            updateStatus('Face detection failed. Please try again.', 'error');
        } finally {
            detectFaceBtn.disabled = false;
        }
    };

    // Handle authentication form
    faceAuthForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();
        
        if (!email) {
            alert('Please enter your email address');
            return;
        }
        
        if (!faceDescriptors.length) {
            alert('Please detect your face first');
            return;
        }

        try {
            authenticateBtn.disabled = true;
            authenticateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            updateStatus('Verifying identity...', 'warning');

            const response = await fetch('/face-auth/authenticate', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    face_descriptors: faceDescriptors,
                    confidence: currentDetection ? (1 - currentDetection.detection.score) : null
                })
            });

            const data = await response.json();

            if (data.success) {
                updateStatus('Authentication successful!', 'success');
                
                // Show success message briefly then redirect
                setTimeout(() => {
                    window.location.href = data.redirect || '/chat';
                }, 1500);
                
            } else {
                throw new Error(data.error || 'Authentication failed');
            }

        } catch (err) {
            console.error('Authentication error:', err);
            updateStatus('Authentication failed', 'error');
            alert('Authentication failed: ' + err.message);
        } finally {
            authenticateBtn.disabled = false;
            authenticateBtn.innerHTML = '<i class="fas fa-unlock"></i> Authenticate';
        }
    });

    // Event listeners
    detectFaceBtn.addEventListener('click', detectFace);

    // Initialize
    initCamera();
});
</script>
</x-guest-layout>