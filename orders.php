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
$userPoints = isset($user['points']) ? $user['points'] : 0;

$stmt = $pdo->prepare("SELECT * FROM recharge_orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute(array($userid));
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>晴玖AI创作系统 - 订单记录</title>
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
        .header-right { display: flex; align-items: center; gap: 20px; }
        .user-points { background: linear-gradient(135deg, #fff5f7, #ffe4e8); color: #ff6b81; padding: 8px 16px; border-radius: 20px; font-weight: 500; }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #ff6b81, #ff8fa3); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; }
        .avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .user-name { font-size: 14px; color: #333; }
        .logout-btn { padding: 8px 20px; background: #f5f5f5; border: none; border-radius: 8px; color: #666; cursor: pointer; transition: all 0.2s; }
        .logout-btn:hover { background: #eee; }
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .card-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 20px; }
        .order-list { margin-top: 20px; }
        .order-item { border: 1px solid #f0f0f0; border-radius: 12px; padding: 16px; margin-bottom: 12px; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .order-no { font-size: 14px; color: #999; }
        .order-time { font-size: 12px; color: #ccc; }
        .order-body { display: flex; justify-content: space-between; align-items: center; }
        .order-amount { font-size: 18px; font-weight: 600; color: #ff6b81; }
        .order-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .status-pending { background: #fffbeb; color: #d97706; }
        .status-success { background: #f0fff4; color: #38a169; }
        .status-failed { background: #fff5f5; color: #e53e3e; }
        .empty { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 16px; }
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
            <li><a href="orders.php" class="active"><i>📋</i>订单记录</a></li>
            <li><a href="account_detail.php"><i>📊</i>资产明细</a></li>
            <li><a href="api_config.php"><i>⚙️</i>接口配置</a></li>
            <li><a href="pay_config.php"><i>💳</i>支付配置</a></li>
            <li><a href="profile.php"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php"><i>🎫</i>售后工单</a></li>
        </ul>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>订单记录</h2></div>
            <div class="header-right">
                <div class="user-points">积分: <?php echo $userPoints; ?></div>
                <div class="user-info">
                    <div class="avatar">
                        <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo mb_substr(isset($user['username']) ? $user['username'] : 'U', 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?php echo isset($user['username']) ? $user['username'] : ''; ?></div>
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'">退出登录</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">充值订单</div>
            
            <?php if (empty($orders)): ?>
                <div class="empty">
                    <div class="empty-icon">📦</div>
                    <p>暂无订单记录</p>
                </div>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <span class="order-no">订单号：<?php echo $order['order_no']; ?></span>
                                <span class="order-time"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-body">
                                <span class="order-amount"> <?php echo number_format($order['amount'], 2); ?></span>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo $order['status'] === 'pending' ? '待支付' : ($order['status'] === 'success' ? '已完成' : '已失败'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>