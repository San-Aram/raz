<?php
session_start();

// If already logged in, redirect based on role
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['user_role'] === 'seller') {
        header('Location: seller-dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/database.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $database = new Database();
        $db = $database->connect();
        $userModel = new User($db);
        
        $user = $userModel->authenticate($username, $password);
        
        if ($user && $user['role'] === 'seller') {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
            
            header('Location: seller-dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials or you do not have seller access.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login - Razology POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-cash-register"></i>
                    <h1>Razology POS</h1>
                </div>
                <p class="login-subtitle">Seller Point of Sale System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login to POS
                </button>
            </form>

            <div class="login-footer">
                <div class="demo-accounts">
                    <h4><i class="fas fa-info-circle"></i> Demo Account</h4>
                    <p><strong>Seller:</strong> seller / seller123</p>
                </div>
                
                <div class="login-links">
                    <a href="login.php" class="link-secondary">
                        <i class="fas fa-cog"></i> Manager Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }

        .login-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .login-header .logo i {
            font-size: 2rem;
        }

        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .login-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #a00;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-footer {
            background: var(--gray-50);
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--gray-200);
        }

        .demo-accounts {
            margin-bottom: 1rem;
        }

        .demo-accounts h4 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-accounts p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--gray-600);
            font-family: 'Courier New', monospace;
            background: var(--white);
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--gray-300);
        }

        .login-links {
            text-align: center;
        }

        .link-secondary {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }

        .link-secondary:hover {
            color: var(--primary-color);
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 1.5rem;
            }

            .login-form {
                padding: 1.5rem;
            }

            .login-footer {
                padding: 1rem 1.5rem;
            }
        }
    </style>

    <script>
        // Auto-focus on username field
        document.getElementById('username').focus();
        
        // Add loading state to login button
        document.querySelector('.login-form').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            btn.disabled = true;
        });

        // Demo account quick fill
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                document.getElementById('username').value = 'seller';
                document.getElementById('password').value = 'seller123';
            }
        });
    </script>
</body>
</html>