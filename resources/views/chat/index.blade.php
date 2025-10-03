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
      <div class="message {{ $message->user->id === auth()->id() ? 'message-own' : 'message-other' }}" data-message-id="{{ $message->id }}">
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
          
          @if($message->is_file_message)
            <!-- File Message -->
            <div class="message-file">
              @if($message->isImage())
                <div class="image-message">
                  <img src="{{ $message->file_url }}" alt="{{ $message->original_name }}" class="message-image" loading="lazy">
                  <div class="image-overlay">
                    <a href="{{ $message->file_url }}" target="_blank" class="image-view-btn">
                      <i class="fas fa-expand"></i>
                    </a>
                    <a href="/chat/download/{{ $message->id }}" class="image-download-btn">
                      <i class="fas fa-download"></i>
                    </a>
                  </div>
                </div>
              @elseif($message->isVideo())
                <div class="video-message">
                  <video controls class="message-video" preload="metadata">
                    <source src="{{ $message->file_url }}" type="{{ $message->file_type }}">
                    Your browser does not support the video tag.
                  </video>
                  <div class="video-info">
                    <span class="file-name">{{ $message->original_name }}</span>
                    <span class="file-size">{{ $message->formatted_file_size }}</span>
                  </div>
                  
                </div>
              @elseif($message->isAudio())
                <div class="audio-message">
                  <audio controls class="message-audio">
                    <source src="{{ $message->file_url }}" type="{{ $message->file_type }}">
                    Your browser does not support the audio tag.
                  </audio>
                  <div class="audio-info">
                    <i class="{{ $message->getFileIcon() }}"></i>
                    <div class="audio-details">
                      <span class="file-name">{{ $message->original_name }}</span>
                      <span class="file-size">{{ $message->formatted_file_size }}</span>
                    </div>
                  </div>
                </div>
              @else
                <!-- Document/File Message -->
                <div class="file-message">
                  <div class="file-icon">
                    <i class="{{ $message->getFileIcon() }}"></i>
                  </div>
                  <div class="file-info">
                    <span class="file-name">{{ $message->original_name }}</span>
                    <span class="file-size">{{ $message->formatted_file_size }}</span>
                  </div>
                  <div class="file-actions">
                    <a href="/chat/download/{{ $message->id }}" class="file-download-btn" title="Download">
                      <i class="fas fa-download"></i>
                    </a>
                  </div>
                </div>
              @endif
              
              @if($message->message)
                <div class="file-caption">{{ $message->message }}</div>
              @endif
            </div>
          @else
            <!-- Text Message -->
            <div class="message-text">{{ $message->message }}</div>
          @endif
        </div>
      </div>

       <!-- Add reactions display -->
      @if($message->reactions->count() > 0)
        <div class="message-reactions">
          @php
            $groupedReactions = $message->grouped_reactions;
          @endphp
          @foreach($groupedReactions as $reactionGroup)
            <div class="reaction-bubble {{ in_array(auth()->id(), $reactionGroup['user_ids']) ? 'user-reacted' : '' }}" 
                 data-reaction="{{ $reactionGroup['reaction'] }}"
                 data-message-id="{{ $message->id }}">
              <span class="reaction-emoji">{{ $reactionGroup['reaction'] }}</span>
              <span class="reaction-count">{{ $reactionGroup['count'] }}</span>
              <div class="reaction-tooltip">
                @foreach($reactionGroup['user_ids'] as $reactorId)
                  {{ $reactorId === auth()->id() ? 'You' : \App\Models\User::find($reactorId)->name }}{{ !$loop->last ? ', ' : '' }}
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      @endif
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
              <!-- File Upload Form (hidden) -->
              <form id="file-upload-form" class="file-form" enctype="multipart/form-data" style="display: none;">
                @csrf
                <input type="hidden" name="receiver_id" value="{{ $chattingWith->id }}">
                <input type="file" id="file-input" name="file" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.csv,.zip,.rar">
                <input type="text" name="message" id="file-caption" placeholder="Add a caption (optional)...">
              </form>

              <!-- Text Message Form -->
              <form id="chat-form" class="chat-form">
                @csrf
                <input type="hidden" id="receiver-id" value="{{ $chattingWith->id }}">
                <div class="input-group">
                  <button type="button" class="attachment-btn" id="attachment-btn" title="Attach File">
                    <i class="fas fa-paperclip"></i>
                  </button>
                  <input type="text" name="message" id="message" class="chat-input" 
                         placeholder="Type your message to {{ $chattingWith->name }}..." autocomplete="off">
                  <button type="button" class="emoji-btn" id="emoji-btn" title="Add Emoji">
                    <i class="fas fa-smile"></i>
                  </button>
                  <button type="submit" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                </div>
              </form>
              
              <!-- File Preview Area -->
              <div class="file-preview-area" id="file-preview-area" style="display: none;">
                <div class="file-preview-content">
                  <div class="file-preview-info">
                    <div class="file-icon-preview">
                      <i class="fas fa-file"></i>
                    </div>
                    <div class="file-details-preview">
                      <span class="file-name-preview"></span>
                      <span class="file-size-preview"></span>
                    </div>
                  </div>
                  <div class="file-preview-actions">
                    <input type="text" id="file-message-input" class="file-caption-input" placeholder="Add a caption (optional)...">
                    <button type="button" class="file-send-btn" id="file-send-btn">
                      <i class="fas fa-paper-plane"></i>
                      Send
                    </button>
                    <button type="button" class="file-cancel-btn" id="file-cancel-btn">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>

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
   window.Echo.private('chat.' + userId)
  .listen('.message.sent', (e) => {
    const message = e.message;
    
    // Only show message if it's from/to the currently selected chat
    if (currentChattingWith && 
        (message.user.id === currentChattingWith || message.receiver_id === currentChattingWith)) {
      
      const chatBox = document.getElementById('chat-box');
      const isOwn = message.user.id === userId;
      
      // Create message HTML based on message type
      let messageHTML = `
        <div class="message ${isOwn ? 'message-own' : 'message-other'}" data-message-id="${message.id}">
          <div class="message-avatar">
            <i class="fas fa-user"></i>
          </div>
          <div class="message-content">
            <div class="message-header">
              <span class="message-author">${message.user.name}</span>
              <span class="message-time">${new Date().toLocaleTimeString('en-US', {hour12: false, hour: '2-digit', minute:'2-digit'})}</span>
              ${isOwn ? '<span class="read-status"><i class="fas fa-check"></i></span>' : ''}
            </div>
      `;

      // Add content based on message type - THIS IS THE IMPORTANT PART
      if (message.is_file_message || message.file_path) {
        messageHTML += '<div class="message-file">';
        
        if (message.type === 'image') {
          messageHTML += `
            <div class="image-message">
              <img src="${message.file_url}" alt="${message.original_name || message.file_name}" class="message-image" loading="lazy">
              <div class="image-overlay">
                <a href="${message.file_url}" target="_blank" class="image-view-btn">
                  <i class="fas fa-expand"></i>
                </a>
                <a href="/chat/download/${message.id}" class="image-download-btn">
                  <i class="fas fa-download"></i>
                </a>
              </div>
            </div>
          `;
        } else if (message.type === 'video') {
          messageHTML += `
            <div class="video-message">
              <video controls class="message-video" preload="metadata">
                <source src="${message.file_url}" type="${message.file_type}">
                Your browser does not support the video tag.
              </video>
              <div class="video-info">
                <span class="file-name">${message.original_name || message.file_name}</span>
                <span class="file-size">${message.formatted_file_size || ''}</span>
              </div>
            </div>
          `;
        } else if (message.type === 'audio') {
          messageHTML += `
            <div class="audio-message">
              <audio controls class="message-audio">
                <source src="${message.file_url}" type="${message.file_type}">
                Your browser does not support the audio tag.
              </audio>
              <div class="audio-info">
                <i class="fas fa-music"></i>
                <div class="audio-details">
                  <span class="file-name">${message.original_name || message.file_name}</span>
                  <span class="file-size">${message.formatted_file_size || ''}</span>
                </div>
              </div>
            </div>
          `;
        } else {
          // Document or other file type
          const fileIcon = getFileIcon(message.file_type);
          messageHTML += `
            <div class="file-message">
              <div class="file-icon">
                <i class="${fileIcon}"></i>
              </div>
              <div class="file-info">
                <span class="file-name">${message.original_name || message.file_name}</span>
                <span class="file-size">${message.formatted_file_size || ''}</span>
              </div>
              <div class="file-actions">
                <a href="/chat/download/${message.id}" class="file-download-btn" title="Download">
                  <i class="fas fa-download"></i>
                </a>
              </div>
            </div>
          `;
        }

        // Add caption if exists
        if (message.message && message.message.trim()) {
          messageHTML += `<div class="file-caption">${message.message}</div>`;
        }
        
        messageHTML += '</div>'; // Close message-file
      } else {
        // Text message
        messageHTML += `<div class="message-text">${message.message}</div>`;
      }

      messageHTML += `
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
  })
      .listen('.message.read', (e) => {
        // Update read status for sent messages
        const messageIds = e.message_ids;
        messageIds.forEach(messageId => {
          const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
          if (messageElement) {
            const readStatus = messageElement.querySelector('.read-status i');
            if (readStatus) {
              readStatus.className = 'fas fa-check-double text-primary';
            }
          }
        });
      });

    // Listen to user status changes on public channel
    window.Echo.channel('user-status')
      .listen('.user.status.changed', (e) => {
        const user = e.user;
        updateUserStatusInList(user.id, user.status);
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

  // Function to update user status in the user list
  const updateUserStatusInList = (userId, status) => {
    const userItem = document.querySelector(`[data-user-id="${userId}"]`);
    if (userItem) {
      const statusDot = userItem.querySelector('.status-dot');
      const statusText = userItem.querySelector('.user-status');
      
      if (statusDot) {
        statusDot.className = `status-dot ${status}`;
      }
      
      if (statusText) {
        statusText.textContent = status === 'online' ? 'Online' : 'Offline';
      }

      // Update in chat header if this is the current chat user
      if (currentChattingWith && parseInt(userId) === currentChattingWith) {
        const chatStatusDot = document.querySelector('.chat-user-status .status-dot');
        const chatStatusText = document.querySelector('.chat-user-status');
        
        if (chatStatusDot) {
          chatStatusDot.className = `status-dot ${status}`;
        }
        
        if (chatStatusText) {
          const textNode = chatStatusText.childNodes[1]; // Get the text node after the status dot
          if (textNode) {
            textNode.textContent = status === 'online' ? ' Online' : ' Offline';
          }
        }
      }
    }
  };

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

          // old code-------------------------------------


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


          // -------------------------------------------old code --------------


          //---------------new code------------------------------------

//           else if (type === 'candidate') {
//   if (!pc) {
//     console.warn('Received ICE candidate but no peer connection exists');
//     return;
//   }

//   // ADD THIS VALIDATION
//   if (!data || !data.candidate || typeof data.candidate !== 'string') {
//     console.warn('Invalid or empty ICE candidate received:', data);
//     return;
//   }

//   // Additional validation for candidate format
//   if (!data.candidate.includes(':')) {
//     console.warn('Malformed ICE candidate (missing colon):', data.candidate);
//     return;
//   }

//   const candidate = new RTCIceCandidate(data);
  
//   if (isRemoteDescriptionSet) {
//     await pc.addIceCandidate(candidate);
//   } else {
//     iceCandidatesQueue.push(candidate);
//   }
// }

// ----------------new code-----------------------------------

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



  const REACTIONS = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🔥'];

// Function to create reaction picker HTML
const createReactionPicker = () => {
  const picker = document.createElement('div');
  picker.className = 'reaction-picker';
  picker.innerHTML = REACTIONS.map(emoji => 
    `<button class="reaction-option" data-reaction="${emoji}">${emoji}</button>`
  ).join('');
  return picker;
};

// Function to create reaction button for messages
const createReactionButton = () => {
  const btn = document.createElement('button');
  btn.className = 'message-reaction-btn';
  btn.innerHTML = '<i class="far fa-smile"></i>';
  btn.title = 'Add reaction';
  return btn;
};

// Function to render reactions on a message
const renderReactions = (messageElement, reactions) => {
  let reactionsContainer = messageElement.querySelector('.message-reactions');
  
  if (!reactionsContainer) {
    reactionsContainer = document.createElement('div');
    reactionsContainer.className = 'message-reactions';
    messageElement.querySelector('.message-content').appendChild(reactionsContainer);
  }
  
  if (!reactions || reactions.length === 0) {
    reactionsContainer.innerHTML = '';
    return;
  }
  
  reactionsContainer.innerHTML = reactions.map(reaction => {
    const isUserReacted = reaction.user_ids.includes(userId);
    const userNames = reaction.user_ids.map(id => {
      // You might want to fetch user names from somewhere
      return id === userId ? 'You' : `User ${id}`;
    }).join(', ');
    
    return `
      <div class="reaction-bubble ${isUserReacted ? 'user-reacted' : ''}" 
           data-reaction="${reaction.reaction}"
           data-message-id="${messageElement.dataset.messageId}">
        <span class="reaction-emoji">${reaction.reaction}</span>
        <span class="reaction-count">${reaction.count}</span>
        <div class="reaction-tooltip">${userNames}</div>
      </div>
    `;
  }).join('');
};

// Function to add reaction to a message
const addReaction = async (messageId, reaction) => {
  try {
    const response = await fetch(`/messages/${messageId}/react`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ reaction })
    });
    
    const data = await response.json();
    
    if (data.success) {
      const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
      if (messageElement) {
        renderReactions(messageElement, data.grouped_reactions);
      }
    } else {
      console.error('Failed to add reaction:', data.error);
    }
  } catch (err) {
    console.error('Error adding reaction:', err);
  }
};

// Function to remove reaction from a message
const removeReaction = async (messageId) => {
  try {
    const response = await fetch(`/messages/${messageId}/react`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
      if (messageElement) {
        renderReactions(messageElement, data.grouped_reactions);
      }
    }
  } catch (err) {
    console.error('Error removing reaction:', err);
  }
};

// Initialize reaction buttons for all messages
const initializeReactions = () => {
  document.querySelectorAll('.message').forEach(messageElement => {
    // Skip if already initialized
    if (messageElement.querySelector('.message-reaction-btn')) {
      return;
    }
    
    const messageContent = messageElement.querySelector('.message-content');
    if (!messageContent) return;
    
    // Add reaction button
    const reactionBtn = createReactionButton();
    messageElement.appendChild(reactionBtn);
    
    // Create reaction picker
    const reactionPicker = createReactionPicker();
    messageElement.appendChild(reactionPicker);
    
    // Show/hide reaction picker
    let hideTimeout;
    
    reactionBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      
      // Hide all other pickers
      document.querySelectorAll('.reaction-picker.show').forEach(picker => {
        if (picker !== reactionPicker) {
          picker.classList.remove('show');
        }
      });
      
      reactionPicker.classList.toggle('show');
      clearTimeout(hideTimeout);
    });
    
    // Handle reaction selection
    reactionPicker.addEventListener('click', (e) => {
      if (e.target.classList.contains('reaction-option')) {
        const reaction = e.target.dataset.reaction;
        const messageId = messageElement.dataset.messageId;
        addReaction(messageId, reaction);
        reactionPicker.classList.remove('show');
      }
    });
    
    // Auto-hide picker after 3 seconds
    reactionBtn.addEventListener('mouseenter', () => {
      clearTimeout(hideTimeout);
    });
    
    reactionPicker.addEventListener('mouseenter', () => {
      clearTimeout(hideTimeout);
    });
    
    reactionPicker.addEventListener('mouseleave', () => {
      hideTimeout = setTimeout(() => {
        reactionPicker.classList.remove('show');
      }, 1000);
    });
  });
  
  // Handle clicking on existing reactions (toggle)
  document.addEventListener('click', (e) => {
    const reactionBubble = e.target.closest('.reaction-bubble');
    if (reactionBubble) {
      const messageId = reactionBubble.dataset.messageId;
      const reaction = reactionBubble.dataset.reaction;
      
      // If user already reacted with this emoji, remove it
      if (reactionBubble.classList.contains('user-reacted')) {
        removeReaction(messageId);
      } else {
        // Otherwise add/change reaction
        addReaction(messageId, reaction);
      }
    }
  });
  
  // Close pickers when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.message-reaction-btn') && 
        !e.target.closest('.reaction-picker')) {
      document.querySelectorAll('.reaction-picker.show').forEach(picker => {
        picker.classList.remove('show');
      });
    }
  });
};

// Initialize reactions on page load
initializeReactions();

// Listen for new messages and initialize reactions
const originalChatBoxObserver = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.addedNodes.length) {
      mutation.addedNodes.forEach(node => {
        if (node.classList && node.classList.contains('message')) {
          initializeReactions();
        }
      });
    }
  });
});

const chatBox = document.getElementById('chat-box');
if (chatBox) {
  originalChatBoxObserver.observe(chatBox, {
    childList: true,
    subtree: true
  });
}

// Listen for real-time reaction updates via Echo
if (window.Echo) {
  window.Echo.private('chat.' + userId)
    .listen('.message.reaction.added', (e) => {
      console.log('Reaction received:', e);
      
      const messageElement = document.querySelector(`[data-message-id="${e.message_id}"]`);
      if (messageElement) {
        // Fetch updated reactions for this message
        fetch(`/messages/${e.message_id}/reactions`)
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              renderReactions(messageElement, data.reactions);
              
              // Show a brief animation if it's not the current user's reaction
              if (e.user_id !== userId) {
                const newReaction = messageElement.querySelector(
                  `.reaction-bubble[data-reaction="${e.reaction}"]`
                );
                if (newReaction) {
                  newReaction.classList.add('new');
                  setTimeout(() => {
                    newReaction.classList.remove('new');
                  }, 300);
                }
              }
            }
          })
          .catch(err => console.error('Error fetching reactions:', err));
      }
    });
}


  // Add this JavaScript code to your existing script section, after the existing code

// Function to get file icon based on mime type
const getFileIcon = (mimeType) => {
  if (!mimeType) return 'fas fa-file';
  
  if (mimeType.startsWith('image/')) return 'fas fa-image';
  if (mimeType.startsWith('video/')) return 'fas fa-video';
  if (mimeType.startsWith('audio/')) return 'fas fa-music';
  if (mimeType.includes('pdf')) return 'fas fa-file-pdf';
  if (mimeType.includes('word')) return 'fas fa-file-word';
  if (mimeType.includes('excel') || mimeType.includes('sheet')) return 'fas fa-file-excel';
  if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'fas fa-file-powerpoint';
  if (mimeType.includes('text')) return 'fas fa-file-alt';
  if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('7z')) return 'fas fa-file-archive';
  
  return 'fas fa-file';
};

// Function to format file size
const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex++;
  }
  
  return `${Math.round(size * 100) / 100} ${units[unitIndex]}`;
};


// Add this code to your existing DOMContentLoaded event listener
// Place it after your Echo setup and before the file upload functionality

// Typing indicator functionality
let typingTimeout = null;
const TYPING_TIMER_LENGTH = 3000; // 3 seconds
let isTyping = false;

const messageInput = document.getElementById('message');
const typingIndicator = document.getElementById('typing-indicator');

if (messageInput && currentChattingWith) {
  // Detect when user is typing
  messageInput.addEventListener('input', () => {
    if (!isTyping && currentChattingWith) {
      isTyping = true;
      
      // Broadcast that user started typing
      fetch('/chat/typing', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          receiver_id: currentChattingWith,
          typing: true
        })
      }).catch(err => console.warn('Typing broadcast failed:', err));
    }

    // Clear existing timeout
    clearTimeout(typingTimeout);

    // Set timeout to stop typing indicator after 3 seconds of inactivity
    typingTimeout = setTimeout(() => {
      isTyping = false;
      
      // Broadcast that user stopped typing
      fetch('/chat/typing', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          receiver_id: currentChattingWith,
          typing: false
        })
      }).catch(err => console.warn('Typing stop broadcast failed:', err));
    }, TYPING_TIMER_LENGTH);
  });

  // Stop typing indicator when user submits message
  const chatForm = document.getElementById('chat-form');
  if (chatForm) {
    chatForm.addEventListener('submit', () => {
      clearTimeout(typingTimeout);
      isTyping = false;
      
      // Broadcast that user stopped typing
      fetch('/chat/typing', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          receiver_id: currentChattingWith,
          typing: false
        })
      }).catch(err => console.warn('Typing stop broadcast failed:', err));
    });
  }
}

// Listen for typing events from other users
if (window.Echo) {
  window.Echo.private('chat.' + userId)
    .listen('.user.typing', (e) => {
      // Only show typing indicator if message is from current chat partner
      if (currentChattingWith && e.user_id === currentChattingWith) {
        if (e.typing) {
          // Show typing indicator
          if (typingIndicator) {
            const typingName = typingIndicator.querySelector('span');
            if (typingName) {
              typingName.textContent = e.user_name || currentChattingWithName;
            }
            typingIndicator.style.display = 'block';
            
            // Scroll chat to bottom to show typing indicator
            const chatBox = document.getElementById('chat-box');
            if (chatBox) {
              chatBox.scrollTop = chatBox.scrollHeight;
            }
          }
        } else {
          // Hide typing indicator
          if (typingIndicator) {
            typingIndicator.style.display = 'none';
          }
        }
      }
    });
}

// File upload functionality
const attachmentBtn = document.getElementById('attachment-btn');
const fileInput = document.getElementById('file-input');
const filePreviewArea = document.getElementById('file-preview-area');
const fileSendBtn = document.getElementById('file-send-btn');
const fileCancelBtn = document.getElementById('file-cancel-btn');
const fileMessageInput = document.getElementById('file-message-input');
const emojiBtn = document.getElementById('emoji-btn');

let selectedFile = null;
const maxFileSize = 52428800; // 50MB

// Attachment button functionality
if (attachmentBtn && fileInput) {
  attachmentBtn.addEventListener('click', () => {
    console.log('Attachment button clicked');
    fileInput.click();
  });

  fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      console.log('File selected:', file.name, file.size);
      
      if (file.size > maxFileSize) {
        alert(`File size too large. Maximum allowed size is ${Math.round(maxFileSize / 1048576)}MB.`);
        fileInput.value = '';
        return;
      }

      selectedFile = file;
      showFilePreview(file);
    }
  });

  if (fileSendBtn) {
    fileSendBtn.addEventListener('click', () => {
      if (selectedFile) {
        sendFileMessage(selectedFile, fileMessageInput.value.trim());
      }
    });
  }

  if (fileCancelBtn) {
    fileCancelBtn.addEventListener('click', () => {
      hideFilePreview();
    });
  }
}

// Emoji button functionality (basic implementation)
if (emojiBtn) {
  emojiBtn.addEventListener('click', () => {
    console.log('Emoji button clicked');
    
    // Basic emoji insertion - you can enhance this with an emoji picker
    const messageInput = document.getElementById('message');
    if (messageInput) {
      const commonEmojis = ['😀', '😂', '❤️', '👍', '👋', '😊', '🔥', '✨', '💯', '🎉'];
      const selectedEmoji = commonEmojis[Math.floor(Math.random() * commonEmojis.length)];
      messageInput.value += selectedEmoji;
      messageInput.focus();
    }
  });
}

const showFilePreview = (file) => {
  const fileIconPreview = document.querySelector('.file-icon-preview i');
  const fileNamePreview = document.querySelector('.file-name-preview');
  const fileSizePreview = document.querySelector('.file-size-preview');

  if (!fileIconPreview || !fileNamePreview || !fileSizePreview) {
    console.error('File preview elements not found');
    return;
  }

  // Update file icon based on type
  const fileIcon = getFileIcon(file.type);
  fileIconPreview.className = fileIcon;

  // Update file details
  fileNamePreview.textContent = file.name;
  fileSizePreview.textContent = formatFileSize(file.size);

  // Show preview area
  if (filePreviewArea) {
    filePreviewArea.style.display = 'block';
    if (fileMessageInput) {
      fileMessageInput.focus();
    }
  }
};

const hideFilePreview = () => {
  if (filePreviewArea) {
    filePreviewArea.style.display = 'none';
  }
  if (fileInput) {
    fileInput.value = '';
  }
  if (fileMessageInput) {
    fileMessageInput.value = '';
  }
  selectedFile = null;
};

const sendFileMessage = async (file, caption) => {
  if (!currentChattingWith) {
    alert('Please select a user to send file to');
    return;
  }

  const formData = new FormData();
  formData.append('file', file);
  formData.append('receiver_id', currentChattingWith);
  if (caption) {
    formData.append('message', caption);
  }

  // Disable send button and show loading
  if (fileSendBtn) {
    fileSendBtn.disabled = true;
    fileSendBtn.innerHTML = '<div class="loading-spinner"></div> Sending...';
  }

  try {
    const response = await fetch('/chat', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
      },
      body: formData
    });

    // Check if response is actually JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const textResponse = await response.text();
      console.error('Non-JSON response:', textResponse);
      throw new Error('Server returned an error. Check file type and size.');
    }

    const data = await response.json();

    if (data.success) {
      hideFilePreview();
      console.log('File sent successfully');
    } else {
      throw new Error(data.error || 'Failed to send file');
    }
  } catch (err) {
    console.error('File send error:', err);
    alert('Failed to send file: ' + err.message);
  } finally {
    if (fileSendBtn) {
      fileSendBtn.disabled = false;
      fileSendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
    }
  }
};
});
</script>