<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/admin-settings-helper.php';

$error = '';

// Check for session expired error
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error = 'Your session has expired due to inactivity. Please log in again.';
}

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
        logAuditEvent('login_attempt_empty_credentials', null, null, ['username' => $username], null, null, $username);
    } else {
        try {
            $database = new Database();
            $db = $database->connect();
            
            // Query database for user
            $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Check if user is seller, manager, or other role
                if ($user['role'] === 'seller' || $user['role'] === 'manager') {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_login_time'] = time();
                    
                    // Log successful login
                    logAuditEvent('user_login_success', 'users', $user['id'], null, ['role' => $user['role']], $user['id'], $user['username']);
                    
                    // Redirect based on role
                    if ($user['role'] === 'seller') {
                        header('Location: seller-dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $error = 'Your account does not have access to this system.';
                    logAuditEvent('login_attempt_access_denied', 'users', $user['id'], null, ['role' => $user['role']], $user['id'], $user['username']);
                }
            } else {
                $error = 'Invalid username or password';
                logAuditEvent('login_attempt_failed', null, null, ['username' => $username], null, null, $username);
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
            logAuditEvent('login_error', null, null, ['error' => $e->getMessage()], null, null, $username);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1>PMS</h1>
                </div>
                <p>Professional Pharmacy Management</p>
            </div>

            <!-- Login Type Selector -->
            <div class="login-type-selector">
                <button type="button" class="login-type-btn active" data-type="manager">
                    <i class="fas fa-user-cog"></i>
                    <span>Manager Login</span>
                </button>
                <button type="button" class="login-type-btn" data-type="seller">
                    <i class="fas fa-cash-register"></i>
                    <span>Seller Login</span>
                </button>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Enter password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>

            <!-- Admin Login Access -->
            <div class="admin-access" style="text-align: center; margin: 1.5rem 0;">
                <a href="admin-login.php" class="admin-login-link">
                    <i class="fas fa-user-shield"></i>
                    Admin Login
                </a>
                <small style="display: block; margin-top: 0.5rem; color: #666;">
                    System administrators access
                </small>
            </div>

            <!-- Demo Credentials Info -->
            <div class="demo-info" id="demoInfo">
                <div class="demo-section">
                    <h4><i class="fas fa-user-cog"></i> Manager Demo</h4>
                    <p><strong>Username:</strong> admin</p>
                    <p><strong>Password:</strong> admin</p>
                    <small>Access inventory management, reports, and admin features</small>
                </div>
                
                <div class="demo-section admin-demo">
                    <h4><i class="fas fa-user-shield"></i> Admin Demo</h4>
                    <p><strong>Username:</strong> admin</p>
                    <p><strong>Password:</strong> admin123</p>
                    <small>Full system administration and user management</small>
                </div>
            </div>
            
            <div class="login-footer">
                <p><small>&copy; Created by Sanology</small></p>
            </div>
        </div>
    </div>
    
    <style>
        .login-body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
        }
        
        .login-header .logo {
            margin-bottom: 1rem;
        }
        
        .login-header .logo i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .login-header .logo h1 {
            color: var(--text-color);
            margin: 0;
            font-size: 2rem;
        }
        
        .login-header p {
            color: var(--muted-color);
            margin: 0 0 2rem 0;
        }
        
        .login-form {
            text-align: left;
        }
        
        .login-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .login-form label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .login-form label i {
            width: 16px;
            color: var(--primary-color);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .admin-login-link {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
        }
        
        .admin-login-link:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 53, 69, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .admin-login-link i {
            margin-right: 0.5rem;
        }
        
        .login-footer {
            margin-top: 2rem;
            color: var(--muted-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-danger {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .login-type-selector {
            display: flex;
            background: var(--gray-100);
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 1.5rem;
            gap: 4px;
        }

        .login-type-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .login-type-btn i {
            font-size: 1.2rem;
        }

        .login-type-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .login-type-btn:hover:not(.active) {
            background: var(--gray-200);
            color: var(--primary-color);
        }

        .demo-info {
            background: var(--gray-50);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: left;
            border: 1px solid var(--gray-200);
        }

        .demo-section h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .demo-section p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .demo-section small {
            color: var(--muted-color);
            font-style: italic;
        }

        .seller-demo {
            border-top: 1px solid var(--gray-300);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
        }

        .seller-demo h4 {
            color: #28a745;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
            }
            
            .login-header .logo h1 {
                font-size: 1.8rem;
            }
            
            .login-header .logo i {
                font-size: 2.5rem;
            }
        }
    </style>
    
    <script>
        let currentLoginType = 'manager';

        // Focus on username field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
            updateDemoInfo();
        });
        
        // Handle login type switching
        document.querySelectorAll('.login-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll('.login-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update current login type
                currentLoginType = this.dataset.type;
                updateDemoInfo();
                
                // Clear form
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
                document.getElementById('username').focus();
            });
        });

        function updateDemoInfo() {
            const demoInfo = document.getElementById('demoInfo');
            
            if (currentLoginType === 'manager') {
                demoInfo.innerHTML = `
                    <div class="demo-section">
                        <h4><i class="fas fa-user-cog"></i> Manager Demo</h4>
                        <p><strong>Username:</strong> admin</p>
                        <p><strong>Password:</strong> admin</p>
                        <small>Access inventory management, reports, and admin features</small>
                    </div>
                `;
            } else {
                demoInfo.innerHTML = `
                    <div class="demo-section">
                        <h4><i class="fas fa-cash-register"></i> Seller/Cashier Demo</h4>
                        <p><strong>Username:</strong> seller</p>
                        <p><strong>Password:</strong> seller123</p>
                        <small>Access POS system, checkout, and sales features</small>
                    </div>
                    <div class="demo-section seller-demo">
                        <h4><i class="fas fa-info-circle"></i> POS Features</h4>
                        <small>• Barcode scanning • Product search • Multiple payment methods • Receipt printing</small>
                    </div>
                `;
            }
        }

        // Handle form submission - redirect based on login type
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            if (currentLoginType === 'seller') {
                e.preventDefault();
                
                // Change form action to seller login
                const form = this;
                const formData = new FormData(form);
                
                // Create a temporary form to submit to seller-login.php
                const tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = 'seller-login.php';
                tempForm.style.display = 'none';
                
                // Add form data
                for (let [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    tempForm.appendChild(input);
                }
                
                document.body.appendChild(tempForm);
                tempForm.submit();
            }
            // For manager login, let the form submit normally
        });
        
        // Handle Enter key to submit form
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && (e.target.id === 'username' || e.target.id === 'password')) {
                document.querySelector('.login-form').dispatchEvent(new Event('submit'));
            }
        });

        // Auto-fill demo credentials on double-click
        document.getElementById('demoInfo').addEventListener('dblclick', function() {
            if (currentLoginType === 'manager') {
                document.getElementById('username').value = 'admin';
                document.getElementById('password').value = 'admin';
            } else {
                document.getElementById('username').value = 'seller';
                document.getElementById('password').value = 'seller123';
            }
            document.getElementById('password').focus();
        });
    </script>
</body>
</html>
