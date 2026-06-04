<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/admin-auth.php';
$adminUser = initAdminPage('User Management');
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'seller';
            
            if ($username && $password && in_array($role, ['admin', 'manager', 'seller'])) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
                    $result = $stmt->execute([$username, $hashedPassword, $role]);
                    
                    if ($result) {
                        $newUserId = $db->lastInsertId();
                        logAdminAction('user_created', [
                            'username' => $username,
                            'role' => $role,
                            'user_id' => $newUserId
                        ], 'users', $newUserId);
                        $success = "User '$username' created successfully.";
                    } else {
                        $error = "Failed to create user.";
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = "Username already exists.";
                    } else {
                        $error = "Database error: " . $e->getMessage();
                    }
                }
            } else {
                $error = "Please fill all required fields.";
            }
            break;
            
        case 'update_role':
            $userId = intval($_POST['user_id'] ?? 0);
            $newRole = $_POST['new_role'] ?? '';
            
            if ($userId && in_array($newRole, ['admin', 'manager', 'seller'])) {
                // Don't allow changing the current admin's role
                if ($userId == $_SESSION['admin_id']) {
                    $error = "Cannot change your own role.";
                } else {
                    $stmt = $db->prepare("SELECT username, role FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $oldUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($oldUser) {
                        $stmt = $db->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$newRole, $userId]);
                        
                        if ($result) {
                            logAdminAction('user_role_updated', [
                                'username' => $oldUser['username'],
                                'old_role' => $oldUser['role'],
                                'new_role' => $newRole
                            ], 'users', $userId);
                            $success = "User role updated successfully.";
                        } else {
                            $error = "Failed to update user role.";
                        }
                    } else {
                        $error = "User not found.";
                    }
                }
            } else {
                $error = "Invalid user or role.";
            }
            break;
            
        case 'toggle_lock':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId && $userId != $_SESSION['admin_id']) {
                $stmt = $db->prepare("SELECT username, account_locked FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $newLockStatus = !$user['account_locked'];
                    $stmt = $db->prepare("UPDATE users SET account_locked = ?, login_attempts = 0, updated_at = NOW() WHERE id = ?");
                    $result = $stmt->execute([$newLockStatus, $userId]);
                    
                    if ($result) {
                        logAdminAction('user_account_' . ($newLockStatus ? 'locked' : 'unlocked'), [
                            'username' => $user['username']
                        ], 'users', $userId);
                        $success = "User account " . ($newLockStatus ? 'locked' : 'unlocked') . " successfully.";
                    } else {
                        $error = "Failed to update account status.";
                    }
                } else {
                    $error = "User not found.";
                }
            } else {
                $error = "Cannot lock your own account or invalid user.";
            }
            break;
            
        case 'delete_user':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId && $userId != $_SESSION['admin_id']) {
                $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $result = $stmt->execute([$userId]);
                    
                    if ($result) {
                        logAdminAction('user_deleted', [
                            'username' => $user['username']
                        ], 'users', $userId);
                        $success = "User '{$user['username']}' deleted successfully.";
                    } else {
                        $error = "Failed to delete user.";
                    }
                } else {
                    $error = "User not found.";
                }
            } else {
                $error = "Cannot delete your own account or invalid user.";
            }
            break;
    }
}

// Get all users (only select columns that exist)
try {
    $users = $db->query("
        SELECT id, username, role, last_login, created_at, full_name, email, is_active
        FROM users 
        ORDER BY role, username
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to basic columns if some don't exist
    $users = $db->query("
        SELECT id, username, role
        FROM users 
        ORDER BY role, username
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .admin-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .user-management {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        .users-list {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .user-form {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 0.875rem;
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
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-manager {
            background: #28a745;
            color: white;
        }
        
        .role-seller {
            background: #17a2b8;
            color: white;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-locked {
            background: #f8d7da;
            color: #721c24;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
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
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="container">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="admin-dashboard.php" style="color: white; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> <?php echo t('common.back'); ?>
                    </a>
                    <h1 style="margin: 0;"><?php echo t('admin.userManagement'); ?></h1>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="nav-language-selector">
                        <select id="languageSelect" class="language-select" onchange="changeLanguage(this.value)">
                            <option value="en">English</option>
                            <option value="ckb">سۆرانی</option>
                            <option value="ar">العربية</option>
                        </select>
                    </div>
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars($adminUser['username']) ?></span>
                </div>
            </div>
        </header>
        
        <main class="admin-content">
            <div class="page-header">
                <h2><i class="fas fa-users-cog"></i> <?php echo t('admin.userManagement'); ?></h2>
                <p><?php echo t('admin.manageUserAccounts'); ?></p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="user-management">
                <div class="users-list">
                    <h3><i class="fas fa-list"></i> <?php echo t('admin.allUsers'); ?> (<?= count($users) ?>)</h3>
                    
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                            <small style="color: #ffd700;">(You)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?= $user['role'] ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['account_locked']): ?>
                                            <span class="status-badge status-locked">Locked</span>
                                        <?php else: ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['login_attempts'] > 0): ?>
                                            <small style="color: #dc3545;">(<?= $user['login_attempts'] ?> attempts)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?= date('M j, Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <em>Never</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                                <!-- Role Change -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <select name="new_role" onchange="this.form.submit()" class="btn-sm">
                                                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
                                                        <option value="seller" <?= $user['role'] == 'seller' ? 'selected' : '' ?>>Seller</option>
                                                    </select>
                                                </form>
                                                
                                                <!-- Lock/Unlock -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_lock">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn-sm <?= $user['account_locked'] ? 'btn-success' : 'btn-warning' ?>">
                                                        <i class="fas fa-<?= $user['account_locked'] ? 'unlock' : 'lock' ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Delete -->
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete user <?= htmlspecialchars($user['username']) ?>?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <em>Current User</em>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="user-form">
                    <h3><i class="fas fa-user-plus"></i> Create New User</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Enter username"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Enter password"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="seller">Seller</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create User
                        </button>
                    </form>
                    
                    <hr style="margin: 2rem 0;">
                    
                    <div style="font-size: 0.9rem; color: #6c757d;">
                        <h4>Role Permissions:</h4>
                        <ul style="margin: 0; padding-left: 1rem;">
                            <li><strong>Admin:</strong> Full system access and user management</li>
                            <li><strong>Manager:</strong> Inventory and product management</li>
                            <li><strong>Seller:</strong> POS and sales operations only</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function changeLanguage(lang) {
        const formData = new FormData();
        formData.append('lang', lang);
        fetch('api/set-language.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); })
        .catch(e => console.error('Error:', e));
    }
    document.addEventListener('DOMContentLoaded', function() {
        const ls = document.getElementById('languageSelect');
        if (ls) { ls.value = '<?php echo getCurrentLanguage(); ?>'; }
        if ('<?php echo getTextDirection(); ?>' === 'rtl') document.documentElement.dir = 'rtl';
    });
    </script>
</body>
</html>