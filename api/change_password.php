<?php
require_once '../config.php';
requireLogin();
$user = getCurrentUser();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效的请求方法']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$oldPassword = $data['old_password'] ?? '';
$newPassword = $data['new_password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';
$csrfToken = $data['csrf_token'] ?? '';

if (!verifyCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'CSRF验证失败']);
    exit;
}

if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => '所有字段都不能为空']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => '新密码和确认密码不一致']);
    exit;
}

if (strlen($newPassword) < 6 || strlen($newPassword) > 100) {
    echo json_encode(['success' => false, 'message' => '新密码长度必须在6-100位之间']);
    exit;
}

if (strlen($oldPassword) > 100) {
    echo json_encode(['success' => false, 'message' => '原密码长度超出限制']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser || !password_verify($oldPassword, $currentUser['password'])) {
        sleep(1);
        echo json_encode(['success' => false, 'message' => '原密码错误']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$newHash, $user['id']]);

    echo json_encode(['success' => true, 'message' => '密码修改成功']);
} catch (PDOException $e) {
    error_log("Change password error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '修改失败']);
}