<?php
session_start();
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

require_once 'config.php';
require_once 'api_config_loader.php';

$apiConfig = getEnabledApiConfig();
if (!$apiConfig) {
    echo json_encode(['success' => false, 'message' => '未配置AI接口']);
    exit;
}

$prompt = trim($_POST['prompt'] ?? '');
$size = $_POST['size'] ?? '1024x1024';

if (empty($prompt)) {
    echo json_encode(['success' => false, 'message' => '请输入描述词']);
    exit;
}

$userid = $_SESSION['userid'];
$stmt = $pdo->prepare("SELECT points FROM user WHERE id = ?");
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || ($user['points'] ?? 0) < 1) {
    echo json_encode(['success' => false, 'message' => '积分不足']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE user SET points = points - 1 WHERE id = ? AND points >= 1");
    $stmt->execute([$userid]);
    
    $provider = $apiConfig['provider'];
    $config = $apiConfig['config'];
    $apiKey = $config['api_key'];
    $model = $config['model'] ?? 'dall-e-3';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/images/generations');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model,
        'prompt' => $prompt,
        'n' => 1,
        'size' => $size
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $pdo->rollBack();
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'API请求失败';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    
    $data = json_decode($response, true);
    $imageUrl = $data['data'][0]['url'] ?? '';
    
    if (empty($imageUrl)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => '未获取到图片']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO works (user_id, prompt, image_url, size) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userid, $prompt, $imageUrl, $size]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'url' => $imageUrl]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>