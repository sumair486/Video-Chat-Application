@extends('layouts.app')

@section('content')
<div class="video-chat-container">
  <!-- Header -->
  <div class="chat-header">
    <div class="container">
      <h1 class="chat-title">
        <i class="fas fa-video"></i>
        Private Chat & Video
      </h1>
      <div class="user-info">
        <div class="user-avatar">
          <i class="fas fa-user"></i>
        </div>
        <span class="user-name">{{ auth()->user()->name }}</span>
        <div class="online-indicator">
          <span class="pulse"></span>
          Online
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="row g-4">
      <!-- Left Panel - User List & Chat -->
      <div class="col-lg-5 col-md-6">
        <!-- User Selection Panel -->
        <div class="user-list-panel mb-4">
          <div class="panel-header">
            <i class="fas fa-users"></i>
            <span>Select User to Chat</span>
            <div class="user-count">{{ count($users) }} users</div>
          </div>
          
          <div class="user-list" id="user-list">
            @foreach($users as $user)
              <div class="user-item {{ $chattingWith && $chattingWith->id === $user->id ? 'active' : '' }}" 
                   data-user-id="{{ $user->id }}" 
                   data-user-name="{{ $user->name }}">
                <div class="user-avatar-small">
                  <i class="fas fa-user"></i>
                  <span class="status-dot {{ $user->isOnline() ? 'online' : 'offline' }}"></span>
                </div>
                <div class="user-details">
                  <span class="user-name-text">{{ $user->name }}</span>
                  <span class="user-status">{{ $user->isOnline() ? 'Online' : 'Offline' }}</span>
                </div>
                @if($user->unread_messages_count > 0)
                  <div class="unread-badge">{{ $user->unread_messages_count }}</div>
                @endif
              </div>
            @endforeach
          </div>
        </div>

        <!-- Chat Section -->
        <div class="chat-panel">
          <div class="chat-panel-header">
            <i class="fas fa-comments"></i>
            <span id="chat-title">
              @if($chattingWith)
                Chat with {{ $chattingWith->name }}
              @else
                Select a user to start chatting
              @endif
            </span>
            @if($chattingWith)
              <div class="chat-user-status">
                <span class="status-dot {{ $chattingWith->isOnline() ? 'online' : 'offline' }}"></span>
                {{ $chattingWith->isOnline() ? 'Online' : 'Offline' }}
              </div>
            @endif
          </div>
          
          <div class="chat-messages" id="chat-box">
            @if($chattingWith)
              @foreach($messages as $message)
                <div class="message {{ $message->user->id === auth()->id() ? 'message-own' : 'message-other' }}">
                  <div class="message-avatar">
                    <i class="fas fa-user"></i>
                  </div>
                  <div class="message-content">
                    <div class="message-header">
                      <span class="message-author">{{ $message->user->name }}</span>
                      <span class="message-time">{{ $message->created_at->format('H:i') }}</span>
                      @if($message->user->id === auth()->id())
                        <span class="read-status">
                          <i class="fas {{ $message->is_read ? 'fa-check-double text-primary' : 'fa-check' }}"></i>
                        </span>
                      @endif
                    </div>
                    <div class="message-text">{{ $message->message }}</div>
                  </div>
                </div>
              @endforeach
            @else
              <div class="no-chat-selected">
                <i class="fas fa-comment-dots"></i>
                <p>Select a user from the list above to start chatting</p>
              </div>
            @endif
          </div>
          
          @if($chattingWith)
            <div class="chat-input-wrapper">
              <form id="chat-form" class="chat-form">
                @csrf
                <input type="hidden" id="receiver-id" value="{{ $chattingWith->id }}">
                <div class="input-group">
                  <input type="text" name="message" id="message" class="chat-input" 
                         placeholder="Type your message to {{ $chattingWith->name }}..." autocomplete="off">
                  <button type="submit" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                </div>
              </form>
              <div class="typing-indicator" id="typing-indicator" style="display: none;">
                <span></span> is typing...
              </div>
            </div>
          @else
            <div class="chat-input-disabled">
              <p>Select a user to start messaging</p>
            </div>
          @endif
        </div>

        <!-- Call Controls -->
        @if($chattingWith)
          <div class="call-controls-panel">
            <div class="panel-header">
              <i class="fas fa-phone-alt"></i>
              <span>Video Call with {{ $chattingWith->name }}</span>
            </div>
            
            <div class="call-setup">
              <input type="hidden" id="call-to" value="{{ $chattingWith->id }}">
              
              <div class="call-actions">
                <button id="startCall" class="btn btn-call">
                  <i class="fas fa-video"></i>
                  Start Video Call
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
        @endif
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
                  <span>Waiting for video call...</span>
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const userId = {{ auth()->id() }};
  let currentChattingWith = {{ $chattingWith ? $chattingWith->id : 'null' }};
  let currentChattingWithName = '{{ $chattingWith ? $chattingWith->name : '' }}';
  
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

  // User status update (ping server every 60 seconds)
  const updateUserStatus = () => {
    fetch('/user/update-status', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json'
      }
    }).catch(err => console.warn('Status update failed:', err));
  };

  // Update status immediately and then every minute
  updateUserStatus();
  setInterval(updateUserStatus, 60000);

  // User selection functionality
  document.querySelectorAll('.user-item').forEach(item => {
    item.addEventListener('click', async () => {
      const userId = item.dataset.userId;
      const userName = item.dataset.userName;
      
      // Update URL and reload with selected user
      window.location.href = `/chat?with=${userId}`;
    });
  });

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

  // Enhanced chat functionality with private channels
  if (!window.Echo) {
    console.error("Echo not found. Make sure resources/js/echo.js is loaded and compiled.");
    updateStatus('Connection error', 'error');
  } else {
    // Listen to private chat channel for this user
    window.Echo.private('chat.' + userId)
      .listen('.message.sent', (e) => {
        const message = e.message;
        
        // Only show message if it's from/to the currently selected chat
        if (currentChattingWith && 
            (message.user.id === currentChattingWith || message.receiver_id === currentChattingWith)) {
          
          const chatBox = document.getElementById('chat-box');
          const isOwn = message.user.id === userId;
          
          const messageHTML = `
            <div class="message ${isOwn ? 'message-own' : 'message-other'}">
              <div class="message-avatar">
                <i class="fas fa-user"></i>
              </div>
              <div class="message-content">
                <div class="message-header">
                  <span class="message-author">${message.user.name}</span>
                  <span class="message-time">${new Date().toLocaleTimeString('en-US', {hour12: false, hour: '2-digit', minute:'2-digit'})}</span>
                  ${isOwn ? '<span class="read-status"><i class="fas fa-check"></i></span>' : ''}
                </div>
                <div class="message-text">${message.message}</div>
              </div>
            </div>
          `;
          
          chatBox.insertAdjacentHTML('beforeend', messageHTML);
          chatBox.scrollTop = chatBox.scrollHeight;

          // Mark as read if not own message
          if (!isOwn) {
            fetch(`/chat/mark-read/${message.user.id}`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
              }
            }).catch(err => console.warn('Mark read failed:', err));
          }
        } else {
          // Update unread count in user list
          updateUnreadCount(message.user.id);
        }
      });

    // Enhanced form submission for private messages
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
      chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!currentChattingWith) {
          alert('Please select a user to chat with');
          return;
        }
        
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
            body: JSON.stringify({ 
              message: message,
              receiver_id: currentChattingWith
            })
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              messageInput.value = '';
            } else {
              throw new Error(data.error || 'Failed to send message');
            }
          })
          .catch(err => {
            console.error('Chat error:', err);
            alert('Failed to send message: ' + err.message);
          })
          .finally(() => {
            messageInput.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            messageInput.focus();
          });
        }
      });
    }
  }

  // Function to update unread count in user list
  const updateUnreadCount = (fromUserId) => {
    const userItem = document.querySelector(`[data-user-id="${fromUserId}"]`);
    if (userItem) {
      let badge = userItem.querySelector('.unread-badge');
      if (!badge) {
        badge = document.createElement('div');
        badge.className = 'unread-badge';
        badge.textContent = '1';
        userItem.appendChild(badge);
      } else {
        const count = parseInt(badge.textContent) + 1;
        badge.textContent = count;
      }
    }
  };

  // WebRTC setup with enhanced features for private calls
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
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
        muteBtn.style.background = 'var(--danger-color)';
      } else {
        muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-microphone"></i>';
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
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
        muteBtn.style.background = 'var(--danger-color)';
        showVideoPlaceholder('local');
      } else {
        muteBtn.innerHTML = '<i class="fas fa-video"></i>';
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-video"></i>';
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
  const toggleAudioBtn = document.getElementById('toggleAudio');
  const toggleVideoBtn = document.getElementById('toggleVideo');
  if (toggleAudioBtn) toggleAudioBtn.addEventListener('click', toggleAudio);
  if (toggleVideoBtn) toggleVideoBtn.addEventListener('click', toggleVideo);
  document.getElementById('shareScreen').addEventListener('click', toggleScreenShare);
  document.getElementById('toggleFullscreen').addEventListener('click', toggleFullscreen);

  // Subscribe to private video channel for signaling
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

  // Start Call button - now uses the selected chat user
  const startCallBtn = document.getElementById('startCall');
  if (startCallBtn) {
    startCallBtn.addEventListener('click', async () => {
      if (!currentChattingWith) {
        alert('Please select a user to call');
        return;
      }

      try {
        updateStatus(`Calling ${currentChattingWithName}...`, 'warning');
        
        // Reset connection state
        if (pc) {
          pc.close();
        }
        
        pc = createPeerConnection();
        isRemoteDescriptionSet = false;
        iceCandidatesQueue = [];
        currentCallTo = currentChattingWith;

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
            to: currentChattingWith,
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
  }

  // Hangup button
  const hangupBtn = document.getElementById('hangup');
  if (hangupBtn) {
    hangupBtn.addEventListener('click', () => {
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
  }

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
    if (window.innerWidth < 768 && localVideoWrapper) {
      localVideoWrapper.style.width = '100px';
      localVideoWrapper.style.height = '75px';
    } else if (localVideoWrapper) {
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
          if (e.target.id === 'message') {
            // Allow normal form submission
            return;
          }
          break;
      }
    }
  });

  // Initialize status
  if (currentChattingWith) {
    updateStatus('Ready to call', 'success');
  } else {
    updateStatus('Select a user to start', 'info');
  }
});
</script>