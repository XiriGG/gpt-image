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

$stmt = $pdo->prepare("SELECT * FROM works WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute(array($userid));
$works = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) ? $_POST['action'] : '') === 'delete') {
    header('Content-Type: application/json; charset=utf-8');
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $stmt = $pdo->prepare("DELETE FROM works WHERE id = ? AND user_id = ?");
    $stmt->execute(array($id, $userid));
    echo json_encode(array('success' => true));
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的作品 - 晴玖AI创作系统</title>
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
        
        .works-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .work-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .work-card:hover { transform: translateY(-4px); }
        .work-image { width: 100%; aspect-ratio: 1; object-fit: cover; }
        .work-info { padding: 16px; }
        .work-title { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .work-meta { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #999; }
        .work-actions { display: flex; gap: 8px; margin-top: 12px; }
        .action-btn { flex: 1; padding: 8px; text-align: center; border-radius: 8px; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .action-btn.view { background: #f5f5f5; color: #666; }
        .action-btn.view:hover { background: #e8e8e8; }
        .action-btn.delete { background: #fff5f5; color: #ff6b81; }
        .action-btn.delete:hover { background: #ffeef0; }
        
        .empty-state { text-align: center; padding: 80px 0; }
        .empty-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-text { font-size: 16px; color: #999; }
        .empty-link { display: inline-block; margin-top: 16px; padding: 10px 24px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border-radius: 20px; text-decoration: none; font-size: 14px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; border-radius: 16px; width: 90%; max-width: 500px; overflow: hidden; }
        .modal-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 18px; font-weight: 600; color: #333; }
        .modal-close { width: 32px; height: 32px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; color: #999; }
        .modal-body { padding: 20px; }
        .modal-image { width: 100%; border-radius: 8px; margin-bottom: 16px; }
        .modal-prompt { background: #f8f9fa; padding: 12px 16px; border-radius: 8px; font-size: 14px; color: #666; line-height: 1.6; }
        .modal-footer { padding: 20px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 12px; }
        .btn { padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; }
        .btn-secondary { background: #f5f5f5; color: #666; border: none; }
        
        .toast { position: fixed; bottom: 32px; right: 32px; padding: 12px 24px; border-radius: 8px; color: #fff; font-size: 14px; z-index: 2000; animation: slideIn 0.3s ease; }
        .toast.success { background: #52c41a; }
        .toast.error { background: #e53e3e; }
        @keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
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
            <li><a href="works.php" class="active"><i>📁</i>我的作品</a></li>
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
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>我的作品</h2></div>
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
        
        <div class="works-grid">
            <?php if (count($works) > 0): ?>
                <?php foreach ($works as $work): ?>
                    <div class="work-card">
                        <img src="<?php echo htmlspecialchars($work['image_url']); ?>" alt="作品" class="work-image" onclick="openModal('<?php echo htmlspecialchars($work['image_url']); ?>', '<?php echo htmlspecialchars($work['prompt']); ?>')">
                        <div class="work-info">
                            <div class="work-title"><?php echo htmlspecialchars(mb_substr($work['prompt'], 0, 20)) . '...'; ?></div>
                            <div class="work-meta">
                                <span><?php echo date('Y-m-d', strtotime($work['created_at'])); ?></span>
                                <span><?php echo $work['status'] === 'completed' ? '已完成' : '生成中'; ?></span>
                            </div>
                            <div class="work-actions">
                                <div class="action-btn view" onclick="openModal('<?php echo htmlspecialchars($work['image_url']); ?>', '<?php echo htmlspecialchars($work['prompt']); ?>')">查看</div>
                                <div class="action-btn delete" onclick="deleteWork(<?php echo $work['id']; ?>)">删除</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🖼️</div>
                    <div class="empty-text">还没有作品，快去生成吧！</div>
                    <a href="index.php" class="empty-link">立即生成</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">作品详情</div>
                <div class="modal-close" onclick="closeModal()">×</div>
            </div>
            <div class="modal-body">
                <img src="" id="modal-image" class="modal-image">
                <div class="modal-prompt" id="modal-prompt"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">关闭</button>
                <button class="btn btn-primary" onclick="copyPrompt()">复制提示词</button>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(imageUrl, prompt) {
            document.getElementById('modal-image').src = imageUrl;
            document.getElementById('modal-prompt').textContent = prompt;
            document.getElementById('viewModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('show');
        }
        
        function copyPrompt() {
            var prompt = document.getElementById('modal-prompt').textContent;
            navigator.clipboard.writeText(prompt).then(function() {
                showToast('已复制到剪贴板');
            });
        }
        
        async function deleteWork(id) {
            if (!confirm('确定要删除这个作品吗？')) return;
            var res = await fetch('works.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'delete', id: id })
            });
            var data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                showToast('删除失败', 'error');
            }
        }
        
        function showToast(message, type) {
            if (type === undefined) type = 'success';
            var toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 3000);
        }
    </script>
</body>
</html>