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
$userPoints = $user['points'] ?? 0;

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardKey = $_POST['card_key'] ?? '';
    
    if (empty($cardKey)) {
        $msg = '请输入卡密';
        $msgType = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM card_keys WHERE card_key = ? AND used = 0");
        $stmt->execute([$cardKey]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$card) {
            $msg = '卡密不存在或已被使用';
            $msgType = 'error';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE card_keys SET used = 1, user_id = ?, used_at = NOW() WHERE id = ?");
                $stmt->execute([$userid, $card['id']]);
                
                if ($card['card_type'] === 'points') {
                    $stmt = $pdo->prepare("UPDATE user SET points = points + ? WHERE id = ?");
                    $stmt->execute([$card['value'], $userid]);
                    $msg = '兑换成功！已获得 ' . $card['value'] . ' 积分';
                } else if ($card['card_type'] === 'member') {
                    $stmt = $pdo->prepare("UPDATE user SET member_level = 1, member_expire = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?");
                    $stmt->execute([$card['days'], $userid]);
                    $msg = '兑换成功！已获得 ' . $card['days'] . ' 天会员';
                }
                
                $pdo->commit();
                $msgType = 'success';
                $userPoints += $card['value'] ?? 0;
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = '兑换失败: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卡密兑换 - 晴玖AI创作系统</title>
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
        
        .exchange-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); max-width: 500px; margin: 0 auto; }
        .card-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 24px; text-align: center; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert.success { background: #f6ffed; color: #52c41a; }
        .alert.error { background: #fff5f5; color: #e53e3e; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 14px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 16px; outline: none; transition: border-color 0.2s; }
        .form-input:focus { border-color: #ff6b81; }
        
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s; }
        .btn-submit:hover { transform: translateY(-1px); }
        
        .tips { margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 10px; font-size: 13px; color: #666; }
        .tips-title { font-weight: 600; margin-bottom: 8px; }
        .tips ul { margin: 0; padding-left: 20px; }
        .tips li { margin-bottom: 4px; }
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
            <li><a href="card_exchange.php" class="active"><i>🎫</i>卡密兑换</a></li>
            <li><a href="recharge.php"><i>💰</i>余额充值</a></li>
            <li><a href="orders.php"><i>📋</i>订单记录</a></li>
            <li><a href="account_detail.php"><i>📊</i>资产明细</a></li>
            <li><a href="api_config.php"><i>⚙️</i>接口配置</a></li>
            <li><a href="pay_config.php"><i>💳</i>支付配置</a></li>
            <li><a href="profile.php"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php"><i>🎫</i>售后工单</a></li>
        </ul>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>卡密兑换</h2></div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo mb_substr($user['username'] ?? 'U', 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?php echo $user['username'] ?? ''; ?></div>
                    <div class="user-points"><?php echo $_SESSION['points']; ?> 积分</div>
                </div>
                <a href="logout.php" class="logout-btn">退出登录</a>
            </div>
        </div>
        
        <div class="exchange-card">
            <div class="card-title">🎫 卡密兑换</div>
            
            <?php if ($msg): ?>
                <div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>
            
            <form method="post" action="card_exchange.php">
                <div class="form-group">
                    <label class="form-label">卡密</label>
                    <input type="text" class="form-input" name="card_key" placeholder="请输入卡密" required>
                </div>
                
                <button type="submit" class="btn-submit">立即兑换</button>
            </form>
            
            <div class="tips">
                <div class="tips-title">💡 温馨提示</div>
                <ul>
                    <li>卡密可以通过购买获得</li>
                    <li>每个卡密只能使用一次</li>
                    <li>卡密兑换后积分即时到账</li>
                    <li>如有问题请联系客服</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>