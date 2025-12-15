<?php
require_once 'config/session.php';
require_once 'controllers/AuthController.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$authController = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->login();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MMS Project Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo dan Branding -->
            <div class="login-header">
                <?php 
                $logoPath = __DIR__ . '/assets/images/logo-mms.png';
                if (file_exists($logoPath)): 
                ?>
                    <img src="assets/images/logo-mms.png" alt="Logo MMS" class="login-logo">
                <?php else: ?>
                    <div class="login-logo-placeholder">MMS</div>
                <?php endif; ?>
                <h1 class="login-app-name">Aplikasi Manajemen Proyek</h1>
                <p class="login-company-name">PT. Multidaya Mitra Sinergi</p>
            </div>
            
            <h2>Login</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem;">
                Belum punya akun? <a href="register.php" style="color: var(--primary-color);">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>

