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
$userBalance = isset($user['balance']) ? $user['balance'] : 0;

$stmt = $pdo->prepare("SELECT * FROM account_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute(array($userid));
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>资产明细 - 晴玖AI创作系统</title>
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
        
        .balance-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
        .balance-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .balance-card.pink { background: linear-gradient(135deg, #fff5f7, #ffeef0); }
        .balance-card.blue { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); }
        .balance-icon { font-size: 32px; margin-bottom: 12px; }
        .balance-label { font-size: 14px; color: #666; margin-bottom: 4px; }
        .balance-value { font-size: 32px; font-weight: 700; }
        .balance-value.pink { color: #ff6b81; }
        .balance-value.blue { color: #1890ff; }
        
        .logs-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .card-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 20px; }
        
        .logs-table { width: 100%; border-collapse: collapse; }
        .logs-table th, .logs-table td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .logs-table th { font-size: 13px; color: #999; font-weight: 500; }
        .logs-table td { font-size: 14px; color: #333; }
        
        .amount.income { color: #52c41a; }
        .amount.expense { color: #ff6b81; }
        
        .type-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .type-badge.points { background: #fff0f0; color: #ff4d4f; }
        .type-badge.balance { background: #e6f7ff; color: #1890ff; }
        
        .empty-state { text-align: center; padding: 40px; color: #999; }
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
            <li><a href="dashboard.php"><i>📊</i>仪表盘</a></li>
            <li><a href="index.php"><i>🎨</i>AI生图</a></li>
            <li><a href="gallery.php"><i>🖼️</i>观赏大厅</a></li>
            <li><a href="works.php"><i>📁</i>我的作品</a></li>
            <li><a href="shop.php"><i>🛒</i>积分商城</a></li>
            <li><a href="card_exchange.php"><i>🎫</i>卡密兑换</a></li>
            <li><a href="recharge.php"><i>💰</i>余额充值</a></li>
            <li><a href="orders.php"><i>📋</i>订单记录</a></li>
            <li><a href="account_detail.php" class="active"><i>📊</i>资产明细</a></li>
            <li><a href="api_config.php"><i>⚙️</i>接口配置</a></li>
            <li><a href="pay_config.php"><i>💳</i>支付配置</a></li>
            <li><a href="profile.php"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php"><i>🎫</i>售后工单</a></li>
        </ul>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>资产明细</h2></div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo mb_substr(isset($user['username']) ? $user['username'] : 'U', 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?php echo isset($user['username']) ? $user['username'] : '用户'; ?></div>
                    <div class="user-points"><?php echo $userPoints; ?> 积分</div>
                </div>
                <a href="logout.php" class="logout-btn">退出登录</a>
            </div>
        </div>
        
        <div class="balance-cards">
            <div class="balance-card pink">
                <div class="balance-icon">🎁</div>
                <div class="balance-label">当前积分</div>
                <div class="balance-value pink"><?php echo $userPoints; ?></div>
            </div>
            <div class="balance-card blue">
                <div class="balance-icon">💰</div>
                <div class="balance-label">当前余额</div>
                <div class="balance-value blue">¥<?php echo number_format($userBalance, 2); ?></div>
            </div>
        </div>
        
        <div class="logs-card">
            <div class="card-title">交易记录</div>
            
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <div>暂无交易记录</div>
                </div>
            <?php else: ?>
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>类型</th>
                            <th>变动</th>
                            <th>金额</th>
                            <th>余额</th>
                            <th>备注</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><span class="type-badge <?php echo $log['type']; ?>">
                                    <?php echo $log['type'] === 'points' ? '积分' : '余额'; ?>
                                </span></td>
                                <td><?php echo $log['change_type'] === 'income' ? '收入' : '支出'; ?></td>
                                <td class="amount <?php echo $log['change_type']; ?>">
                                    <?php echo $log['change_type'] === 'income' ? '+' : '-'; ?>
                                    <?php echo $log['type'] === 'points' ? $log['amount'] : '¥' . number_format($log['amount'], 2); ?>
                                </td>
                                <td>
                                    <?php echo $log['type'] === 'points' ? $log['balance'] : '¥' . number_format($log['balance'], 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['remark']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>