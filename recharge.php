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

$rechargeAmounts = array(10, 20, 50, 100, 200, 500);

$stmt = $pdo->prepare("SELECT * FROM recharge_orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute(array($userid));
$rechargeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
    $payType = isset($_POST['pay_type']) ? $_POST['pay_type'] : 'alipay';
    
    if (!in_array($amount, $rechargeAmounts)) {
        $msg = '请选择有效的充值金额';
        $msgType = 'error';
    } else {
        $orderNo = date('YmdHis') . rand(1000, 9999);
        
        $stmt = $pdo->prepare("INSERT INTO recharge_orders (order_no, user_id, amount, pay_type, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute(array($orderNo, $userid, $amount, $payType));
        
        $payConfig = array(
            'url' => 'https://pay.yunhuni.com/api/payment',
            'api_key' => '123456',
            'secret' => 'your_secret_key'
        );
        
        $notifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/recharge_notify.php';
        $returnUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/recharge.php';
        
        $params = array(
            'pid' => $payConfig['api_key'],
            'type' => $payType,
            'out_trade_no' => $orderNo,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'name' => '积分充值',
            'money' => $amount,
            'sign_type' => 'MD5'
        );
        
        ksort($params);
        $sign = md5(http_build_query($params) . '&key=' . $payConfig['secret']);
        $params['sign'] = $sign;
        
        $formHtml = '<form id="payForm" action="' . $payConfig['url'] . '" method="post">';
        foreach ($params as $key => $value) {
            $formHtml .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
        }
        $formHtml .= '</form><script>document.getElementById("payForm").submit();</script>';
        
        echo $formHtml;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>余额充值 - 晴玖AI创作系统</title>
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
        
        .notice-bar { background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; border-left: 4px solid #ff6b81; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .notice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .notice-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #333; }
        .notice-badge { padding: 2px 8px; background: #fff1f0; color: #ff6b81; border-radius: 4px; font-size: 12px; }
        .notice-close { width: 24px; height: 24px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; color: #999; }
        .notice-content { font-size: 13px; color: #666; line-height: 1.6; margin-bottom: 8px; }
        .notice-contact { font-size: 13px; color: #1890ff; display: flex; align-items: center; gap: 8px; }
        .notice-expand { cursor: pointer; }
        
        .recharge-container { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 900px; margin: 0 auto; }
        
        .recharge-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .card-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 24px; display: flex; align-items: center; gap: 8px; }
        
        .balance-card { padding: 20px; border-radius: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .balance-card.pink { background: #ffeef0; }
        .balance-card.blue { background: #e6f7ff; }
        .balance-label { font-size: 14px; color: #999; display: flex; align-items: center; gap: 8px; }
        .balance-value { font-size: 28px; font-weight: 700; }
        .balance-value.pink { color: #ff6b81; }
        .balance-value.blue { color: #1890ff; }
        
        .form-group { margin-bottom: 24px; }
        .form-label { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 16px; display: block; }
        
        .amount-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .amount-btn { padding: 20px; border: 2px solid #e8e8e8; border-radius: 12px; background: #fff; cursor: pointer; transition: all 0.2s; font-size: 18px; font-weight: 600; color: #333; }
        .amount-btn:hover { border-color: #ff6b81; }
        .amount-btn.active { border-color: #ff6b81; background: #ffeef0; color: #ff6b81; }
        .amount-btn.custom { color: #999; }
        .amount-btn.custom.active { color: #ff6b81; }
        
        .custom-input { margin-top: 12px; display: none; }
        .custom-input.show { display: block; }
        .custom-input input { width: 100%; padding: 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 16px; outline: none; transition: border-color 0.2s; }
        .custom-input input:focus { border-color: #ff6b81; }
        
        .pay-type-group { margin-top: 12px; }
        .pay-type-btn { width: 100%; padding: 16px; border: 2px solid #f0f0f0; border-radius: 10px; background: #fff; cursor: pointer; transition: all 0.2s; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .pay-type-btn:hover { border-color: #ff6b81; }
        .pay-type-btn.active { border-color: #ff6b81; background: #ffeef0; color: #ff6b81; }
        
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { transform: translateY(-1px); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .records-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        
        .records-table { width: 100%; border-collapse: collapse; }
        .records-table th, .records-table td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .records-table th { font-size: 13px; color: #999; font-weight: 500; }
        .records-table td { font-size: 14px; color: #333; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .status-badge.success { background: #f6ffed; color: #52c41a; }
        .status-badge.pending { background: #fff7e6; color: #fa8c16; }
        .status-badge.failed { background: #fff1f0; color: #f5222d; }
        
        .empty-records { text-align: center; padding: 40px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        
        .toast { position: fixed; bottom: 32px; right: 32px; padding: 12px 24px; border-radius: 8px; color: #fff; font-size: 14px; z-index: 2000; animation: slideIn 0.3s ease; }
        .toast.success { background: #52c41a; }
        .toast.error { background: #e53e3e; }
        @keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        .warning-box { background: #fff7e6; border: 1px solid #ffe58f; border-radius: 8px; padding: 16px; margin-bottom: 20px; font-size: 14px; color: #fa8c16; }
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
            <li><a href="recharge.php" class="active"><i>💰</i>余额充值</a></li>
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
            <div class="header-left"><h2>余额充值</h2></div>
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
        
        <div class="notice-bar" id="noticeBar">
            <div class="notice-header">
                <div class="notice-title">
                    <span>📢</span> 系统公告
                    <span class="notice-badge">重要</span>
                </div>
                <div class="notice-close" onclick="document.getElementById('noticeBar').style.display='none'">✕</div>
            </div>
            <div class="notice-content">
                郑重声明：本产品/服务仅允许用于合法合规的业务场景，严禁将其用于任何违法违规、损害公共利益或侵犯他人合法权益的业务展示及相关活动。一经发现违规使用行为，我方将立即终止相关授权，并保留追究其法律责任的权利。
            </div>
            <div class="notice-contact">
                <span>如您有任何使用上的疑问，可联系官方客服咨询：1557320789</span>
                <span class="notice-expand" onclick="toggleNotice()">▼ 展开</span>
            </div>
        </div>
        
        <div class="recharge-container">
            <div class="recharge-card">
                <div class="card-title">💰 余额充值</div>
                
                <div class="balance-card pink">
                    <div class="balance-label"><span>💰</span> 当前余额</div>
                    <div class="balance-value pink">¥<?php echo number_format($userBalance, 2); ?></div>
                </div>
                
                <div class="balance-card blue">
                    <div class="balance-label"><span>⭐</span> 积分</div>
                    <div class="balance-value blue"><?php echo $userPoints; ?></div>
                </div>
                
                <form method="post" action="recharge.php">
                    <div class="form-group">
                        <label class="form-label">选择金额</label>
                        <div class="amount-grid">
                            <button type="button" class="amount-btn" onclick="selectAmount(10)">10</button>
                            <button type="button" class="amount-btn" onclick="selectAmount(50)">50</button>
                            <button type="button" class="amount-btn" onclick="selectAmount(100)">100</button>
                            <button type="button" class="amount-btn" onclick="selectAmount(200)">200</button>
                            <button type="button" class="amount-btn" onclick="selectAmount(500)">500</button>
                            <button type="button" class="amount-btn custom" onclick="selectCustom()">自定义</button>
                        </div>
                        <div class="custom-input" id="customInput">
                            <input type="number" id="customAmount" placeholder="请输入金额" onblur="checkCustomAmount()">
                        </div>
                        <input type="hidden" name="amount" id="selectedAmount" value="">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">支付方式</label>
                        <div class="pay-type-group">
                            <button type="button" class="pay-type-btn active" onclick="selectPayType('alipay')"><span>💳</span>支付宝</button>
                        </div>
                        <input type="hidden" name="pay_type" id="selectedPayType" value="alipay">
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn" disabled><span>💳</span>立即充值</button>
                </form>
            </div>
            
            <div class="records-card">
                <div class="card-title">📋 充值记录</div>
                
                <?php if (empty($rechargeRecords)): ?>
                    <div class="empty-records">
                        <div class="empty-icon">📋</div>
                        <div>暂无充值记录</div>
                    </div>
                <?php else: ?>
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>订单号</th>
                                <th>金额</th>
                                <th>支付方式</th>
                                <th>状态</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rechargeRecords as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['order_no']); ?></td>
                                    <td>¥<?php echo number_format($record['amount'], 2); ?></td>
                                    <td><?php echo $record['pay_type'] === 'alipay' ? '支付宝' : '微信支付'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $record['status']; ?>">
                                            <?php 
                                                $statusText = '';
                                                if ($record['status'] === 'success') $statusText = '成功';
                                                elseif ($record['status'] === 'pending') $statusText = '处理中';
                                                else $statusText = '失败';
                                                echo $statusText;
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function selectAmount(amount) {
            document.querySelectorAll('.amount-btn').forEach(function(el) { el.classList.remove('active'); });
            event.target.classList.add('active');
            document.getElementById('selectedAmount').value = amount;
            document.getElementById('customInput').classList.remove('show');
            document.getElementById('submitBtn').disabled = false;
        }
        
        function selectCustom() {
            document.querySelectorAll('.amount-btn').forEach(function(el) { el.classList.remove('active'); });
            document.getElementById('customInput').classList.add('show');
            document.getElementById('selectedAmount').value = '';
            document.getElementById('submitBtn').disabled = true;
        }
        
        function checkCustomAmount() {
            var customAmount = document.getElementById('customAmount').value;
            if (customAmount && customAmount > 0) {
                document.getElementById('selectedAmount').value = customAmount;
                document.getElementById('submitBtn').disabled = false;
            } else {
                document.getElementById('selectedAmount').value = '';
                document.getElementById('submitBtn').disabled = true;
            }
        }
        
        function selectPayType(type) {
            document.querySelectorAll('.pay-type-btn').forEach(function(el) { el.classList.remove('active'); });
            event.target.classList.add('active');
            document.getElementById('selectedPayType').value = type;
        }
        
        function toggleNotice() {
            var notice = document.getElementById('noticeBar');
            if (notice.classList.contains('expanded')) {
                notice.classList.remove('expanded');
                event.target.textContent = '▼ 展开';
            } else {
                notice.classList.add('expanded');
                event.target.textContent = '▲ 收起';
            }
        }
        
        <?php if ($msg): ?>
            showToast('<?php echo $msg; ?>', '<?php echo $msgType; ?>');
        <?php endif; ?>
        
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