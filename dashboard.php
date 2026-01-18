<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$csrfToken = generateCsrfToken();

try {
    $stmt = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM projects WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $projects = [];
}

function getProjectStats($pdo, $projectId) {
    $stats = ['new' => 0, 'doing' => 0, 'done' => 0, 'total' => 0];
    try {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE project_id = ? GROUP BY status");
        $stmt->execute([$projectId]);
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
        }
    } catch (PDOException $e) {}
    return $stats;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>项目列表 - 项目待办看板</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-gray-800">项目待办看板</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="showPasswordModal()" class="text-gray-600 hover:text-indigo-600 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        修改密码
                    </button>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600">你好, <span class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></span></span>
                    <button onclick="logout()" class="text-indigo-600 hover:text-indigo-800 transition-colors">
                        退出登录
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">我的项目</h1>
            <button onclick="showCreateModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                新建项目
            </button>
        </div>

        <?php if (empty($projects)): ?>
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">暂无项目</h3>
                <p class="mt-2 text-gray-500">创建一个新项目开始使用看板</p>
                <button onclick="showCreateModal()" class="mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    创建项目
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($projects as $project): ?>
                    <?php $stats = getProjectStats($pdo, $project['id']); ?>
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all overflow-hidden border border-gray-200">
                        <a href="kanban.php?project_id=<?php echo $project['id']; ?>" class="block p-5">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-800"><?php echo htmlspecialchars($project['name']); ?></h3>
                                        <p class="text-xs text-gray-400 mt-0.5">创建于 <?php echo date('Y年m月d日', strtotime($project['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex space-x-1">
                                    <button onclick="event.preventDefault(); showEditModal(<?php echo $project['id']; ?>, <?php echo json_encode(htmlspecialchars($project['name'])); ?>, <?php echo json_encode(htmlspecialchars($project['description'] ?? '')); ?>)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="event.preventDefault(); deleteProject(<?php echo $project['id']; ?>)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <?php if ($project['description']): ?>
                                <p class="text-gray-600 text-sm mb-4 whitespace-pre-wrap line-clamp-3"><?php echo htmlspecialchars($project['description']); ?></p>
                            <?php else: ?>
                                <p class="text-gray-400 text-sm mb-4 italic">暂无项目描述</p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex space-x-4">
                                    <span class="flex items-center text-yellow-600 bg-yellow-50 px-2 py-1 rounded-lg">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?php echo $stats['new']; ?>
                                    </span>
                                    <span class="flex items-center text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        <?php echo $stats['doing']; ?>
                                    </span>
                                    <span class="flex items-center text-green-600 bg-green-50 px-2 py-1 rounded-lg">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?php echo $stats['done']; ?>
                                    </span>
                                </div>
                                <span class="text-gray-500 font-medium"><?php echo $stats['total']; ?> 任务</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="projectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
            <h2 id="modalTitle" class="text-xl font-bold text-gray-800 mb-4">新建项目</h2>
            <form id="projectForm" onsubmit="saveProject(event)">
                <input type="hidden" id="projectId" name="id">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="mb-4">
                    <label for="projectName" class="block text-sm font-medium text-gray-700 mb-2">项目名称</label>
                    <input type="text" id="projectName" name="name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="请输入项目名称">
                </div>
                <div class="mb-6">
                    <label for="projectDesc" class="block text-sm font-medium text-gray-700 mb-2">项目描述</label>
                    <textarea id="projectDesc" name="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="请输入项目描述，支持换行"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">取消</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">保存</button>
                </div>
            </form>
        </div>
    </div>

    <div id="passwordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4">修改密码</h2>
            <form id="passwordForm" onsubmit="changePassword(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div id="passwordError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm"></div>
                <div class="mb-4">
                    <label for="oldPassword" class="block text-sm font-medium text-gray-700 mb-2">原密码</label>
                    <input type="password" id="oldPassword" name="old_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="请输入原密码">
                </div>
                <div class="mb-4">
                    <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">新密码</label>
                    <input type="password" id="newPassword" name="new_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="请输入新密码（至少6位）">
                </div>
                <div class="mb-6">
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">确认新密码</label>
                    <input type="password" id="confirmPassword" name="confirm_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="请再次输入新密码">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hidePasswordModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">取消</button>
                    <button type="submit" id="passwordSubmitBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">确认修改</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function logout() {
            fetch('api/logout.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) window.location.href = 'index.php';
                });
        }

        function showCreateModal() {
            document.getElementById('modalTitle').textContent = '新建项目';
            document.getElementById('projectForm').reset();
            document.getElementById('projectId').value = '';
            document.getElementById('projectModal').classList.remove('hidden');
            document.getElementById('projectModal').classList.add('flex');
        }

        function showEditModal(id, name, desc) {
            event.preventDefault();
            document.getElementById('modalTitle').textContent = '编辑项目';
            document.getElementById('projectId').value = id;
            document.getElementById('projectName').value = name;
            document.getElementById('projectDesc').value = desc;
            document.getElementById('projectModal').classList.remove('hidden');
            document.getElementById('projectModal').classList.add('flex');
        }

        function hideModal() {
            document.getElementById('projectModal').classList.add('hidden');
            document.getElementById('projectModal').classList.remove('flex');
        }

        function saveProject(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('projectForm'));
            const id = formData.get('id');
            
            fetch('api/projects.php' + (id ? '?id=' + id : ''), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    hideModal();
                    location.reload();
                } else {
                    alert(data.message || '保存失败');
                }
            });
        }

        function deleteProject(id) {
            if (!confirm('确定要删除这个项目吗？相关任务也会被删除。')) return;
            fetch('api/projects.php?id=' + id, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.message || '删除失败');
                });
        }

        document.getElementById('projectModal').addEventListener('click', function(e) {
            if (e.target === this) hideModal();
        });

        function showPasswordModal() {
            document.getElementById('passwordForm').reset();
            document.getElementById('passwordError').classList.add('hidden');
            document.getElementById('passwordModal').classList.remove('hidden');
            document.getElementById('passwordModal').classList.add('flex');
        }

        function hidePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
            document.getElementById('passwordModal').classList.remove('flex');
        }

        function changePassword(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('passwordSubmitBtn');
            const errorDiv = document.getElementById('passwordError');
            const formData = new FormData(document.getElementById('passwordForm'));
            
            submitBtn.disabled = true;
            submitBtn.textContent = '修改中...';
            errorDiv.classList.add('hidden');
            
            fetch('api/change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    hidePasswordModal();
                    alert('密码修改成功！');
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '确认修改';
                }
            })
            .catch(error => {
                errorDiv.textContent = '网络错误，请重试';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = '确认修改';
            });
        }

        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) hidePasswordModal();
        });
    </script>
</body>
</html>
