<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin System - Razology Pharmacy</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-portal {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .admin-portal h1 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-portal h1 i {
            margin-right: 1rem;
            color: #007bff;
        }

        .admin-portal p {
            color: #666;
            margin-bottom: 2rem;
        }

        .admin-actions {
            display: grid;
            gap: 1rem;
        }

        .admin-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .admin-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .admin-btn i {
            margin-right: 0.5rem;
        }

        .admin-btn.dashboard { background: #28a745; }
        .admin-btn.dashboard:hover { background: #1e7e34; }

        .admin-btn.logs { background: #17a2b8; }
        .admin-btn.logs:hover { background: #138496; }

        .admin-btn.settings { background: #ffc107; color: #333; }
        .admin-btn.settings:hover { background: #e0a800; }

        .admin-btn.backup { background: #6c757d; }
        .admin-btn.backup:hover { background: #5a6268; }

        .status-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #007bff;
        }

        .status-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .status-info p {
            color: #666;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="admin-portal">
        <h1><i class="fas fa-user-shield"></i> Admin System</h1>
        <p>Welcome to the Razology Pharmacy Admin Portal</p>
        
        <div class="status-info">
            <h3><i class="fas fa-check-circle"></i> System Status</h3>
            <p>Admin system is operational and ready for use.</p>
        </div>

        <div class="admin-actions">
            <a href="admin-dashboard.php" class="admin-btn dashboard">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            
            <a href="simple-admin-logs.php" class="admin-btn logs">
                <i class="fas fa-history"></i> Audit Logs
            </a>
            
            <a href="simple-admin-settings.php" class="admin-btn settings">
                <i class="fas fa-cog"></i> System Settings
            </a>
            
            <a href="backup.php" class="admin-btn backup">
                <i class="fas fa-database"></i> Database Backup
            </a>
            
            <a href="index.php" class="admin-btn">
                <i class="fas fa-home"></i> Manager Dashboard
            </a>
        </div>
    </div>
</body>
</html>