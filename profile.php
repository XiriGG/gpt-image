<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

$userid = $_SESSION['userid'];
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute(array($userid));
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_profile'])) {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        
        if (!$username) {
            $msg = '请输入用户名';
            $msgType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE user SET username = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute(array($username, $email, $phone, $userid));
            
            $msg = '资料更新成功';
            $msgType = 'success';
            
            $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
            $stmt->execute(array($userid));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } elseif (isset($_POST['upload_avatar'])) {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowedTypes = array('image/jpeg', 'image/png', 'image/gif');
            
            if (!in_array($file['type'], $allowedTypes)) {
                $msg = '请上传有效的图片文件';
                $msgType = 'error';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $msg = '图片大小不能超过2MB';
                $msgType = 'error';
            } else {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'avatar_' . $userid . '.' . $ext;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $stmt = $pdo->prepare("UPDATE user SET avatar = ? WHERE id = ?");
                    $stmt->execute(array($uploadPath, $userid));
                    
                    $msg = '头像上传成功，请点击保存按钮应用';
                    $msgType = 'success';
                    
                    $_SESSION['avatar_temp'] = $uploadPath;
                } else {
                    $msg = '头像上传失败';
                    $msgType = 'error';
                }
            }
        } else {
            $msg = '请选择要上传的图片';
            $msgType = 'error';
        }
    } elseif (isset($_POST['save_avatar'])) {
        if (isset($_SESSION['avatar_temp'])) {
            $stmt = $pdo->prepare("UPDATE user SET avatar = ? WHERE id = ?");
            $stmt->execute(array($_SESSION['avatar_temp'], $userid));
            
            $user['avatar'] = $_SESSION['avatar_temp'];
            $_SESSION['avatar'] = $_SESSION['avatar_temp'];
            unset($_SESSION['avatar_temp']);
            
            $msg = '头像保存成功';
            $msgType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料 - 晴玖AI创作系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", sans-serif; background: #f5f5f5; min-height: 100vh; }
        
        .sidebar { width: 200px; background: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; left: 0; top: 0; bottom: 0; padding: 20px 0; }
        .sidebar-logo { text-align: center; margin-bottom: 32px; padding: 0 20px; }
        .sidebar-logo h1 { font-size: 18px; color: #333; margin-bottom: 4px; font-weight: 600; }
        .sidebar-logo p { font-size: 12px; color: #999; }
        .sidebar-nav { list-style: none; padding: 0 12px; }
        .sidebar-nav li { margin-bottom: 4px; }
        .sidebar-nav a { display: flex; align-items: center; padding: 12px 16px; border-radius: 8px; color: #666; text-decoration: none; transition: all 0.2s; font-size: 14px; }
        .sidebar-nav a:hover { background: #fff5f7; color: #ff6b81; }
        .sidebar-nav a.active { background: #ffeef0; color: #ff6b81; font-weight: 500; }
        .sidebar-nav a i { margin-right: 12px; font-size: 16px; }
        
        .main { margin-left: 200px; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header-left h2 { font-size: 24px; color: #333; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .user-info { display: flex; align-items: center; gap: 12px; padding: 8px 16px; background: #fff; border-radius: 20px; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #ff6b81, #ff8fa3); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        .user-name { font-size: 14px; color: #333; }
        .user-points { font-size: 14px; color: #ff6b81; font-weight: 600; }
        .logout-btn { padding: 8px 16px; background: #fff; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; color: #666; cursor: pointer; transition: all 0.2s; }
        .logout-btn:hover { background: #f5f5f5; }
        
        .profile-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
        
        .avatar-section { text-align: center; margin-bottom: 32px; }
        .avatar-container { position: relative; display: inline-block; }
        .avatar { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #ff6b81, #ff8fa3); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 48px; font-weight: bold; object-fit: cover; }
        .avatar-upload-btn { position: absolute; bottom: 0; right: 0; width: 36px; height: 36px; border-radius: 50%; background: #ff6b81; color: #fff; border: none; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; }
        .avatar-upload-btn:hover { background: #ff8fa3; }
        .avatar-input { display: none; }
        .avatar-hint { font-size: 12px; color: #999; margin-top: 8px; }
        .temp-badge { display: inline-block; padding: 4px 12px; background: #ffeef0; color: #ff6b81; border-radius: 20px; font-size: 12px; margin-top: 8px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 8px; display: block; }
        .form-input { width: 100%; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-input:focus { border-color: #ff6b81; }
        
        .btn-group { display: flex; gap: 12px; margin-top: 24px; }
        .btn-submit { flex: 1; padding: 14px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: transform 0.15s; }
        .btn-submit:hover { transform: translateY(-1px); }
        .btn-secondary { flex: 1; padding: 14px; background: #f5f5f5; color: #666; border: none; border-radius: 10px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { background: #eee; }
        .btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .toast { position: fixed; bottom: 32px; right: 32px; padding: 12px 24px; border-radius: 8px; color: #fff; font-size: 14px; z-index: 2000; animation: slideIn 0.3s ease; }
        .toast.success { background: #52c41a; }
        .toast.error { background: #e53e3e; }
        @keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        .link-group { margin-top: 24px; padding-top: 24px; border-top: 1px solid #f0f0f0; }
        .link-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; color: #666; text-decoration: none; transition: color 0.2s; }
        .link-item:hover { color: #ff6b81; }
        .link-item i { font-size: 16px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <h1>晴玖AI创作系统</h1>
            <p>专业AI图片生成</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i>📊</i>仪表盘</a></li>
            <li><a href="index.php"><i>🎨</i>AI生图</a></li>
            <li><a href="gallery.php"><i>🖼️</i>观赏大厅</a></li>
            <li><a href="works.php"><i>📁</i>我的作品</a></li>
            <li><a href="shop.php"><i>🛒</i>积分商城</a></li>
            <li><a href="card_exchange.php"><i>🎫</i>卡密兑换</a></li>
            <li><a href="recharge.php"><i>💰</i>余额充值</a></li>
            <li><a href="orders.php"><i>📋</i>订单记录</a></li>
            <li><a href="account_detail.php"><i>📊</i>资产明细</a></li>
            <li><a href="api_config.php"><i>⚙️</i>接口配置</a></li>
            <li><a href="pay_config.php"><i>💳</i>支付配置</a></li>
            <li><a href="profile.php" class="active"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php"><i>🎫</i>售后工单</a></li>
        </ul>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>个人资料</h2></div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo mb_substr(isset($user['username']) ? $user['username'] : 'U', 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?php echo isset($user['username']) ? $user['username'] : ''; ?></div>
                    <div class="user-points"><?php echo isset($user['points']) ? $user['points'] : 0; ?> 积分</div>
                </div>
                <a href="logout.php" class="logout-btn">退出登录</a>
            </div>
        </div>
        
        <div class="profile-card">
            <div class="avatar-section">
                <div class="avatar-container">
                    <?php if ($_SESSION['avatar_temp']): ?>
                        <img class="avatar" src="<?php echo htmlspecialchars($_SESSION['avatar_temp']); ?>" alt="头像">
                        <span class="temp-badge">待保存</span>
                    <?php elseif ($user['avatar']): ?>
                        <img class="avatar" src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像">
                    <?php else: ?>
                        <div class="avatar"><?php echo mb_substr(isset($user['username']) ? $user['username'] : 'U', 0, 1); ?></div>
                    <?php endif; ?>
                    <label class="avatar-upload-btn" for="avatarInput">📷</label>
                    <input type="file" id="avatarInput" class="avatar-input" accept="image/*" name="avatar">
                </div>
                <div class="avatar-hint">支持 JPG/PNG/GIF · 最大2MB</div>
                
                <form method="post" enctype="multipart/form-data" style="display: none;" id="avatarForm">
                    <input type="file" name="avatar" id="hiddenAvatarInput">
                    <input type="hidden" name="upload_avatar" value="1">
                </form>
                
                <?php if ($_SESSION['avatar_temp']): ?>
                    <form method="post" style="margin-top: 12px;">
                        <input type="hidden" name="save_avatar" value="1">
                        <button type="submit" class="btn-submit" style="width: auto; padding: 8px 20px;">保存头像</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <form method="post">
                <input type="hidden" name="save_profile" value="1">
                
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-input" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="请输入用户名">
                </div>
                
                <div class="form-group">
                    <label class="form-label">邮箱</label>
                    <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="请输入邮箱">
                </div>
                
                <div class="form-group">
                    <label class="form-label">手机号</label>
                    <input type="tel" class="form-input" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="请输入手机号">
                </div>
                
                <div class="form-group">
                    <label class="form-label">用户ID</label>
                    <input type="text" class="form-input" value="<?php echo $user['id'] ?? ''; ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">注册时间</label>
                    <input type="text" class="form-input" value="<?php echo $user['created_at'] ?? ''; ?>" disabled>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn-submit">保存资料</button>
                    <a href="change_password.php" class="btn-secondary">修改密码</a>
                </div>
            </form>
            
            <div class="link-group">
                <a href="api_config.php" class="link-item">
                    <span><i>⚙️</i> 接口配置</span>
                    <span>→</span>
                </a>
                <a href="pay_config.php" class="link-item">
                    <span><i>💳</i> 支付配置</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const form = document.getElementById('avatarForm');
            const hiddenInput = document.getElementById('hiddenAvatarInput');
            hiddenInput.files = e.target.files;
            form.submit();
        });
        
        <?php if ($msg): ?>
            showToast('<?php echo $msg; ?>', '<?php echo $msgType; ?>');
        <?php endif; ?>
        
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3000);
        }
    </script>
</body>
</html>