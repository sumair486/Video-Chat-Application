@extends('layouts.app')

@section('content')
<div class="video-chat-container">
  <!-- Header -->
  <div class="chat-header">
    <div class="container">
      <h1 class="chat-title">
        <i class="fas fa-video"></i>
        Video Chat
      </h1>
      <div class="user-info">
        <div class="user-avatar">
          <i class="fas fa-user"></i>
        </div>
        <span class="user-name">{{ auth()->user()->name }}</span>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="row g-4">
      <!-- Left Panel - Chat & Controls -->
      <div class="col-lg-5 col-md-6">
        <!-- Chat Section -->
        <div class="chat-panel">
          <div class="chat-panel-header">
            <i class="fas fa-comments"></i>
            <span>Chat Messages</span>
            <div class="online-indicator">
              <span class="pulse"></span>
              Online
            </div>
          </div>
          
          <div class="chat-messages" id="chat-box">
            @foreach($messages as $message)
              <div class="message {{ $message->user->id === auth()->id() ? 'message-own' : 'message-other' }}">
                <div class="message-avatar">
                  <i class="fas fa-user"></i>
                </div>
                <div class="message-content">
                  <div class="message-header">
                    <span class="message-author">{{ $message->user->name }}</span>
                    <span class="message-time">{{ $message->created_at->format('H:i') }}</span>
                  </div>
                  <div class="message-text">{{ $message->message }}</div>
                </div>
              </div>
            @endforeach
          </div>
          
          <div class="chat-input-wrapper">
            <form id="chat-form" class="chat-form">
              @csrf
              <div class="input-group">
                <input type="text" name="message" id="message" class="chat-input" placeholder="Type your message..." autocomplete="off">
                <button type="submit" class="send-btn">
                  <i class="fas fa-paper-plane"></i>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Call Controls -->
        <div class="call-controls-panel">
          <div class="panel-header">
            <i class="fas fa-phone-alt"></i>
            <span>Video Call</span>
          </div>
          
          <div class="call-setup">
            <div class="input-wrapper">
              <label for="call-to">Call User ID</label>
              <input id="call-to" type="number" class="form-input" placeholder="Enter user ID (e.g. 2)" min="1">
            </div>
            
            <div class="call-actions">
              <button id="startCall" class="btn btn-call">
                <i class="fas fa-video"></i>
                Start Call
              </button>
              <button id="hangup" class="btn btn-hangup">
                <i class="fas fa-phone-slash"></i>
                Hang Up
              </button>
            </div>
            
            <div class="call-status">
              <div id="callStatus" class="status-text">Ready to call</div>
              <div class="status-indicator">
                <span class="status-dot"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel - Video -->
      <div class="col-lg-7 col-md-6">
        <div class="video-panel">
          <div class="video-container">
            <!-- Remote Video (Main) -->
            <div class="remote-video-wrapper">
              <video id="remoteVideo" autoplay playsinline class="remote-video"></video>
              <div class="video-overlay remote-overlay">
                <div class="video-placeholder">
                  <i class="fas fa-user-friends"></i>
                  <span>Waiting for remote video...</span>
                </div>
                <div class="video-controls">
                  <button class="control-btn" id="toggleFullscreen">
                    <i class="fas fa-expand"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Local Video (Picture-in-Picture) -->
            <div class="local-video-wrapper">
              <video id="localVideo" autoplay muted playsinline class="local-video"></video>
              <div class="video-overlay local-overlay">
                <div class="video-placeholder">
                  <i class="fas fa-user"></i>
                  <span>Your camera</span>
                </div>
              </div>
              <div class="pip-controls">
                <button class="pip-btn" id="toggleVideo">
                  <i class="fas fa-video"></i>
                </button>
                <button class="pip-btn" id="toggleAudio">
                  <i class="fas fa-microphone"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Video Controls Bar -->
          <div class="video-controls-bar">
            <div class="controls-left">
              <div class="connection-quality">
                <i class="fas fa-signal"></i>
                <span id="connectionQuality">Good</span>
              </div>
            </div>
            
            <div class="controls-center">
              <button class="control-btn" id="muteAudio" title="Mute/Unmute">
                <i class="fas fa-microphone"></i>
              </button>
              <button class="control-btn" id="muteVideo" title="Camera On/Off">
                <i class="fas fa-video"></i>
              </button>
              <button class="control-btn" id="shareScreen" title="Share Screen">
                <i class="fas fa-desktop"></i>
              </button>
            </div>
            
            <div class="controls-right">
              <div class="call-duration" id="callDuration">00:00</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
:root {
  --primary-color: #667eea;
  --primary-dark: #5a67d8;
  --secondary-color: #764ba2;
  --accent-color: #f093fb;
  --success-color: #48bb78;
  --danger-color: #f56565;
  --warning-color: #ed8936;
  --dark-bg: #1a202c;
  --dark-surface: #2d3748;
  --dark-border: #4a5568;
  --text-primary: #2d3748;
  --text-secondary: #718096;
  --text-light: #ffffff;
  --border-color: #e2e8f0;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
  --glass-bg: rgba(255, 255, 255, 0.1);
  --glass-border: rgba(255, 255, 255, 0.2);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--gradient-bg);
  min-height: 100vh;
  color: var(--text-primary);
}

.video-chat-container {
  min-height: 100vh;
  background: var(--gradient-bg);
}

/* Header */
.chat-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--glass-border);
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-sm);
}

.chat-header .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--gradient-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

/* Panels */
.chat-panel, .call-controls-panel, .video-panel {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  border: 1px solid var(--glass-border);
  box-shadow: var(--shadow-xl);
  overflow: hidden;
}

.chat-panel {
  height: 500px;
  display: flex;
  flex-direction: column;
  margin-bottom: 1.5rem;
}

.call-controls-panel {
  height: auto;
}

.video-panel {
  height: 600px;
  display: flex;
  flex-direction: column;
}

/* Panel Headers */
.chat-panel-header, .panel-header {
  background: var(--gradient-bg);
  color: white;
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  font-size: 1.1rem;
}

.online-indicator {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.pulse {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--success-color);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(72, 187, 120, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 10px rgba(72, 187, 120, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(72, 187, 120, 0);
  }
}

/* Chat Messages */
.chat-messages {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  scroll-behavior: smooth;
}

.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
  background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: var(--text-secondary);
}

.message {
  display: flex;
  gap: 0.75rem;
  animation: messageSlide 0.3s ease-out;
}

.message-own {
  flex-direction: row-reverse;
}

.message-own .message-content {
  background: var(--gradient-bg);
  color: white;
}

.message-other .message-content {
  background: #f7fafc;
  color: var(--text-primary);
}

@keyframes messageSlide {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--gradient-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.message-content {
  max-width: 70%;
  padding: 0.75rem 1rem;
  border-radius: 18px;
  box-shadow: var(--shadow-sm);
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.message-author {
  font-weight: 600;
  font-size: 0.85rem;
}

.message-time {
  font-size: 0.75rem;
  opacity: 0.7;
}

.message-text {
  font-size: 0.95rem;
  line-height: 1.4;
}

/* Chat Input */
.chat-input-wrapper {
  padding: 1rem;
  border-top: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.5);
}

.chat-form {
  position: relative;
}

.input-group {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 25px;
  padding: 0.5rem;
  box-shadow: var(--shadow-sm);
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.input-group:focus-within {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.chat-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  background: transparent;
  color: var(--text-primary);
}

.chat-input::placeholder {
  color: var(--text-secondary);
}

.send-btn {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 50%;
  background: var(--gradient-bg);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.send-btn:hover {
  transform: scale(1.05);
  box-shadow: var(--shadow-md);
}

.send-btn:active {
  transform: scale(0.95);
}

/* Call Controls */
.call-setup {
  padding: 1.5rem;
}

.input-wrapper {
  margin-bottom: 1.5rem;
}

.input-wrapper label {
  display: block;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: 12px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.call-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.btn {
  flex: 1;
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-call {
  background: var(--success-color);
  color: white;
}

.btn-call:hover {
  background: #38a169;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn-hangup {
  background: var(--danger-color);
  color: white;
}

.btn-hangup:hover {
  background: #e53e3e;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.call-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 12px;
}

.status-text {
  font-size: 0.9rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.status-indicator {
  display: flex;
  align-items: center;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--success-color);
  animation: pulse 2s infinite;
}

/* Video Container */
.video-container {
  flex: 1;
  position: relative;
  background: var(--dark-bg);
  border-radius: 16px 16px 0 0;
  overflow: hidden;
}

.remote-video-wrapper {
  width: 100%;
  height: 100%;
  position: relative;
  background: var(--dark-bg);
}

.remote-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  background: var(--dark-bg);
}

.local-video-wrapper {
  position: absolute;
  bottom: 20px;
  right: 20px;
  width: 200px;
  height: 150px;
  border-radius: 12px;
  overflow: hidden;
  background: var(--dark-surface);
  border: 3px solid white;
  box-shadow: var(--shadow-lg);
  z-index: 10;
}

.local-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  background: var(--dark-surface);
}

.video-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  opacity: 1;
  transition: opacity 0.3s ease;
}

.video-placeholder {
  text-align: center;
}

.video-placeholder i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.7;
}

.video-placeholder span {
  display: block;
  font-size: 1.1rem;
  opacity: 0.9;
}

.local-video-wrapper .video-placeholder i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.local-video-wrapper .video-placeholder span {
  font-size: 0.85rem;
}

.video-controls {
  position: absolute;
  top: 15px;
  right: 15px;
  display: flex;
  gap: 0.5rem;
}

.pip-controls {
  position: absolute;
  bottom: 8px;
  right: 8px;
  display: flex;
  gap: 0.25rem;
}

.control-btn, .pip-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pip-btn {
  width: 28px;
  height: 28px;
  font-size: 0.75rem;
}

.control-btn:hover, .pip-btn:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: scale(1.1);
}

/* Video Controls Bar */
.video-controls-bar {
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.controls-left, .controls-right {
  flex: 1;
}

.controls-center {
  display: flex;
  gap: 1rem;
}

.controls-right {
  text-align: right;
}

.connection-quality {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.call-duration {
  font-weight: 600;
  font-family: 'Courier New', monospace;
  font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .chat-header .container {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .chat-panel {
    height: 350px;
  }
  
  .video-panel {
    height: 450px;
    margin-top: 1rem;
  }
  
  .local-video-wrapper {
    width: 120px;
    height: 90px;
    bottom: 15px;
    right: 15px;
  }
  
  .call-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .video-controls-bar {
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
  }
  
  .controls-center {
    gap: 0.75rem;
  }
  
  .control-btn {
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
  }
}

@media (max-width: 576px) {
  .container {
    padding: 0 1rem;
  }
  
  .chat-panel, .video-panel {
    border-radius: 16px;
  }
  
  .local-video-wrapper {
    width: 100px;
    height: 75px;
  }
  
  .message-content {
    max-width: 85%;
  }
}

/* Loading and Animation Effects */
.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Focus States for Accessibility */
.btn:focus,
.form-input:focus,
.chat-input:focus {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .chat-panel, .call-controls-panel, .video-panel {
    background: rgba(45, 55, 72, 0.95);
    color: var(--text-light);
  }
  
  .message-other .message-content {
    background: var(--dark-surface);
    color: var(--text-light);
  }
  
  .form-input {
    background: var(--dark-surface);
    color: var(--text-light);
    border-color: var(--dark-border);
  }
  
  .call-status {
    background: var(--dark-surface);
  }
  
  .chat-input-wrapper {
    background: rgba(45, 55, 72, 0.5);
  }
}
</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const userId = {{ auth()->id() }};
  const statusDiv = document.getElementById('callStatus');
  const statusDot = document.querySelector('.status-dot');

  // Enhanced status update function
  const updateStatus = (message, type = 'info') => {
    statusDiv.textContent = message;
    console.log('Status:', message);
    
    // Update status indicator color
    statusDot.className = 'status-dot';
    switch(type) {
      case 'success':
        statusDot.style.background = 'var(--success-color)';
        break;
      case 'error':
        statusDot.style.background = 'var(--danger-color)';
        break;
      case 'warning':
        statusDot.style.background = 'var(--warning-color)';
        break;
      default:
        statusDot.style.background = 'var(--primary-color)';
    }
  };

  // Call duration timer
  let callStartTime = null;
  let callDurationInterval = null;

  const startCallTimer = () => {
    callStartTime = Date.now();
    callDurationInterval = setInterval(() => {
      const elapsed = Date.now() - callStartTime;
      const minutes = Math.floor(elapsed / 60000);
      const seconds = Math.floor((elapsed % 60000) / 1000);
      document.getElementById('callDuration').textContent = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
  };

  const stopCallTimer = () => {
    if (callDurationInterval) {
      clearInterval(callDurationInterval);
      callDurationInterval = null;
    }
    document.getElementById('callDuration').textContent = '00:00';
  };

  // Enhanced chat functionality
  if (!window.Echo) {
    console.error("Echo not found. Make sure resources/js/echo.js is loaded and compiled.");
    updateStatus('Connection error', 'error');
  } else {
    window.Echo.channel('chat')
      .listen('MessageSent', (e) => {
        const chatBox = document.getElementById('chat-box');
        const isOwn = e.message.user.id === userId;
        
        const messageHTML = `
          <div class="message ${isOwn ? 'message-own' : 'message-other'}">
            <div class="message-avatar">
              <i class="fas fa-user"></i>
            </div>
            <div class="message-content">
              <div class="message-header">
                <span class="message-author">${e.message.user.name}</span>
                <span class="message-time">${new Date().toLocaleTimeString('en-US', {hour12: false, hour: '2-digit', minute:'2-digit'})}</span>
              </div>
              <div class="message-text">${e.message.message}</div>
            </div>
          </div>
        `;
        
        chatBox.insertAdjacentHTML('beforeend', messageHTML);
        chatBox.scrollTop = chatBox.scrollHeight;
      });

    // Enhanced form submission
    document.getElementById('chat-form').addEventListener('submit', function(e) {
      e.preventDefault();
      const messageInput = document.getElementById('message');
      const message = messageInput.value.trim();
      
      if (message) {
        // Disable input while sending
        messageInput.disabled = true;
        const sendBtn = document.querySelector('.send-btn');
        sendBtn.innerHTML = '<div class="loading-spinner"></div>';
        
        fetch('/chat', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ message: message })
        })
        .then(r => r.json())
        .then(data => {
          messageInput.value = '';
        })
        .catch(err => {
          console.error('Chat error:', err);
          updateStatus('Failed to send message', 'error');
        })
        .finally(() => {
          messageInput.disabled = false;
          sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
          messageInput.focus();
        });
      }
    });
  }

  // WebRTC setup with enhanced features
  let localStream = null;
  let pc = null;
  let iceCandidatesQueue = [];
  let isRemoteDescriptionSet = false;
  let currentCallTo = null;
  let isAudioMuted = false;
  let isVideoMuted = false;

  const iceServers = [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
    { urls: 'stun:stun2.l.google.com:19302' },
    { urls: 'stun:stun.cloudflare.com:3478' }
  ];

  const createPeerConnection = () => {
    const config = {
      iceServers,
      iceCandidatePoolSize: 10,
      bundlePolicy: 'max-bundle',
      rtcpMuxPolicy: 'require',
      iceTransportPolicy: 'all'
    };
    
    const peer = new RTCPeerConnection(config);

    // Enhanced connection state monitoring
    peer.onconnectionstatechange = () => {
      const state = peer.connectionState;
      console.log('Connection state:', state);
      
      switch(state) {
        case 'connected':
          updateStatus('Call connected', 'success');
          startCallTimer();
          hideVideoPlaceholders();
          break;
        case 'connecting':
          updateStatus('Connecting...', 'warning');
          break;
        case 'disconnected':
          updateStatus('Connection lost', 'warning');
          break;
        case 'failed':
          updateStatus('Connection failed - retrying...', 'error');
          peer.restartIce();
          break;
        case 'closed':
          updateStatus('Call ended', 'info');
          stopCallTimer();
          showVideoPlaceholders();
          break;
      }
    };

    peer.oniceconnectionstatechange = () => {
      console.log('ICE connection state:', peer.iceConnectionState);
      updateConnectionQuality(peer.iceConnectionState);
    };

    peer.onicegatheringstatechange = () => {
      console.log('ICE gathering state:', peer.iceGatheringState);
    };

    // Handle remote stream
    peer.ontrack = (event) => {
      console.log('Received remote track:', event.track.kind);
      const remoteVideo = document.getElementById('remoteVideo');
      if (remoteVideo.srcObject !== event.streams[0]) {
        remoteVideo.srcObject = event.streams[0];
        remoteVideo.onloadedmetadata = () => {
          hideVideoPlaceholder('remote');
        };
      }
    };

    // Send ICE candidates
    peer.onicecandidate = (e) => {
      if (e.candidate && currentCallTo) {
        console.log('Sending ICE candidate');
        fetch('/signal', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            type: 'candidate',
            to: currentCallTo,
            data: e.candidate.toJSON()
          })
        }).catch(err => {
          console.error('Failed to send ICE candidate:', err);
        });
      }
    };

    return peer;
  };

  // Video placeholder management
  const hideVideoPlaceholder = (type) => {
    const overlay = document.querySelector(`.${type}-overlay`);
    if (overlay) {
      overlay.style.opacity = '0';
      setTimeout(() => {
        overlay.style.display = 'none';
      }, 300);
    }
  };

  const showVideoPlaceholder = (type) => {
    const overlay = document.querySelector(`.${type}-overlay`);
    if (overlay) {
      overlay.style.display = 'flex';
      overlay.style.opacity = '1';
    }
  };

  const hideVideoPlaceholders = () => {
    hideVideoPlaceholder('remote');
  };

  const showVideoPlaceholders = () => {
    showVideoPlaceholder('remote');
  };

  // Connection quality indicator
  const updateConnectionQuality = (iceState) => {
    const qualityElement = document.getElementById('connectionQuality');
    let quality = 'Unknown';
    
    switch(iceState) {
      case 'connected':
      case 'completed':
        quality = 'Excellent';
        break;
      case 'checking':
        quality = 'Connecting...';
        break;
      case 'disconnected':
        quality = 'Poor';
        break;
      case 'failed':
        quality = 'Failed';
        break;
      case 'closed':
        quality = 'Disconnected';
        break;
    }
    
    if (qualityElement) {
      qualityElement.textContent = quality;
    }
  };

  // Clean SDP function
  const cleanSDP = (sdp) => {
    return sdp
      .replace(/a=ssrc-group:.*\r?\n/g, '')
      .replace(/a=ssrc:\d+ msid:[^\r\n]*\r?\n/g, (match) => {
        const parts = match.split(' ');
        if (parts.length >= 3) {
          const ssrcPart = parts[0];
          const msidPart = parts[1];
          const streamId = parts[2].replace(/\r?\n$/, '');
          return `${ssrcPart} ${msidPart}:${streamId}\r\n`;
        }
        return match;
      });
  };

  // Process queued ICE candidates
  const processQueuedCandidates = async () => {
    if (pc && isRemoteDescriptionSet && iceCandidatesQueue.length > 0) {
      console.log(`Processing ${iceCandidatesQueue.length} queued ICE candidates`);
      for (const candidate of iceCandidatesQueue) {
        try {
          await pc.addIceCandidate(candidate);
        } catch (err) {
          console.warn('Failed to add ICE candidate:', err);
        }
      }
      iceCandidatesQueue = [];
    }
  };

  // Enhanced local media initialization
  const initializeLocalMedia = async () => {
    try {
      const constraints = {
        video: {
          width: { min: 320, ideal: 640, max: 1280 },
          height: { min: 240, ideal: 480, max: 720 },
          frameRate: { ideal: 30 },
          facingMode: 'user'
        },
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          sampleRate: { ideal: 44100 }
        }
      };

      localStream = await navigator.mediaDevices.getUserMedia(constraints);
      const localVideo = document.getElementById('localVideo');
      localVideo.srcObject = localStream;
      
      localVideo.onloadedmetadata = () => {
        hideVideoPlaceholder('local');
      };
      
      updateStatus('Camera ready', 'success');
      return true;
    } catch (err) {
      console.error('Failed to get user media:', err);
      updateStatus('Camera access denied', 'error');
      
      // Show user-friendly error message
      const errorMsg = err.name === 'NotAllowedError' 
        ? 'Please allow camera and microphone access to use video chat.'
        : 'Unable to access camera/microphone. Please check your device settings.';
      
      alert(errorMsg);
      return false;
    }
  };

  // Media control functions
  const toggleAudio = () => {
    if (localStream) {
      const audioTracks = localStream.getAudioTracks();
      audioTracks.forEach(track => {
        track.enabled = !track.enabled;
      });
      isAudioMuted = !audioTracks[0].enabled;
      
      const muteBtn = document.getElementById('muteAudio');
      const toggleBtn = document.getElementById('toggleAudio');
      
      if (isAudioMuted) {
        muteBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
        toggleBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
        muteBtn.style.background = 'var(--danger-color)';
      } else {
        muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        toggleBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        muteBtn.style.background = 'rgba(0, 0, 0, 0.6)';
      }
    }
  };

  const toggleVideo = () => {
    if (localStream) {
      const videoTracks = localStream.getVideoTracks();
      videoTracks.forEach(track => {
        track.enabled = !track.enabled;
      });
      isVideoMuted = !videoTracks[0].enabled;
      
      const muteBtn = document.getElementById('muteVideo');
      const toggleBtn = document.getElementById('toggleVideo');
      
      if (isVideoMuted) {
        muteBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
        toggleBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
        muteBtn.style.background = 'var(--danger-color)';
        showVideoPlaceholder('local');
      } else {
        muteBtn.innerHTML = '<i class="fas fa-video"></i>';
        toggleBtn.innerHTML = '<i class="fas fa-video"></i>';
        muteBtn.style.background = 'rgba(0, 0, 0, 0.6)';
        hideVideoPlaceholder('local');
      }
    }
  };

  // Screen sharing function
  const toggleScreenShare = async () => {
    try {
      if (!pc) {
        alert('Please start a call first');
        return;
      }

      const screenBtn = document.getElementById('shareScreen');
      
      if (screenBtn.classList.contains('sharing')) {
        // Stop screen sharing, switch back to camera
        const videoTrack = localStream.getVideoTracks()[0];
        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
        
        if (sender && videoTrack) {
          await sender.replaceTrack(videoTrack);
          screenBtn.innerHTML = '<i class="fas fa-desktop"></i>';
          screenBtn.classList.remove('sharing');
          screenBtn.style.background = 'rgba(0, 0, 0, 0.6)';
          updateStatus('Screen sharing stopped', 'info');
        }
      } else {
        // Start screen sharing
        const screenStream = await navigator.mediaDevices.getDisplayMedia({
          video: true,
          audio: true
        });
        
        const videoTrack = screenStream.getVideoTracks()[0];
        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
        
        if (sender) {
          await sender.replaceTrack(videoTrack);
          screenBtn.innerHTML = '<i class="fas fa-stop"></i>';
          screenBtn.classList.add('sharing');
          screenBtn.style.background = 'var(--warning-color)';
          updateStatus('Screen sharing active', 'success');
          
          // Handle screen share end
          videoTrack.onended = async () => {
            const originalTrack = localStream.getVideoTracks()[0];
            if (sender && originalTrack) {
              await sender.replaceTrack(originalTrack);
              screenBtn.innerHTML = '<i class="fas fa-desktop"></i>';
              screenBtn.classList.remove('sharing');
              screenBtn.style.background = 'rgba(0, 0, 0, 0.6)';
              updateStatus('Screen sharing stopped', 'info');
            }
          };
        }
      }
    } catch (err) {
      console.error('Screen sharing error:', err);
      updateStatus('Screen sharing failed', 'error');
    }
  };

  // Fullscreen toggle
  const toggleFullscreen = () => {
    const videoContainer = document.querySelector('.video-container');
    
    if (!document.fullscreenElement) {
      videoContainer.requestFullscreen().catch(err => {
        console.error('Error entering fullscreen:', err);
      });
    } else {
      document.exitFullscreen();
    }
  };

  // Initialize local media
  const mediaReady = await initializeLocalMedia();
  if (!mediaReady) return;

  // Event listeners for media controls
  document.getElementById('muteAudio').addEventListener('click', toggleAudio);
  document.getElementById('muteVideo').addEventListener('click', toggleVideo);
  document.getElementById('toggleAudio').addEventListener('click', toggleAudio);
  document.getElementById('toggleVideo').addEventListener('click', toggleVideo);
  document.getElementById('shareScreen').addEventListener('click', toggleScreenShare);
  document.getElementById('toggleFullscreen').addEventListener('click', toggleFullscreen);

  // Subscribe to private channel for signaling
  if (window.Echo) {
    window.Echo.private('video.' + userId)
      .listen('.video.signal', async (e) => {
        const { from, type, data } = e.payload;
        console.log(`Received ${type} from user ${from}`);

        try {
          if (type === 'offer') {
            // Reset connection for incoming call
            if (pc) {
              pc.close();
            }
            
            pc = createPeerConnection();
            isRemoteDescriptionSet = false;
            iceCandidatesQueue = [];
            currentCallTo = from;

            // Add local tracks before setting remote description
            localStream.getTracks().forEach(track => {
              pc.addTrack(track, localStream);
            });

            // Clean and set remote description
            const cleanedSDP = cleanSDP(data.sdp);
            const offer = new RTCSessionDescription({
              type: data.type,
              sdp: cleanedSDP
            });

            updateStatus('Incoming call...', 'warning');
            await pc.setRemoteDescription(offer);
            isRemoteDescriptionSet = true;

            // Process any queued ICE candidates
            await processQueuedCandidates();

            // Create and send answer
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);

            await fetch('/signal', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                type: 'answer',
                to: from,
                data: pc.localDescription.toJSON()
              })
            });

            updateStatus('Answering call...', 'success');
          }
          else if (type === 'answer') {
            if (!pc) {
              console.warn('Received answer but no peer connection exists');
              return;
            }

            // Clean and set remote description
            const cleanedSDP = cleanSDP(data.sdp);
            const answer = new RTCSessionDescription({
              type: data.type,
              sdp: cleanedSDP
            });

            await pc.setRemoteDescription(answer);
            isRemoteDescriptionSet = true;

            // Process any queued ICE candidates
            await processQueuedCandidates();
            updateStatus('Call establishing...', 'success');
          }
          else if (type === 'candidate') {
            if (!pc) {
              console.warn('Received ICE candidate but no peer connection exists');
              return;
            }

            const candidate = new RTCIceCandidate(data);
            
            if (isRemoteDescriptionSet) {
              await pc.addIceCandidate(candidate);
            } else {
              iceCandidatesQueue.push(candidate);
            }
          }
        } catch (err) {
          console.error(`Error handling ${type} signal:`, err);
          updateStatus(`Error: ${err.message}`, 'error');
        }
      })
      .error((error) => {
        console.error('Echo channel error:', error);
        updateStatus('Connection error', 'error');
      });
  }

  // Start Call button
  document.getElementById('startCall').addEventListener('click', async () => {
    const to = parseInt(document.getElementById('call-to').value, 10);
    
    if (!to || isNaN(to)) {
      alert('Please enter a valid user ID');
      return;
    }

    if (to === userId) {
      alert('Cannot call yourself');
      return;
    }

    try {
      updateStatus('Starting call...', 'warning');
      
      // Reset connection state
      if (pc) {
        pc.close();
      }
      
      pc = createPeerConnection();
      isRemoteDescriptionSet = false;
      iceCandidatesQueue = [];
      currentCallTo = to;

      // Add local tracks
      localStream.getTracks().forEach(track => {
        pc.addTrack(track, localStream);
      });

      // Create offer with enhanced constraints
      const offerOptions = {
        offerToReceiveAudio: true,
        offerToReceiveVideo: true,
        voiceActivityDetection: false
      };

      const offer = await pc.createOffer(offerOptions);
      await pc.setLocalDescription(offer);

      // Send the offer
      const response = await fetch('/signal', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          type: 'offer',
          to: to,
          data: pc.localDescription.toJSON()
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      updateStatus('Calling...', 'warning');
    } catch (err) {
      console.error('Error starting call:', err);
      updateStatus(`Failed to start call: ${err.message}`, 'error');
    }
  });

  // Hangup button
  document.getElementById('hangup').addEventListener('click', () => {
    if (pc) {
      pc.close();
      pc = null;
    }

    // Clear remote video
    const remoteVideo = document.getElementById('remoteVideo');
    if (remoteVideo.srcObject) {
      remoteVideo.srcObject = null;
    }

    // Reset state
    isRemoteDescriptionSet = false;
    iceCandidatesQueue = [];
    currentCallTo = null;
    
    // Reset media controls
    document.getElementById('muteAudio').innerHTML = '<i class="fas fa-microphone"></i>';
    document.getElementById('muteVideo').innerHTML = '<i class="fas fa-video"></i>';
    document.getElementById('shareScreen').innerHTML = '<i class="fas fa-desktop"></i>';
    
    // Reset button styles
    document.querySelectorAll('.control-btn').forEach(btn => {
      btn.style.background = 'rgba(0, 0, 0, 0.6)';
      btn.classList.remove('sharing');
    });
    
    stopCallTimer();
    showVideoPlaceholders();
    updateStatus('Ready to call', 'info');
  });

  // Handle page unload
  window.addEventListener('beforeunload', () => {
    if (pc) {
      pc.close();
    }
    if (localStream) {
      localStream.getTracks().forEach(track => track.stop());
    }
  });

  // Handle visibility changes to manage resources
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && localStream) {
      // Optionally pause video when tab is hidden
      localStream.getVideoTracks().forEach(track => {
        track.enabled = false;
      });
    } else if (!document.hidden && localStream) {
      // Resume video when tab becomes visible (if not muted)
      if (!isVideoMuted) {
        localStream.getVideoTracks().forEach(track => {
          track.enabled = true;
        });
      }
    }
  });

  // Auto-resize local video on window resize
  window.addEventListener('resize', () => {
    const localVideoWrapper = document.querySelector('.local-video-wrapper');
    if (window.innerWidth < 768) {
      localVideoWrapper.style.width = '100px';
      localVideoWrapper.style.height = '75px';
    } else {
      localVideoWrapper.style.width = '200px';
      localVideoWrapper.style.height = '150px';
    }
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey) {
      switch(e.key) {
        case 'm':
          e.preventDefault();
          toggleAudio();
          break;
        case 'd':
          e.preventDefault();
          toggleVideo();
          break;
        case 's':
          e.preventDefault();
          toggleScreenShare();
          break;
        case 'Enter':
          if (e.target.id === 'call-to') {
            e.preventDefault();
            document.getElementById('startCall').click();
          }
          break;
      }
    }
  });

  // Initialize status
  updateStatus('Ready to call', 'success');
});
</script>