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

$stmt = $pdo->prepare("SELECT w.*, u.username FROM works w JOIN user u ON w.user_id = u.id ORDER BY w.created_at DESC");
$stmt->execute();
$works = $stmt->fetchAll(PDO::FETCH_ASSOC);

$initialImages = array(
    array(
        'url' => '1.png',
        'title' => '温婉时光',
        'author' => '晴玖用户',
        'model' => 'GPT①号',
        'prompt' => 'beautiful young asian woman, long dark wavy hair, wearing elegant dress, soft warm lighting, professional portrait photography',
        'time' => '05-25 21:04'
    ),
    array(
        'url' => '1.png',
        'title' => '柔光少女',
        'author' => '晴玖用户',
        'model' => 'GPT②号',
        'prompt' => 'elegant asian beauty, long hair, white blouse, warm lighting, professional portrait photography, soft focus',
        'time' => '05-25 05:24'
    ),
    array(
        'url' => '1.png',
        'title' => '暖阳浅笑',
        'author' => '晴玖用户',
        'model' => 'Gemini',
        'prompt' => 'beautiful chinese woman, gentle expression, white shirt, soft sunlight, natural makeup, portrait',
        'time' => '05-25 01:39'
    ),
    array(
        'url' => '1.png',
        'title' => '优雅时刻',
        'author' => '晴玖用户',
        'model' => 'GPT①号',
        'prompt' => 'young asian woman, natural makeup, white outfit, elegant pose, studio photography, high fashion',
        'time' => '05-25 01:18'
    ),
    array(
        'url' => '1.png',
        'title' => '清新日常',
        'author' => '晴玖用户',
        'model' => 'GPT②号',
        'prompt' => 'pretty asian girl, long hair, casual white shirt, natural beauty, outdoor portrait, sunny day',
        'time' => '05-24 23:45'
    ),
    array(
        'url' => '1.png',
        'title' => '温柔岁月',
        'author' => '晴玖用户',
        'model' => 'GPT①号',
        'prompt' => 'beautiful woman, soft features, white blouse, warm ambient lighting, cozy room, lifestyle portrait',
        'time' => '05-24 22:12'
    ),
    array(
        'url' => '1.png',
        'title' => '梦幻光影',
        'author' => '晴玖用户',
        'model' => 'Gemini',
        'prompt' => 'elegant young woman, flowing hair, white clothes, dreamy lighting, artistic portrait, ethereal',
        'time' => '05-24 20:30'
    ),
    array(
        'url' => '1.png',
        'title' => '自然之美',
        'author' => '晴玖用户',
        'model' => 'GPT①号',
        'prompt' => 'asian beauty, natural look, white shirt, soft smile, professional portrait photography, minimalist',
        'time' => '05-24 19:15'
    ),
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>观赏大厅 - 晴玖AI创作系统</title>
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
        
        .gallery-header { background: linear-gradient(135deg, #ff6b81, #ff8fa3); border-radius: 16px; padding: 32px; margin-bottom: 24px; color: #fff; }
        .gallery-title { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .gallery-desc { font-size: 14px; opacity: 0.9; }
        
        .stats-row { display: flex; gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; flex: 1; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-icon.pink { background: #ffeef0; color: #ff6b81; }
        .stat-icon.blue { background: #e6f7ff; color: #1890ff; }
        .stat-icon.purple { background: #f9f0ff; color: #722ed1; }
        .stat-info .stat-value { font-size: 24px; font-weight: 700; color: #333; }
        .stat-info .stat-label { font-size: 13px; color: #999; }
        
        .works-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .work-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .work-image { width: 100%; height: 280px; object-fit: cover; }
        .work-info { padding: 12px; }
        .work-author-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .author-avatar { width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(135deg, #ff6b81, #ff8fa3); display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff; }
        .author-name { font-size: 12px; color: #666; }
        .work-actions { display: flex; gap: 8px; margin-bottom: 8px; }
        .action-btn { flex: 1; padding: 6px; border-radius: 6px; font-size: 11px; cursor: pointer; border: none; transition: all 0.2s; }
        .action-btn.download { background: #f5f5f5; color: #666; }
        .action-btn.download:hover { background: #e8e8e8; }
        .action-btn.prompt { background: #ffeef0; color: #ff6b81; }
        .action-btn.prompt:hover { background: #ffe0e5; }
        .work-model { font-size: 11px; color: #ff6b81; background: #fff0f0; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-right: 8px; }
        .work-time { font-size: 11px; color: #999; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; }
        .empty-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-title { font-size: 18px; color: #333; margin-bottom: 8px; }
        .empty-desc { font-size: 14px; color: #999; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; border-radius: 16px; max-width: 90vw; max-height: 90vh; overflow: hidden; }
        .modal-image { max-width: 100%; max-height: 70vh; }
        .modal-info { padding: 20px; }
        .modal-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 8px; }
        .modal-author { font-size: 14px; color: #999; }
        .modal-close { position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.5); color: #fff; border: none; cursor: pointer; font-size: 18px; }
        
        .prompt-modal { display: flex; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 3000; align-items: center; justify-content: center; padding: 20px; }
        .prompt-modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 500px; overflow: hidden; }
        .prompt-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f0f0f0; }
        .prompt-modal-title { font-size: 16px; font-weight: 600; color: #333; }
        .prompt-modal-close { width: 28px; height: 28px; border-radius: 50%; background: #f5f5f5; border: none; cursor: pointer; font-size: 16px; color: #666; }
        .prompt-modal-text { width: 100%; padding: 16px 20px; min-height: 150px; border: none; resize: none; font-family: monospace; font-size: 14px; line-height: 1.6; color: #333; background: #fafafa; }
        .prompt-modal-copy { width: calc(100% - 40px); margin: 16px 20px; padding: 12px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; }
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
            <li><a href="gallery.php" class="active"><i>🖼️</i>观赏大厅</a></li>
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
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>观赏大厅</h2></div>
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
        
        <div class="gallery-header">
            <div class="gallery-title">🎨 作品展示</div>
            <div class="gallery-desc">欣赏社区用户创作的精美AI作品</div>
        </div>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon pink">🖼️</div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo max(count($works), count($initialImages)); ?></div>
                    <div class="stat-label">作品总数</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-info">
                    <div class="stat-value">8</div>
                    <div class="stat-label">活跃创作者</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🔥</div>
                <div class="stat-info">
                    <div class="stat-value">128</div>
                    <div class="stat-label">总浏览量</div>
                </div>
            </div>
        </div>
        
        <div class="works-grid">
            <?php if (empty($works)): ?>
                <?php foreach ($initialImages as $img): ?>
                    <div class="work-card">
                        <img class="work-image" src="<?php echo $img['url']; ?>" alt="<?php echo $img['title']; ?>">
                        <div class="work-info">
                            <div class="work-author-row">
                                <div class="author-avatar"><?php echo mb_substr($img['author'], 0, 1); ?></div>
                                <span class="author-name"><?php echo $img['author']; ?></span>
                            </div>
                            <div class="work-actions">
                                <button class="action-btn download" onclick="downloadImage('<?php echo $img['url']; ?>', '<?php echo $img['title']; ?>')">⬇️ 下载</button>
                                <button class="action-btn prompt" onclick="showPrompt('<?php echo htmlspecialchars($img['prompt']); ?>')">💡 提示词</button>
                            </div>
                            <div>
                                <span class="work-model"><?php echo $img['model']; ?></span>
                                <span class="work-time"><?php echo $img['time']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($works as $work): ?>
                    <div class="work-card">
                        <img class="work-image" src="<?php echo htmlspecialchars($work['image_url']); ?>" alt="<?php echo htmlspecialchars($work['title']); ?>">
                        <div class="work-info">
                            <div class="work-author-row">
                                <div class="author-avatar"><?php echo mb_substr($work['username'], 0, 1); ?></div>
                                <span class="author-name"><?php echo htmlspecialchars($work['username']); ?></span>
                            </div>
                            <div class="work-actions">
                                <button class="action-btn download" onclick="downloadImage('<?php echo htmlspecialchars($work['image_url']); ?>', '<?php echo htmlspecialchars($work['title'] ?: '作品'); ?>')">⬇️ 下载</button>
                                <button class="action-btn prompt" onclick="showPrompt('<?php echo htmlspecialchars(isset($work['prompt']) ? $work['prompt'] : ''); ?>')">💡 提示词</button>
                            </div>
                            <div>
                                <span class="work-model"><?php echo htmlspecialchars(isset($work['model']) ? $work['model'] : 'unknown'); ?></span>
                                <span class="work-time"><?php echo date('m-d H:i', strtotime($work['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (empty($works) && empty($initialImages)): ?>
            <div class="empty-state">
                <div class="empty-icon">🖼️</div>
                <div class="empty-title">暂无作品</div>
                <div class="empty-desc">快来创作你的第一件作品吧！</div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="modal" id="previewModal">
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-content">
            <img id="modalImage" class="modal-image" src="">
            <div class="modal-info">
                <div class="modal-title" id="modalTitle"></div>
                <div class="modal-author" id="modalAuthor"></div>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(url, title, author) {
            document.getElementById('modalImage').src = url;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalAuthor').textContent = '作者: ' + author;
            document.getElementById('previewModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('previewModal').classList.remove('show');
        }
        
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === document.getElementById('previewModal')) {
                closeModal();
            }
        });
        
        function downloadImage(url, title) {
            var link = document.createElement('a');
            link.href = url;
            link.download = title + '.jpg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function showPrompt(promptText) {
            if (!promptText) {
                alert('该作品暂无提示词信息');
                return;
            }
            var modal = document.createElement('div');
            modal.className = 'prompt-modal';
            modal.innerHTML = '\n                <div class="prompt-modal-content">\n                    <div class="prompt-modal-header">\n                        <span class="prompt-modal-title">💡 提示词</span>\n                        <button class="prompt-modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>\n                    </div>\n                    <textarea class="prompt-modal-text" readonly>' + promptText + '</textarea>\n                    <button class="prompt-modal-copy" onclick="copyPrompt(this.parentElement.querySelector(\'.prompt-modal-text\').value)">📋 复制提示词</button>\n                </div>\n            ';
            document.body.appendChild(modal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        function copyPrompt(promptText) {
            navigator.clipboard.writeText(promptText).then(function() {
                alert('提示词已复制到剪贴板');
            }).catch(function() {
                alert('复制失败，请手动复制');
            });
        }
    </script>
</body>
</html>