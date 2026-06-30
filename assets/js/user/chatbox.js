// Chatbox functionality
class Chatbox {
    constructor() {
        this.overlay = document.getElementById('chatboxOverlay');
        this.messagesContainer = document.getElementById('chatboxMessages');
        this.messageInput = document.getElementById('messageInput');
        this.sendBtn = document.getElementById('sendBtn');
        this.inboxBtn = document.getElementById('inboxBtn');
        this.chatboxClose = document.getElementById('chatboxClose');
        this.unreadCount = document.getElementById('unreadCount');
        
        this.isOpen = false;
        this.lastMessageTime = null;
        
        this.init();
    }
    
    init() {
        // Event listeners
        this.inboxBtn.addEventListener('click', () => this.toggleChatbox());
        this.chatboxClose.addEventListener('click', () => this.closeChatbox());
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) this.closeChatbox();
        });
        
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
        
        // Load initial unread count
        this.loadUnreadCount();
        
        // Periodic updates every 30 seconds
        setInterval(() => {
            if (this.isOpen) {
                this.loadMessages();
            }
            this.loadUnreadCount();
        }, 30000);
    }
    
    toggleChatbox() {
        if (this.isOpen) {
            this.closeChatbox();
        } else {
            this.openChatbox();
        }
    }
    
    openChatbox() {
        this.isOpen = true;
        this.overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        this.loadMessages();
        this.messageInput.focus();
    }
    
    closeChatbox() {
        this.isOpen = false;
        this.overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    async loadMessages() {
        try {
            const response = await fetch('../../../services/messages/getMessages.php');
            const data = await response.json();
            
            if (data.success) {
                this.displayMessages(data.messages);
            } else {
                this.showError('Failed to load messages');
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            this.showError('Connection error');
        }
    }
    
    async loadUnreadCount() {
        try {
            const response = await fetch('../../../services/messages/getUnreadCount.php');
            const data = await response.json();
            
            if (data.success) {
                if (data.count > 0) {
                    this.unreadCount.textContent = data.count;
                    this.unreadCount.style.display = 'inline';
                } else {
                    this.unreadCount.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error loading unread count:', error);
        }
    }
    
    displayMessages(messages) {
        if (!messages || messages.length === 0) {
            this.messagesContainer.innerHTML = `
                <div class="no-messages">
                    <i class="bi bi-chat-text"></i>
                    <p>No messages yet. Start a conversation!</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        messages.forEach(msg => {
            const isUser = msg.sender === 'USER';
            const timeAgo = this.formatTimeAgo(msg.created_at);
            
            html += `
                <div class="message ${isUser ? 'message-user' : 'message-admin'}">
                    <div class="message-content">${this.escapeHtml(msg.message)}</div>
                    <div class="message-time">${timeAgo}</div>
                </div>
            `;
        });
        
        this.messagesContainer.innerHTML = html;
        this.scrollToBottom();
    }
    
    async sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;
        
        this.sendBtn.disabled = true;
        this.sendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        try {
            const response = await fetch('../../../services/messages/sendMessage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.messageInput.value = '';
                this.loadMessages();
            } else {
                this.showError(data.message || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showError('Connection error');
        } finally {
            this.sendBtn.disabled = false;
            this.sendBtn.innerHTML = '<i class="bi bi-send"></i>';
        }
    }
    
    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    formatTimeAgo(datetime) {
        const now = new Date();
        const msgTime = new Date(datetime);
        const diffMs = now - msgTime;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays}d ago`;
        return msgTime.toLocaleDateString();
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showError(message) {
        this.messagesContainer.innerHTML = `
            <div class="error-message">
                <i class="bi bi-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }
}

// Initialize chatbox when page loads
document.addEventListener('DOMContentLoaded', function() {
    new Chatbox();
});