// Notifications System JavaScript
class NotificationSystem {
    constructor() {
        this.notifications = [];
        this.notificationCount = 0;
        this.isDropdownOpen = false;
        this.refreshInterval = 300000; // 5 minutes
        this.init();
    }

    init() {
        this.createNotificationBadge();
        this.loadNotifications();
        this.startPeriodicRefresh();
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notification-dropdown')) {
                this.closeDropdown();
            }
        });
    }

    createNotificationBadge() {
        // Find the navigation element
        const nav = document.querySelector('.nav');
        if (!nav) return;

        // Create notification button
        const notificationBtn = document.createElement('div');
        notificationBtn.className = 'notification-dropdown';
        notificationBtn.innerHTML = `
            <button class="notification-btn" onclick="notificationSystem.toggleDropdown()" title="Inventory Alerts">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
            <div class="dropdown-content" id="notificationDropdown">
                <div class="dropdown-header">
                    <h4><i class="fas fa-bell"></i> Inventory Alerts</h4>
                    <div class="header-actions">
                        <button class="dismiss-all-btn" onclick="notificationSystem.dismissAllNotifications()" title="Dismiss All">
                            <i class="fas fa-times-circle"></i> Dismiss All
                        </button>
                        <button class="refresh-btn" onclick="notificationSystem.loadNotifications()" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="notifications-list" id="notificationsList">
                    <div class="loading-indicator">
                        <i class="fas fa-spinner fa-spin"></i> Loading notifications...
                    </div>
                </div>
                <div class="dropdown-footer">
                    <a href="inventory-management.php" class="view-all-btn">
                        <i class="fas fa-boxes"></i> Manage Inventory
                    </a>
                </div>
            </div>
        `;

        // Insert before logout link
        const logoutLink = nav.querySelector('.logout-link');
        if (logoutLink) {
            nav.insertBefore(notificationBtn, logoutLink);
        } else {
            nav.appendChild(notificationBtn);
        }
    }

    async loadNotifications() {
        try {
            console.log('Loading notifications from API...');
            const response = await fetch('api/notifications.php');
            console.log('API response status:', response.status);
            
            if (response.status === 401) {
                console.error('Unauthorized - user not logged in');
                this.showError('Please log in to view notifications');
                return;
            }
            
            const data = await response.json();
            console.log('API response data:', data);
            
            if (data.success) {
                this.notifications = data.notifications || [];
                this.notificationCount = data.totalCount || data.total_count || 0;
                console.log(`Loaded ${this.notificationCount} notifications`);
                this.updateBadge();
                this.updateDropdownContent();
            } else {
                console.error('Failed to load notifications:', data.error);
                this.showError('Failed to load notifications');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError('Network error loading notifications');
        }
    }

    updateBadge() {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;

        if (this.notificationCount > 0) {
            badge.textContent = this.notificationCount > 99 ? '99+' : this.notificationCount;
            badge.style.display = 'block';
            
            // Add pulsing animation for critical notifications
            const criticalCount = this.notifications.filter(n => n.severity === 'critical').length;
            if (criticalCount > 0) {
                badge.classList.add('pulse');
            } else {
                badge.classList.remove('pulse');
            }
        } else {
            badge.style.display = 'none';
            badge.classList.remove('pulse');
        }
    }

    updateDropdownContent() {
        const dropdownList = document.getElementById('notificationsList');
        if (!dropdownList) return;

        if (this.notifications.length === 0) {
            dropdownList.innerHTML = `
                <div class="no-notifications">
                    <i class="fas fa-check-circle"></i>
                    <p>All inventory items are in good condition!</p>
                </div>
            `;
            return;
        }

        // Group notifications by severity
        const critical = this.notifications.filter(n => n.severity === 'critical');
        const warning = this.notifications.filter(n => n.severity === 'warning');
        const info = this.notifications.filter(n => n.severity === 'info');

        let html = '';
        
        if (critical.length > 0) {
            html += this.renderNotificationGroup('Critical', critical, 'critical');
        }
        
        if (warning.length > 0) {
            html += this.renderNotificationGroup('Warning', warning, 'warning');
        }
        
        if (info.length > 0) {
            html += this.renderNotificationGroup('Info', info, 'info');
        }

        dropdownList.innerHTML = html;
    }

    renderNotificationGroup(title, notifications, severity) {
        const maxShow = 3; // Show max 3 notifications per group initially
        const displayNotifications = notifications.slice(0, maxShow);
        const hasMore = notifications.length > maxShow;
        
        let html = `
            <div class="notification-group ${severity}">
                <div class="group-header">
                    <span class="group-title">${title} (${notifications.length})</span>
                </div>
        `;
        
        displayNotifications.forEach(notification => {
            html += `
                <div class="notification-item ${notification.severity}">
                    <div class="notification-icon">
                        <i class="${notification.icon}" style="color: ${notification.color}"></i>
                    </div>
                    <div class="notification-content" onclick="notificationSystem.handleNotificationClick('${notification.category}', ${notification.item_id})">
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-meta">
                            <span class="notification-category">${notification.category}</span>
                            ${notification.quantity !== undefined ? `<span class="notification-quantity">Qty: ${notification.quantity}</span>` : ''}
                        </div>
                    </div>
                    <div class="notification-actions">
                        <button class="dismiss-btn" onclick="event.stopPropagation(); notificationSystem.dismissNotification('${notification.type}', '${notification.category}', ${notification.item_id})" title="Dismiss">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        if (hasMore) {
            html += `
                <div class="show-more" onclick="notificationSystem.showMoreInGroup('${severity}')">
                    <i class="fas fa-chevron-down"></i> Show ${notifications.length - maxShow} more
                </div>
            `;
        }
        
        html += '</div>';
        return html;
    }

    handleNotificationClick(category, itemId) {
        // Navigate to the appropriate detail page
        let detailPage = '';
        switch (category) {
            case 'products':
                detailPage = `product-detail.php?id=${itemId}`;
                break;
            case 'cosmetics':
                detailPage = `cosmetics-detail.php?id=${itemId}`;
                break;
            case 'dental':
                detailPage = `dental-detail.php?id=${itemId}`;
                break;
        }
        
        if (detailPage) {
            window.location.href = detailPage;
        }
    }

    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        this.isDropdownOpen = !this.isDropdownOpen;
        
        if (this.isDropdownOpen) {
            dropdown.classList.add('show');
            // Refresh notifications when opening
            this.loadNotifications();
        } else {
            dropdown.classList.remove('show');
        }
    }

    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
            this.isDropdownOpen = false;
        }
    }

    showError(message) {
        const dropdownList = document.getElementById('notificationsList');
        if (dropdownList) {
            dropdownList.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                    <button onclick="notificationSystem.loadNotifications()" class="retry-btn">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        }
    }

    startPeriodicRefresh() {
        // Refresh notifications every 5 minutes
        setInterval(() => {
            this.loadNotifications();
        }, this.refreshInterval);
    }

    showMoreInGroup(severity) {
        // This would expand the group to show all notifications
        // For now, we'll just reload to show more
        this.updateDropdownContent();
    }

    async dismissNotification(type, category, itemId) {
        try {
            const formData = new FormData();
            formData.append('action', 'dismiss');
            formData.append('type', type);
            formData.append('category', category);
            formData.append('item_id', itemId);

            const response = await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Reload notifications
                this.loadNotifications();
            } else {
                this.showError('Failed to dismiss notification: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error dismissing notification:', error);
            this.showError('Network error occurred while dismissing notification');
        }
    }

    async dismissAllNotifications() {
        if (!confirm('Are you sure you want to dismiss all current notifications?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'dismiss_all');

            const response = await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Reload notifications
                this.loadNotifications();
            } else {
                this.showError('Failed to dismiss all notifications: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error dismissing all notifications:', error);
            this.showError('Network error occurred while dismissing notifications');
        }
    }
}

// Initialize notification system when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing notification system...');
    
    // Wait a bit for page to fully load
    setTimeout(() => {
        const nav = document.querySelector('.nav');
        console.log('Nav element found:', nav ? 'YES' : 'NO');
        
        if (nav) {
            window.notificationSystem = new NotificationSystem();
            console.log('Notification system initialized successfully');
        } else {
            console.error('Could not find .nav element for notification system');
        }
    }, 100);
});

// Expose for global access
window.NotificationSystem = NotificationSystem;