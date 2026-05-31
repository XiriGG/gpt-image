<?php
session_start();
require_once 'config.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $msg = '请填写所有字段';
    } elseif ($password != $confirmPassword) {
        $msg = '两次输入的密码不一致';
    } elseif (strlen($password) < 6) {
        $msg = '密码长度至少为6位';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
            $stmt->execute(array($username, $email));
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                $msg = '用户名或邮箱已被注册';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO user (username, email, password, points, balance, created_at) VALUES (?, ?, ?, 10, 0, NOW())");
                $stmt->execute(array($username, $email, $hashedPassword));
                
                $userId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO account_logs (user_id, type, change_type, amount, balance, remark) VALUES (?, 'points', 'income', 10, 10, '注册赠送')");
                $stmt->execute(array($userId));
                
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            $msg = '注册失败，请稍后再试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>晴玖AI创作系统 - 注册</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", sans-serif; background: linear-gradient(135deg, #fff5f7 0%, #ffe4e8 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .register-box { background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(255, 107, 129, 0.15); padding: 48px; width: 100%; max-width: 420px; }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-icon { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; }
        .logo-title { font-size: 24px; font-weight: 700; color: #333; }
        .logo-subtitle { font-size: 14px; color: #999; margin-top: 4px; }
        .alert { background: #fff5f5; color: #e53e3e; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 14px 16px; border: 2px solid #f0f0f0; border-radius: 12px; font-size: 15px; transition: border-color 0.2s; outline: none; }
        .form-input:focus { border-color: #ff6b81; }
        .btn-register { width: 100%; padding: 14px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; }
        .btn-register:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255, 107, 129, 0.3); }
        .register-footer { text-align: center; margin-top: 24px; font-size: 14px; color: #999; }
        .register-footer a { color: #ff6b81; text-decoration: none; }
        .register-footer a:hover { text-decoration: underline; }
        .privacy-note { font-size: 12px; color: #999; text-align: center; margin-top: 16px; }
        .privacy-note a { color: #ff6b81; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="logo">
            <div class="logo-icon">&#9733;</div>
            <div class="logo-title">晴玖AI创作系统</div>
            <div class="logo-subtitle">专业AI图片生成平台</div>
        </div>
        
        <?php if ($msg): ?>
        <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        
        <form method="post" action="register.php">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" class="form-input" name="username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label class="form-label">邮箱</label>
                <input type="email" class="form-input" name="email" placeholder="请输入邮箱" required>
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" class="form-input" name="password" placeholder="请输入密码（至少6位）" required>
            </div>
            <div class="form-group">
                <label class="form-label">确认密码</label>
                <input type="password" class="form-input" name="confirm_password" placeholder="请再次输入密码" required>
            </div>
            <button type="submit" class="btn-register">注册</button>
        </form>
        
        <div class="register-footer">
            已有账号？<a href="login.php">立即登录</a>
        </div>
        
        <div class="privacy-note">
            注册即表示同意我们的 <a href="#">服务条款</a> 和 <a href="#">隐私政策</a>
        </div>
    </div>
</body>
</html>