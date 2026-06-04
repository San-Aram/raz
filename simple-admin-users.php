<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();
require_once 'includes/database.php';
require_once 'includes/admin-settings-helper.php';

$adminUser = [
    'id' => getAdminId(),
    'username' => getAdminUsername()
];

$database = new Database();
$db = $database->connect();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $userId = $_POST['user_id'] ?? '';
        
        switch ($action) {
            case 'create_user':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $role = $_POST['role'] ?? 'seller';
                
                if (!$username) {
                    $error = "Username is required!";
                } elseif (!$password) {
                    $error = "Password is required!";
                } elseif (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters!";
                } elseif ($password !== $confirmPassword) {
                    $error = "Passwords do not match!";
                } elseif (!in_array($role, ['admin', 'manager', 'seller'])) {
                    $error = "Invalid role selected!";
                } else {
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = "Username already exists!";
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
                        if ($stmt->execute([$username, $hashedPassword, $role])) {
                            $message = "User '$username' created successfully with role '" . ucfirst($role) . "'!";
                            $newUserId = $db->lastInsertId();
                            // Log user creation
                            logAuditEvent('user_created', 'users', $newUserId, null, ['username' => $username, 'role' => $role], $adminUser['id'], $adminUser['username']);
                            $_POST = [];
                        } else {
                            $error = "Failed to create user!";
                        }
                    }
                }
                break;
                
            case 'delete_user':
                if ($userId && $userId != $adminUser['id']) {
                    // Get user info before deletion for audit log
                    $stmt = $db->prepare("SELECT username, role FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $deletedUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$userId])) {
                        $message = "User deleted successfully!";
                        // Log user deletion
                        logAuditEvent('user_deleted', 'users', $userId, $deletedUser, null, $adminUser['id'], $adminUser['username']);
                    } else {
                        $error = "Failed to delete user!";
                    }
                }
                break;
                
            case 'change_role':
                $newRole = $_POST['new_role'] ?? '';
                if ($userId && $userId != $adminUser['id'] && in_array($newRole, ['admin', 'manager', 'seller'])) {
                    // Get old role for audit log
                    $stmt = $db->prepare("SELECT username, role FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                    if ($stmt->execute([$newRole, $userId])) {
                        $message = "User role updated successfully!";
                        // Log role change
                        logAuditEvent('user_role_changed', 'users', $userId, ['role' => $userInfo['role']], ['role' => $newRole], $adminUser['id'], $adminUser['username']);
                    } else {
                        $error = "Failed to update user role!";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

try {
    $users = $db->query("SELECT id, username, role FROM users ORDER BY role, username")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
    $error = "Could not load users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem 0;
            overflow-y: auto;
        }

        .admin-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
            padding: 0 2rem;
        }

        .admin-header h2 {
            font-size: 1.5rem;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin-bottom: 0.5rem;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .admin-nav i {
            margin-right: 1rem;
            width: 20px;
        }

        .admin-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .admin-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .page-header h1 {
            color: #333;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
        }

        .role-admin {
            background: #dc3545;
        }

        .role-manager {
            background: #007bff;
        }

        .role-seller {
            background: #28a745;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .admin-user {
            color: rgba(255, 255, 255, 0.9);
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid #007bff;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-submit:hover {
            background: #0056b3;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        @media (max-width: 1200px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }

        .admin-language {
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        .admin-language label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .language-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .language-select option {
            background: #1e3c72;
            color: white;
        }

        .language-select:hover {
            background: rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <h2><i class="fas fa-user-shield"></i> <?= t('admin.admin') ?></h2>
            </div>
            
            <nav>
                <ul class="admin-nav">
                    <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> <?= t('nav.home') ?></a></li>
                    <li><a href="simple-admin-users.php" class="active"><i class="fas fa-users"></i> <?= t('admin.userManagement') ?></a></li>
                    <li><a href="simple-admin-settings.php"><i class="fas fa-cog"></i> <?= t('common.settings') ?></a></li>
                    <li><a href="simple-admin-logs.php"><i class="fas fa-history"></i> <?= t('admin.auditLogs') ?></a></li>
                    <li><a href="backup.php"><i class="fas fa-database"></i> <?= t('common.backup') ?></a></li>
                </ul>
            </nav>

            <div class="admin-language">
                <label>Language / زمان</label>
                <select class="language-select" id="languageSelect" onchange="changeLanguage(this.value)">
                    <option value="en">English</option>
                    <option value="ckb">سۆرانی (Kurdish)</option>
                    <option value="ar">العربية (Arabic)</option>
                </select>
            </div>
            
            <div class="admin-user">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($adminUser['username']) ?>
                <a href="includes/simple-admin-auth.php?logout=1" style="color: #ffd700; margin-left: 1rem;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-content">
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> <?= t('admin.userManagement') ?></h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="two-column">
                    <div>
                        <h2><i class="fas fa-list"></i> <?= t('admin.allUsers') ?></h2>
                        <?php if (empty($users)): ?>
                            <p style="padding: 2rem; text-align: center; color: #999;"><?= t('messages.noUsersFound') ?></p>
                        <?php else: ?>
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th><?= t('common.id') ?></th>
                                        <th><?= t('common.username') ?></th>
                                        <th><?= t('admin.role') ?></th>
                                        <th><?= t('common.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td>
                                                <span class="role-badge role-<?= $user['role'] ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['id'] != $adminUser['id']): ?>
                                                    <form style="display: inline;" method="post">
                                                        <input type="hidden" name="action" value="change_role">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <select name="new_role" onchange="this.form.submit()">
                                                            <option value=""><?= t('admin.selectRole') ?></option>
                                                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>><?= t('common.admin') ?></option>
                                                            <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>><?= t('common.manager') ?></option>
                                                            <option value="seller" <?= $user['role'] == 'seller' ? 'selected' : '' ?>><?= t('common.seller') ?></option>
                                                        </select>
                                                    </form>
                                                    
                                                    <form style="display: inline;" method="post" onsubmit="return confirm('<?= t('messages.deleteUserConfirm') ?>')">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #999; font-style: italic;"><?= t('messages.currentAdmin') ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-user-plus"></i> <?= t('admin.createUser') ?></h3>
                        <form method="post">
                            <input type="hidden" name="action" value="create_user">
                            
                            <div class="form-group">
                                <label for="username"><?= t('common.username') ?></label>
                                <input type="text" id="username" name="username" required placeholder="<?= t('common.username') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password"><?= t('admin.password') ?></label>
                                <input type="password" id="password" name="password" required placeholder="Min 6 chars">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password"><?= t('admin.confirmPassword') ?></label>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="<?= t('admin.confirmPassword') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="role"><?= t('admin.role') ?></label>
                                <select id="role" name="role" required>
                                    <option value="seller"><?= t('common.seller') ?></option>
                                    <option value="manager"><?= t('common.manager') ?></option>
                                    <option value="admin"><?= t('common.admin') ?></option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-plus"></i> <?= t('admin.createUserBtn') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function changeLanguage(lang) {
        const formData = new FormData();
        formData.append('lang', lang);
        
        fetch('api/set-language.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const langSelect = document.getElementById('languageSelect');
        if (langSelect) {
            langSelect.value = '<?php echo getCurrentLanguage(); ?>';
        }
    });
    </script>
</body>
</html>
