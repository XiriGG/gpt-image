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

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute(array($userid));
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'other';
    
    if (empty($title) || empty($content)) {
        $msg = '请填写完整信息';
        $msgType = 'error';
    } else {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, content, type, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute(array($userid, $title, $content, $type));
        
        $msg = '工单提交成功，我们会尽快处理';
        $msgType = 'success';
        
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute(array($userid));
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>售后工单 - 晴玖AI创作系统</title>
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
        
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        
        .form-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .card-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 24px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 8px; display: block; }
        .form-input { width: 100%; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-input:focus { border-color: #ff6b81; }
        
        .type-options { display: flex; flex-wrap: wrap; gap: 12px; }
        .type-btn { padding: 10px 20px; border: 2px solid #f0f0f0; border-radius: 8px; background: #fff; cursor: pointer; transition: all 0.2s; font-size: 14px; }
        .type-btn:hover { border-color: #ff6b81; }
        .type-btn.active { border-color: #ff6b81; background: #ffeef0; color: #ff6b81; }
        
        .form-textarea { width: 100%; height: 150px; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; resize: vertical; outline: none; transition: border-color 0.2s; font-family: inherit; }
        .form-textarea:focus { border-color: #ff6b81; }
        
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: transform 0.15s; }
        .btn-submit:hover { transform: translateY(-1px); }
        
        .records-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        
        .tickets-list { margin-top: 16px; }
        .ticket-item { padding: 16px; border-radius: 12px; background: #f8f9fa; margin-bottom: 12px; cursor: pointer; transition: all 0.2s; }
        .ticket-item:hover { background: #f0f0f0; }
        .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .ticket-title { font-size: 15px; font-weight: 600; color: #333; }
        .ticket-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .ticket-status.pending { background: #fff7e6; color: #fa8c16; }
        .ticket-status.processing { background: #e6f7ff; color: #1890ff; }
        .ticket-status.completed { background: #f6ffed; color: #52c41a; }
        .ticket-meta { display: flex; justify-content: space-between; font-size: 13px; color: #999; }
        
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        
        .toast { position: fixed; bottom: 32px; right: 32px; padding: 12px 24px; border-radius: 8px; color: #fff; font-size: 14px; z-index: 2000; animation: slideIn 0.3s ease; }
        .toast.success { background: #52c41a; }
        .toast.error { background: #e53e3e; }
        @keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: #f6ffed; color: #52c41a; }
        .alert.error { background: #fff5f5; color: #e53e3e; }
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
            <li><a href="profile.php"><i>👤</i>个人资料</a></li>
            <li><a href="ticket.php" class="active"><i>🎫</i>售后工单</a></li>
        </ul>
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>售后工单</h2></div>
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
        
        <div class="container">
            <div class="form-card">
                <div class="card-title">提交工单</div>
                
                <?php if ($msg): ?>
                    <div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label class="form-label">工单标题</label>
                        <input type="text" class="form-input" name="title" placeholder="请输入工单标题">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">问题类型</label>
                        <div class="type-options">
                            <button type="button" class="type-btn active" onclick="selectType('payment')">💳 支付问题</button>
                            <button type="button" class="type-btn" onclick="selectType('generate')">🎨 生成问题</button>
                            <button type="button" class="type-btn" onclick="selectType('account')">👤 账户问题</button>
                            <button type="button" class="type-btn" onclick="selectType('other')">📝 其他问题</button>
                        </div>
                        <input type="hidden" name="type" id="selectedType" value="payment">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">问题描述</label>
                        <textarea class="form-textarea" name="content" placeholder="请详细描述您的问题..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">提交工单</button>
                </form>
            </div>
            
            <div class="records-card">
                <div class="card-title">我的工单</div>
                
                <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <div>暂无工单记录</div>
                        <div style="font-size: 12px; margin-top: 4px;">有问题请随时提交工单</div>
                    </div>
                <?php else: ?>
                    <div class="tickets-list">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="ticket-item">
                                <div class="ticket-header">
                                    <div class="ticket-title"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                    <span class="ticket-status <?php echo $ticket['status']; ?>">
                                        <?php 
                                            $statusText = '';
                                            if ($ticket['status'] === 'pending') $statusText = '待处理';
                                            elseif ($ticket['status'] === 'processing') $statusText = '处理中';
                                            else $statusText = '已完成';
                                            echo $statusText;
                                        ?>
                                    </span>
                                </div>
                                <div class="ticket-meta">
                                    <span><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></span>
                                    <span>ID: #<?php echo $ticket['id']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function selectType(type) {
            document.querySelectorAll('.type-btn').forEach(el => el.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('selectedType').value = type;
        }
        
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