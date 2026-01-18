<?php
require_once '../config.php';
requireLogin();
$user = getCurrentUser();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM projects WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '项目名称不能为空']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $user['id']]);
            $projectId = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'message' => '创建成功', 'id' => $projectId]);
            break;
            
        case 'PUT':
            $id = intval($_GET['id'] ?? 0);
            $data = json_decode(file_get_contents('php://input'), true);
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '项目名称不能为空']);
                exit;
            }
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的项目ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '项目不存在或无权限']);
                exit;
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['name', 'description'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field]) && $field === 'name') {
                    $val = trim($data[$field]);
                    if (!empty($val)) {
                        $updates[] = 'name = ?';
                        $params[] = $val;
                    }
                } elseif (isset($data[$field]) && $field === 'description') {
                    $updates[] = 'description = ?';
                    $params[] = trim($data[$field]);
                }
            }
            
            if (count($updates) > 0) {
                $updates[] = 'updated_at = NOW()';
                $params[] = $id;
                $params[] = $user['id'];
                
                $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            echo json_encode(['success' => true, 'message' => '更新成功']);
            break;
            
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的项目ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '项目不存在或无权限']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            
            echo json_encode(['success' => true, 'message' => '删除成功']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
    }
} catch (PDOException $e) {
    error_log("Database error in projects.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作失败']);
}