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

$_SESSION['points'] = isset($user['points']) ? $user['points'] : 1;
$_SESSION['avatar'] = isset($user['avatar']) ? $user['avatar'] : '';

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM works WHERE user_id = ?");
$stmt->execute(array($userid));
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$generateCount = isset($result['count']) ? $result['count'] : 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM recharge_orders WHERE user_id = ?");
$stmt->execute(array($userid));
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$orderCount = isset($result['count']) ? $result['count'] : 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM recharge_orders WHERE user_id = ? AND status = 'success'");
$stmt->execute(array($userid));
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalSpent = isset($result['total']) ? $result['total'] : 0;

$stmt = $pdo->prepare("SELECT * FROM works WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute(array($userid));
$recentWorks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$quickActions = array(
    array('icon' => '🎨', 'title' => 'AI生图', 'desc' => '立即创作', 'url' => 'index.php'),
    array('icon' => '💰', 'title' => '充值积分', 'desc' => '快速充值', 'url' => 'recharge.php'),
    array('icon' => '🛒', 'title' => '积分商城', 'desc' => '兑换商品', 'url' => 'shop.php'),
    array('icon' => '🖼️', 'title' => '观赏大厅', 'desc' => '浏览作品', 'url' => 'gallery.php'),
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仪表盘 - 晴玖AI创作系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", sans-serif; background: #f5f5f5; min-height: 100vh; }
        
        .sidebar { width: 200px; background: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; left: 0; top: 0; bottom: 0; padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar-logo { text-align: center; margin-bottom: 32px; padding: 0 20px; }
        .sidebar-logo h1 { font-size: 18px; color: #333; margin-bottom: 4px; font-weight: 600; }
        .sidebar-logo p { font-size: 12px; color: #999; }
        .sidebar-nav { list-style: none; padding: 0 12px; }
        .sidebar-nav li { margin-bottom: 4px; }
        .sidebar-nav a { display: flex; align-items: center; padding: 12px 16px; border-radius: 8px; color: #666; text-decoration: none; transition: all 0.2s; font-size: 14px; }
        .sidebar-nav a:hover { background: #fff5f7; color: #ff6b81; }
        .sidebar-nav a.active { background: #ffeef0; color: #ff6b81; font-weight: 500; }
        .sidebar-nav a i { margin-right: 12px; font-size: 16px; }
        .sidebar-version { margin-top: auto; text-align: center; padding: 16px; font-weight: 700; border-top: 1px solid #f0f0f0; }
        .sidebar-version span { font-size: 14px; color: #666; display: block; font-weight: 700; text-align: center; }
        
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
        
        .welcome-card { background: linear-gradient(135deg, #ff6b81, #ff8fa3); border-radius: 16px; padding: 32px; margin-bottom: 24px; color: #fff; }
        .welcome-title { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .welcome-desc { font-size: 14px; opacity: 0.9; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-icon.pink { background: #ffeef0; color: #ff6b81; }
        .stat-icon.blue { background: #e6f7ff; color: #1890ff; }
        .stat-icon.green { background: #f6ffed; color: #52c41a; }
        .stat-icon.red { background: #fff1f0; color: #f5222d; }
        .stat-value { font-size: 32px; font-weight: 700; color: #333; margin-bottom: 4px; }
        .stat-label { font-size: 14px; color: #999; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .action-card { background: #fff; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .action-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .action-icon { font-size: 36px; margin-bottom: 12px; }
        .action-title { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 4px; }
        .action-desc { font-size: 12px; color: #999; }
        
        .recent-section { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: 600; color: #333; }
        .view-all { font-size: 14px; color: #ff6b81; text-decoration: none; }
        
        .recent-list { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
        .recent-item { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 1; }
        .recent-item img { width: 100%; height: 100%; object-fit: cover; }
        .recent-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; }
        .recent-item:hover .recent-overlay { opacity: 1; }
        .recent-btn { padding: 8px 16px; background: rgba(255,255,255,0.9); border: none; border-radius: 20px; font-size: 13px; color: #333; cursor: pointer; }
        
        .empty-recent { text-align: center; padding: 40px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <h1>晴玖AI创作系统</h1>
            <p>专业AI图片生成</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i>📊</i>仪表盘</a></li>
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
            <li><a href="profile.php"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php"><i>🎫</i>售后工单</a></li>
        </ul>
        <div class="sidebar-version">
            <span>版本号：v2.12</span>
        </div>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>仪表盘</h2></div>
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
        
        <div class="welcome-card">
            <div class="welcome-title">👋 欢迎回来，<?php echo isset($user['username']) ? $user['username'] : '用户'; ?>！</div>
            <div class="welcome-desc">今天想创作什么风格的图片呢？</div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon pink">🎁</div>
                </div>
                <div class="stat-value"><?php echo $_SESSION['points']; ?></div>
                <div class="stat-label">积分</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon blue">🖼️</div>
                </div>
                <div class="stat-value"><?php echo $generateCount; ?></div>
                <div class="stat-label">生图次数</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon green">📋</div>
                </div>
                <div class="stat-value"><?php echo $orderCount; ?></div>
                <div class="stat-label">订单数量</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon red">💳</div>
                </div>
                <div class="stat-value">¥<?php echo number_format($totalSpent, 2); ?></div>
                <div class="stat-label">累计消费</div>
            </div>
        </div>
        
        <div class="quick-actions">
            <?php foreach ($quickActions as $action): ?>
                <a href="<?php echo $action['url']; ?>" class="action-card">
                    <div class="action-icon"><?php echo $action['icon']; ?></div>
                    <div class="action-title"><?php echo $action['title']; ?></div>
                    <div class="action-desc"><?php echo $action['desc']; ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="recent-section">
            <div class="section-header">
                <div class="section-title">最近生图记录</div>
                <a href="works.php" class="view-all">查看全部 →</a>
            </div>
            
            <?php if (empty($recentWorks)): ?>
                <div class="empty-recent">
                    <div class="empty-icon">🖼️</div>
                    <div>暂无作品记录</div>
                    <div style="font-size: 12px; margin-top: 4px;">快去创作你的第一张图片吧！</div>
                </div>
            <?php else: ?>
                <div class="recent-list">
                    <?php foreach ($recentWorks as $work): ?>
                        <div class="recent-item">
                            <img src="<?php echo htmlspecialchars($work['image_url']); ?>" alt="">
                            <div class="recent-overlay">
                                <a href="works.php"><button class="recent-btn">查看详情</button></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>