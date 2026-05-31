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

$stmt = $pdo->prepare("SELECT * FROM shop_items WHERE status = 1 ORDER BY id ASC");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    $items = array(
        array('id' => 1, 'name' => '500次大客户特惠', 'description' => '单次低至0.1元', 'price_points' => 50, 'value' => 500, 'stock' => 999),
        array('id' => 2, 'name' => '150次特惠套餐', 'description' => '单次低至0.2元', 'price_points' => 30, 'value' => 150, 'stock' => 999),
        array('id' => 3, 'name' => '67次进阶套餐', 'description' => '单次低至0.3元', 'price_points' => 20, 'value' => 67, 'stock' => 999),
        array('id' => 4, 'name' => '25次优惠套餐', 'description' => '单次低至0.4元', 'price_points' => 10, 'value' => 25, 'stock' => 999),
        array('id' => 5, 'name' => '2次体验卡', 'description' => '单次默认0.5元', 'price_points' => 2, 'value' => 2, 'stock' => 999),
    );
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemId = isset($_POST['item_id']) ? $_POST['item_id'] : 0;
    
    $stmt = $pdo->prepare("SELECT * FROM shop_items WHERE id = ? AND status = 1");
    $stmt->execute(array($itemId));
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        $msg = '商品不存在';
        $msgType = 'error';
    } else {
        $priceAmount = $item['price_points'] * 0.1;
        
        $stmt = $pdo->prepare("SELECT balance, points FROM user WHERE id = ?");
        $stmt->execute(array($userid));
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        $userBalance = isset($userData['balance']) ? $userData['balance'] : 0;
        
        if ($userBalance < $priceAmount) {
            $msg = '余额不足，请先充值';
            $msgType = 'error';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE user SET balance = balance - ? WHERE id = ?");
                $stmt->execute(array($priceAmount, $userid));
                
                $stmt = $pdo->prepare("UPDATE user SET points = points + ? WHERE id = ?");
                $stmt->execute(array($item['value'], $userid));
                
                $stmt = $pdo->prepare("INSERT INTO shop_orders (user_id, item_id, item_name, price_points, value) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(array($userid, $item['id'], $item['name'], $item['price_points'], $item['value']));
                
                $newBalance = $userBalance - $priceAmount;
                $stmt = $pdo->prepare("INSERT INTO account_logs (user_id, type, change_type, amount, balance, remark) VALUES (?, 'balance', 'expense', ?, ?, ?)");
                $stmt->execute(array($userid, $priceAmount, $newBalance, '购买积分商品：' . $item['name']));
                
                $pdo->commit();
                $msg = '购买成功！获得' . $item['value'] . ' 积分';
                $msgType = 'success';
                
                $stmt = $pdo->prepare("SELECT points, balance FROM user WHERE id = ?");
                $stmt->execute(array($userid));
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $userPoints = isset($user['points']) ? $user['points'] : 0;
                $_SESSION['points'] = $userPoints;
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = '购买失败: ' . $e->getMessage();
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
    <title>晴玖AI创作系统 - 积分商城</title>
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
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .card-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 20px; }
        .points-card { background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .points-info { display: flex; align-items: center; gap: 16px; }
        .points-icon { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #fff5f7, #ffe4e8); display: flex; align-items: center; justify-content: center; }
        .points-icon span { font-size: 24px; }
        .points-text h3 { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 4px; }
        .points-text p { font-size: 13px; color: #999; }
        .points-value { font-size: 28px; font-weight: 700; color: #ff6b81; }
        .btn-recharge { background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; padding: 10px 24px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 500; transition: transform 0.15s; }
        .btn-recharge:hover { transform: translateY(-1px); }
        .price-note { font-size: 13px; color: #999; margin-bottom: 20px; }
        .price-note span { color: #ff6b81; font-weight: 500; }
        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .item-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .item-header { background: linear-gradient(135deg, #fff5f7, #ffe4e8); padding: 16px; }
        .item-name { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 4px; }
        .item-desc { font-size: 12px; color: #999; }
        .item-body { padding: 16px; }
        .item-price { display: flex; align-items: baseline; gap: 4px; margin-bottom: 12px; }
        .price-label { font-size: 13px; color: #999; }
        .price-value { font-size: 24px; font-weight: 700; color: #ff6b81; }
        .price-unit { font-size: 13px; color: #999; }
        .item-value { font-size: 12px; color: #666; margin-bottom: 16px; }
        .item-value span { color: #52c41a; font-weight: 500; }
        .btn-buy { width: 100%; padding: 12px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 500; cursor: pointer; transition: transform 0.15s; }
        .btn-buy:hover { transform: translateY(-1px); }
        .btn-buy:disabled { opacity: 0.6; cursor: not-allowed; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: #f6ffed; color: #52c41a; }
        .alert.error { background: #fff5f5; color: #e53e3e; }
        .warning-box { background: #fff5f5; border-left: 4px solid #ff6b81; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; }
        .warning-box p { font-size: 13px; color: #666; line-height: 1.6; }
        .warning-box a { color: #ff6b81; text-decoration: none; }
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
            <li><a href="shop.php" class="active"><i>🛒</i>积分商城</a></li>
            <li><a href="card_exchange.php"><i>🎫</i>卡密兑换</a></li>
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
            <div class="header-left"><h2>积分商城</h2></div>
            <div class="header-right">
                <div class="user-points">积分: <?php echo isset($_SESSION['points']) ? $_SESSION['points'] : 1; ?></div>
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
        
        <div class="warning-box">
            <p>温馨提示：本站商品仅供个人使用，严禁用于商业用途。如有违规，我们将依法追究法律责任，并保留相关证据</p>
            <p style="margin-top:8px;">如有任何疑问，请联系客服 <a href="ticket.php">提交工单</a></p>
        </div>
        
        <?php if ($msg): ?>
            <div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <div class="points-card">
            <div class="points-info">
                <div class="points-icon"><span>🎁</span></div>
                <div class="points-text">
                    <h3>我的积分</h3>
                    <p>可用于生成图片</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="points-value"><?php echo $userPoints; ?></div>
            </div>
        </div>
        
        <div class="points-card" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
            <div class="points-info">
                <div class="points-icon" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);"><span>💰</span></div>
                <div class="points-text">
                    <h3>我的余额</h3>
                    <p>可用于购买积分</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="points-value" style="color: #22c55e;"> <?php echo number_format($userBalance, 2); ?></div>
                <a href="recharge.php" class="btn-recharge" style="background: linear-gradient(135deg, #22c55e, #16a34a);">去充值</a>
            </div>
        </div>
        
        <div class="price-note">
            <span>1积分 = 0.10元</span> | 1元 = 10积分
        </div>
        
        <div class="items-grid">
            <?php foreach ($items as $item): 
                $priceAmount = $item['price_points'] * 0.1;
                $canBuy = $userBalance >= $priceAmount;
            ?>
                <div class="item-card">
                    <div class="item-header">
                        <div class="item-name"><?php echo $item['name']; ?></div>
                        <div class="item-desc"><?php echo $item['description']; ?></div>
                    </div>
                    <div class="item-body">
                        <div class="item-price">
                            <span class="price-label">售价</span>
                            <span class="price-value" style="color: #22c55e;"><?php echo number_format($priceAmount, 2); ?></span>
                            <span class="price-unit">元</span>
                        </div>
                        <div class="item-value">
                            可获得<span><?php echo $item['value']; ?> 积分</span>
                        </div>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn-buy" <?php echo !$canBuy ? 'disabled' : ''; ?>>
                                <?php echo !$canBuy ? '余额不足' : '立即购买'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>