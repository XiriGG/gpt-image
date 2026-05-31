<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';
require_once 'api_config_loader.php';

$userid = $_SESSION['userid'];
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute(array($userid));
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$_SESSION['points'] = isset($user['points']) ? $user['points'] : 1;
$_SESSION['avatar'] = isset($user['avatar']) ? $user['avatar'] : '';

$hasApiConfig = getEnabledApiConfig() !== null;

$styles = array(
    array('id' => 'realistic', 'name' => '写实风格', 'description' => '逼真细腻，还原真实场景'),
    array('id' => 'anime', 'name' => '动漫风格', 'description' => '日系动漫，卡通可爱'),
    array('id' => 'oil', 'name' => '油画风格', 'description' => '经典油画，艺术气息'),
    array('id' => 'watercolor', 'name' => '水彩风格', 'description' => '清新淡雅，如水彩画'),
);

$aspectRatios = array(
    array('id' => '1:1', 'name' => '1:1', 'desc' => '正方形'),
    array('id' => '3:2', 'name' => '3:2', 'desc' => '横版'),
    array('id' => '2:3', 'name' => '2:3', 'desc' => '竖版'),
    array('id' => '4:3', 'name' => '4:3', 'desc' => '横版'),
    array('id' => '3:4', 'name' => '3:4', 'desc' => '竖版'),
    array('id' => '16:9', 'name' => '16:9', 'desc' => '宽屏'),
    array('id' => '9:16', 'name' => '9:16', 'desc' => '竖屏'),
    array('id' => '21:9', 'name' => '21:9', 'desc' => '超宽屏'),
);

$qualityLevels = array(
    array('id' => 'auto', 'name' => '默认', 'code' => 'auto'),
    array('id' => 'low', 'name' => '1K', 'code' => 'low'),
    array('id' => 'medium', 'name' => '2K', 'code' => 'medium'),
    array('id' => 'high', 'name' => '4K', 'code' => 'high'),
);

$models = array(
    array('id' => 'gpt1', 'name' => 'GPT①号', 'tags' => array('首选', '推荐', '不稳定', '时间长'), 'desc' => '有时候不太稳定，失败就重试或者换一个。等待时间较长，但质量非常好。广告图宣传图首选！', 'points' => 1),
    array('id' => 'gpt2', 'name' => 'GPT②号', 'tags' => array('备用', '推荐', '文生图'), 'desc' => '1号失败就用这个，质量和1号差不多，生成会快一点。不支持上传参考图！', 'points' => 1),
    array('id' => 'gemini', 'name' => 'Gemini', 'tags' => array('最快', '拉胯', '支持图文'), 'desc' => '这个最快，十几秒左右出图。提示失败就重试。单纯生成图片还可以，要是图中有文字或者要加字的话不要用，很差。', 'points' => 1),
);

$msg = '';
$msgType = '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>晴玖AI创作系统 - AI生图</title>
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
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: #f6ffed; color: #52c41a; }
        .alert.error { background: #fff5f5; color: #e53e3e; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; }
        .form-textarea { width: 100%; padding: 14px 16px; border: 2px solid #f0f0f0; border-radius: 12px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 100px; transition: border-color 0.2s; outline: none; }
        .form-textarea:focus { border-color: #ff6b81; }
        .form-hint { font-size: 12px; color: #999; margin-top: 8px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .grid-8 { display: grid; grid-template-columns: repeat(8, 1fr); gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .style-card { padding: 16px; border: 2px solid #f0f0f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; text-align: center; }
        .style-card:hover { border-color: #ff6b81; background: #fff5f7; }
        .style-card.selected { border-color: #ff6b81; background: #ffeef0; }
        .style-card .style-name { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 4px; }
        .style-card .style-desc { font-size: 12px; color: #999; }
        .ratio-card { padding: 12px; border: 2px solid #f0f0f0; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: center; }
        .ratio-card:hover { border-color: #ff6b81; }
        .ratio-card.selected { border-color: #ff6b81; background: #ffeef0; }
        .ratio-card .ratio-value { font-size: 13px; font-weight: 600; color: #333; }
        .ratio-card .ratio-desc { font-size: 11px; color: #999; margin-top: 2px; }
        .model-card { padding: 16px; border: 2px solid #f0f0f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; }
        .model-card:hover { border-color: #ff6b81; }
        .model-card.selected { border-color: #ff6b81; background: #ffeef0; }
        .model-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .model-name { font-size: 15px; font-weight: 600; color: #333; }
        .model-points { font-size: 12px; color: #ff6b81; font-weight: 500; }
        .model-tags { display: flex; flex-wrap: gap: 6px; margin-bottom: 8px; }
        .tag { padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        .tag.recommend { background: #fef3c7; color: #d97706; }
        .tag.fast { background: #dcfce7; color: #16a34a; }
        .tag.warning { background: #fee2e2; color: #dc2626; }
        .model-desc { font-size: 12px; color: #999; line-height: 1.5; }
        .btn-generate { width: 100%; padding: 16px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s; }
        .btn-generate:hover { transform: translateY(-1px); }
        .btn-generate:disabled { opacity: 0.6; cursor: not-allowed; }
        .upload-area { border: 2px dashed #e5e7eb; border-radius: 12px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .upload-area:hover { border-color: #ff6b81; background: #fff5f7; }
        .upload-area.dragover { border-color: #ff6b81; background: #ffeef0; }
        .upload-icon { font-size: 32px; color: #999; margin-bottom: 12px; }
        .upload-text { font-size: 14px; color: #666; }
        .upload-hint { font-size: 12px; color: #999; margin-top: 4px; }
        .preview-container { margin-top: 24px; }
        .preview-image { max-width: 100%; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .generating { text-align: center; padding: 48px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f0f0f0; border-top-color: #ff6b81; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .generating-text { font-size: 14px; color: #666; }
        .api-warning { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; }
        .api-warning p { font-size: 13px; color: #666; }
        .api-warning a { color: #ff6b81; text-decoration: none; }
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
            <li><a href="index.php" class="active"><i>🎨</i>AI生图</a></li>
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
    </div>
    
    <div class="main">
        <div class="header">
            <div class="header-left"><h2>AI生图</h2></div>
            <div class="header-right">
                <div class="user-points">积分: <?php echo $_SESSION['points']; ?></div>
                <div class="user-info">
                    <div class="avatar">
                        <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像">
                        <?php else: ?>
                            <?php echo mb_substr($user['username'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?php echo $user['username']; ?></div>
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'">退出登录</button>
            </div>
        </div>
        
        <?php if (!$hasApiConfig): ?>
        <div class="api-warning">
            <p>⚠️ 尚未配置API接口，请先前往 <a href="api_config.php">接口配置</a> 页面进行配置，否则无法生成图片。</p>
        </div>
        <?php endif; ?>
        
        <?php if ($msg): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title">📝 输入提示词</div>
            <div class="form-group">
                <label class="form-label">描述你想要生成的图片</label>
                <textarea class="form-textarea" id="prompt" placeholder="例如：一位美丽的亚洲女性，长发飘飘，穿着白色连衣裙，站在花海中，阳光明媚，专业摄影，高清画质..." rows="4"></textarea>
                <div class="form-hint">提示词越详细，生成的图片越符合你的预期。支持中文和英文。</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">🎨 选择风格</div>
            <div class="grid-4">
                <?php foreach ($styles as $style): ?>
                <div class="style-card" data-id="<?php echo $style['id']; ?>" onclick="selectStyle('<?php echo $style['id']; ?>')">
                    <div class="style-name"><?php echo $style['name']; ?></div>
                    <div class="style-desc"><?php echo $style['description']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">📐 选择比例</div>
            <div class="grid-8">
                <?php foreach ($aspectRatios as $ratio): ?>
                <div class="ratio-card" data-id="<?php echo $ratio['id']; ?>" onclick="selectRatio('<?php echo $ratio['id']; ?>')">
                    <div class="ratio-value"><?php echo $ratio['name']; ?></div>
                    <div class="ratio-desc"><?php echo $ratio['desc']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">⚡ 选择模型</div>
            <div class="grid-3">
                <?php foreach ($models as $model): ?>
                <div class="model-card" data-id="<?php echo $model['id']; ?>" onclick="selectModel('<?php echo $model['id']; ?>')">
                    <div class="model-header">
                        <span class="model-name"><?php echo $model['name']; ?></span>
                        <span class="model-points"><?php echo $model['points']; ?>积分</span>
                    </div>
                    <div class="model-tags">
                        <?php foreach ($model['tags'] as $tag): ?>
                        <span class="tag <?php echo $tag === '推荐' || $tag === '首选' ? 'recommend' : ($tag === '最快' ? 'fast' : 'warning'); ?>"><?php echo $tag; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="model-desc"><?php echo $model['desc']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">🖼️ 参考图片（可选）</div>
            <div class="upload-area" id="uploadArea" onclick="document.getElementById('referenceImage').click()">
                <div class="upload-icon">📤</div>
                <div class="upload-text">点击或拖拽上传参考图片</div>
                <div class="upload-hint">支持 jpg、png 格式，最大 5MB</div>
            </div>
            <input type="file" id="referenceImage" accept="image/jpeg,image/png" style="display: none;" onchange="handleImageUpload(event)">
            <div id="previewContainer" class="preview-container" style="display: none;">
                <img id="previewImage" class="preview-image" src="" alt="预览">
                <button style="margin-top: 12px; padding: 8px 16px; background: #f5f5f5; border: none; border-radius: 8px; cursor: pointer;" onclick="removeImage()">移除图片</button>
            </div>
        </div>
        
        <div class="card">
            <button class="btn-generate" id="generateBtn" onclick="generateImage()">🎨 开始生成</button>
        </div>
        
        <div id="resultContainer" style="display: none;">
            <div class="card">
                <div class="card-title">🎭 生成结果</div>
                <div id="resultContent">
                    <div class="generating">
                        <div class="spinner"></div>
                        <div class="generating-text">正在生成图片，请稍候...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        var selectedStyle = 'realistic';
        var selectedRatio = '1:1';
        var selectedModel = 'gpt1';
        var referenceImage = null;
        
        function selectStyle(id) {
            selectedStyle = id;
            document.querySelectorAll('.style-card').forEach(el => el.classList.remove('selected'));
            document.querySelector(`.style-card[data-id="${id}"]`).classList.add('selected');
        }
        
        function selectRatio(id) {
            selectedRatio = id;
            document.querySelectorAll('.ratio-card').forEach(el => el.classList.remove('selected'));
            document.querySelector(`.ratio-card[data-id="${id}"]`).classList.add('selected');
        }
        
        function selectModel(id) {
            selectedModel = id;
            document.querySelectorAll('.model-card').forEach(el => el.classList.remove('selected'));
            document.querySelector(`.model-card[data-id="${id}"]`).classList.add('selected');
        }
        
        function handleImageUpload(event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    referenceImage = file;
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                    document.getElementById('uploadArea').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage() {
            referenceImage = null;
            document.getElementById('referenceImage').value = '';
            document.getElementById('previewContainer').style.display = 'none';
            document.getElementById('uploadArea').style.display = 'block';
        }
        
        function generateImage() {
            var prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                alert('请输入提示词');
                return;
            }
            
            var btn = document.getElementById('generateBtn');
            btn.disabled = true;
            btn.innerHTML = '🎨 生成中...';
            
            document.getElementById('resultContainer').style.display = 'block';
            document.getElementById('resultContent').innerHTML = `
                <div class="generating">
                    <div class="spinner"></div>
                    <div class="generating-text">正在生成图片，请稍候...</div>
                </div>
            `;
            
            var formData = new FormData();
            formData.append('prompt', prompt);
            formData.append('style', selectedStyle);
            formData.append('ratio', selectedRatio);
            formData.append('model', selectedModel);
            if (referenceImage) {
                formData.append('image', referenceImage);
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'generate_image.php', true);
            xhr.onload = function() {
                btn.disabled = false;
                btn.innerHTML = '🎨 开始生成';
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            document.getElementById('resultContent').innerHTML = `
                                <img src="${response.image_url}" class="preview-image" alt="生成的图片">
                                <div style="margin-top: 16px; display: flex; gap: 12px;">
                                    <button style="flex: 1; padding: 12px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; cursor: pointer;" onclick="downloadImage('${response.image_url}')">📥 下载图片</button>
                                    <button style="flex: 1; padding: 12px; background: #f5f5f5; color: #666; border: none; border-radius: 10px; cursor: pointer;" onclick="regenerateImage()">🔄 重新生成</button>
                                </div>
                            `;
                        } else {
                            document.getElementById('resultContent').innerHTML = `<div class="alert error">生成失败: ${response.message}</div>`;
                        }
                    } catch (e) {
                        document.getElementById('resultContent').innerHTML = `<div class="alert error">生成失败: 服务器返回无效响应</div>`;
                    }
                } else {
                    document.getElementById('resultContent').innerHTML = `<div class="alert error">生成失败: 服务器错误</div>`;
                }
            };
            xhr.onerror = function() {
                btn.disabled = false;
                btn.innerHTML = '🎨 开始生成';
                document.getElementById('resultContent').innerHTML = `<div class="alert error">生成失败: 网络错误</div>`;
            };
            xhr.send(formData);
        }
        
        function downloadImage(url) {
            var link = document.createElement('a');
            link.href = url;
            link.download = 'ai-generated-image.jpg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function regenerateImage() {
            generateImage();
        }
        
        document.getElementById('uploadArea').addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        document.getElementById('uploadArea').addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        
        document.getElementById('uploadArea').addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            var file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                document.getElementById('referenceImage').files = e.dataTransfer.files;
                handleImageUpload({ target: { files: [file] } });
            }
        });
        
        selectStyle('realistic');
        selectRatio('1:1');
        selectModel('gpt1');
    </script>
</body>
</html>