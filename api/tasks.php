<?php
require_once '../config.php';
requireLogin();
$user = getCurrentUser();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $projectId = intval($_GET['project_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT p.*, t.id as task_id, t.title, t.description, t.status, t.priority, t.due_date, t.created_at, t.updated_at 
                                   FROM projects p 
                                   LEFT JOIN tasks t ON p.id = t.project_id 
                                   WHERE p.id = ? AND p.user_id = ?
                                   ORDER BY t.created_at DESC");
            $stmt->execute([$projectId, $user['id']]);
            $results = $stmt->fetchAll();
            
            $tasks = ['new' => [], 'doing' => [], 'done' => []];
            foreach ($results as $row) {
                if ($row['task_id']) {
                    $task = [
                        'id' => $row['task_id'],
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'status' => $row['status'],
                        'priority' => $row['priority'],
                        'due_date' => $row['due_date'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at']
                    ];
                    $tasks[$row['status']][] = $task;
                }
            }
            
            echo json_encode(['success' => true, 'data' => $tasks, 'project' => [
                'id' => $results[0]['id'] ?? 0,
                'name' => $results[0]['name'] ?? ''
            ]]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $projectId = intval($data['project_id'] ?? 0);
            $title = trim($data['title'] ?? '');
            $description = trim($data['description'] ?? '');
            $status = $data['status'] ?? 'new';
            $priority = intval($data['priority'] ?? 0);
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => '任务标题不能为空']);
                exit;
            }
            
            $validStatuses = ['new', 'doing', 'done'];
            if (!in_array($status, $validStatuses)) {
                $status = 'new';
            }
            
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$projectId, $user['id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '项目不存在或无权限']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, project_id, status, priority) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $projectId, $status, $priority]);
            
            echo json_encode(['success' => true, 'message' => '创建成功', 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'PUT':
            $id = intval($_GET['id'] ?? 0);
            $data = json_decode(file_get_contents('php://input'), true);
            $title = trim($data['title'] ?? '');
            $description = trim($data['description'] ?? '');
            $status = $data['status'] ?? '';
            $priority = intval($data['priority'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的任务ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT t.id FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.id = ? AND p.user_id = ?");
            $stmt->execute([$id, $user['id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '任务不存在或无权限']);
                exit;
            }
            
            $allowedFields = ['title', 'description', 'status', 'priority'];
            $updates = [];
            $params = [];
            
            if (!empty($title)) {
                $updates[] = 'title = ?';
                $params[] = $title;
            }
            if (!empty($description)) {
                $updates[] = 'description = ?';
                $params[] = $description;
            }
            $validStatuses = ['new', 'doing', 'done'];
            if (!empty($status) && in_array($status, $validStatuses)) {
                $updates[] = 'status = ?';
                $params[] = $status;
            }
            if ($priority >= 0) {
                $updates[] = 'priority = ?';
                $params[] = $priority;
            }
            
            if (count($updates) > 0) {
                $updates[] = 'updated_at = NOW()';
                $params[] = $id;
                
                $sql = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            echo json_encode(['success' => true, 'message' => '更新成功']);
            break;
            
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的任务ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT t.id FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.id = ? AND p.user_id = ?");
            $stmt->execute([$id, $user['id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '任务不存在或无权限']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => '删除成功']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
    }
} catch (PDOException $e) {
    error_log("Database error in tasks.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作失败']);
}