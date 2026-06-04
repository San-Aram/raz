<?php
// Admin Login Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Razology</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .admin-login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .admin-login-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .admin-login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .admin-login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .admin-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .admin-login-form {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
        }
        
        .input-icon .form-control {
            padding-left: 3rem;
        }
        
        .admin-login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .admin-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .admin-login-btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .back-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .back-links a {
            color: #6c757d;
            text-decoration: none;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }
        
        .back-links a:hover {
            color: #667eea;
        }
        
        .security-notice {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <div class="admin-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Administrator Access</h1>
                <p style="margin: 0; opacity: 0.9;">Management System</p>
            </div>
            
            <div class="admin-login-form">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php 
                        switch ($_GET['error']) {
                            case 'invalid_credentials':
                                echo 'Invalid username or password';
                                break;
                            case 'account_locked':
                                echo 'Account is locked. Please try again later.';
                                break;
                            case 'access_denied':
                                echo 'Access denied. Admin privileges required.';
                                break;
                            case 'session_expired':
                                echo 'Your session has expired. Please login again.';
                                break;
                            default:
                                echo 'Authentication error occurred.';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo 'Logged out successfully'; ?>
                    </div>
                <?php endif; ?>
                
                        <form action="basic-admin-auth.php" method="post" class="login-form">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Administrator Username
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Enter admin username"
                                   required
                                   autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Administrator Password
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Enter admin password"
                                   required
                                   autocomplete="current-password">
                        </div>
                    </div>
                    
                    <button type="submit" class="admin-login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="btn-text">Access Admin Panel</span>
                        <i class="fas fa-spinner loading"></i>
                    </button>
                </form>
                
                <div class="debug-links" style="text-align: center; margin-top: 1rem;">
                    <p style="font-size: 0.9em; color: #666;">
                        <a href="basic_admin_setup.php" style="color: #007bff; text-decoration: none;">
                            <i class="fas fa-user-plus"></i> Create Admin User
                        </a> | 
                        <a href="#" onclick="debugLogin(); return false;" style="color: #28a745; text-decoration: none;">
                            <i class="fas fa-bug"></i> Debug
                        </a>
                    </p>
                </div>
                
                <div class="security-notice">
                    <i class="fas fa-shield-alt"></i>
                    Keep your admin credentials secure and never share them.
                </div>
                
                <div class="back-links">
                    <a href="index.php">
                        <i class="fas fa-home"></i> Manager Dashboard
                    </a>
                    <a href="seller-login.php">
                        <i class="fas fa-cash-register"></i> Seller Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.admin-login-btn');
            const btnText = button.querySelector('.btn-text');
            const loading = button.querySelector('.loading');
            
            btnText.style.display = 'none';
            loading.classList.add('show');
            button.disabled = true;
        });
        
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Enhanced security - clear form on back navigation
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('adminLoginForm').reset();
            }
        });
        
        // Debug login function
        function debugLogin() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                alert('Please enter both username and password first');
                return;
            }
            
            const debugUrl = `basic-admin-auth.php?debug=1&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;
            window.open(debugUrl, '_blank');
        }
    </script>
</body>
</html>