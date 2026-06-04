<?php
require_once 'includes/admin-settings-helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo getSiteName(); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .maintenance-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            text-align: center;
        }
        
        .maintenance-icon {
            font-size: 5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .maintenance-info {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .maintenance-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .maintenance-info p {
            margin: 0;
            font-size: 0.95rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #764ba2;
        }
        
        .footer {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <h1>Maintenance Mode</h1>
        
        <p>We're currently performing maintenance to improve our system. The <?php echo getSiteName(); ?> system is temporarily unavailable.</p>
        
        <div class="maintenance-info">
            <h3><i class="fas fa-info-circle"></i> We'll be back soon!</h3>
            <p>We're working hard to get everything back online. Please check back in a few moments.</p>
        </div>
        
        <a href="admin-login.php" class="btn">
            <i class="fas fa-sign-in-alt"></i> Admin Login
        </a>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo getSiteName(); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
