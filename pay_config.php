<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

$userid = $_SESSION['userid'];
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['points'] = $user['points'] ?? 1;

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payUrl = $_POST['pay_url'] ?? '';
    $payId = $_POST['pay_id'] ?? '';
    $payKey = $_POST['pay_key'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM system_config WHERE config_key = 'pay'");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'pay'");
            $configValue = json_encode(array('url' => $payUrl, 'api_key' => $payId, 'secret' => $payKey));
            $stmt->execute(array($configValue));
        } else {
            $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES ('pay', ?)");
            $configValue = json_encode(array('url' => $payUrl, 'api_key' => $payId, 'secret' => $payKey));
            $stmt->execute(array($configValue));
        }
        
        $msg = '支付配置保存成功';
        $msgType = 'success';
    } catch (Exception $e) {
        $msg = '保存失败: ' . $e->getMessage();
        $msgType = 'error';
    }
}

$payConfig = array();
$stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'pay'");
$stmt->execute();
$result = $stmt->fetch();
if ($result) {
    $decoded = json_decode($result['config_value'], true);
    $payConfig = $decoded !== null ? $decoded : array();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>晴玖AI创作系统 - 支付配置</title>
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
        
        .config-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); max-width: 600px; }
        .card-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 24px; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: #f6ffed; color: #52c41a; }
        .alert.error { background: #fff5f5; color: #e53e3e; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-input:focus { border-color: #ff6b81; }
        
        .form-hint { font-size: 12px; color: #999; margin-top: 4px; }
        
        .btn-submit { padding: 14px 32px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s; margin-top: 8px; }
        .btn-submit:hover { transform: translateY(-1px); }
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
            <li><a href="pay_config.php" class="active"><i>💳</i>支付配置</a></li>
            <li><a href="profile.php"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php"><i>🎫</i>售后工单</a></li>
        </ul>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>支付配置</h2></div>
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
                    <div class="user-points"><?php echo $_SESSION['points']; ?> 积分</div>
                </div>
                <a href="logout.php" class="logout-btn">退出登录</a>
            </div>
        </div>
        
        <div class="config-card">
            <div class="card-title">易支付配置</div>
            
            <?php if ($msg): ?>
                <div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>
            
            <form method="post" action="pay_config.php">
                <div class="form-group">
                    <label class="form-label">支付接口地址</label>
                    <input type="text" class="form-input" name="pay_url" value="<?php echo htmlspecialchars($payConfig['url'] ?? ''); ?>" placeholder="例如: https://pay.yunhuni.com/api/payment">
                    <div class="form-hint">易支付接口的完整URL地址</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">商户ID (PID)</label>
                    <input type="text" class="form-input" name="pay_id" value="<?php echo htmlspecialchars($payConfig['api_key'] ?? ''); ?>" placeholder="您的易支付商户ID">
                    <div class="form-hint">在易支付平台获取的商户ID</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">商户密钥 (KEY)</label>
                    <input type="password" class="form-input" name="pay_key" value="<?php echo htmlspecialchars($payConfig['secret'] ?? ''); ?>" placeholder="您的易支付密钥">
                    <div class="form-hint">用于签名验证的密钥，请妥善保管</div>
                </div>
                
                <button type="submit" class="btn-submit">保存配置</button>
            </form>
        </div>
    </div>
</body>
</html>