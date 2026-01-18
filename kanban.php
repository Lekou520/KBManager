<?php
require_once 'config.php';
requireLogin();

$projectId = intval($_GET['project_id'] ?? 0);
if (!$projectId) {
    header('Location: dashboard.php');
    exit;
}

$user = getCurrentUser();
$csrfToken = generateCsrfToken();

try {
    $stmt = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, $user['id']]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header('Location: dashboard.php');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM projects WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $allProjects = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $taskCount = $stmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("查询失败");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - 项目待办看板</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .task-card { cursor: grab; }
        .task-card:active { cursor: grabbing; }
        .task-card.sortable-ghost { opacity: 0.4; }
        .task-card.sortable-chosen { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .column-content { min-height: 150px; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <nav class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center text-gray-600 hover:text-indigo-600 transition-colors text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        返回
                    </a>
                    <div class="mx-3 h-4 w-px bg-gray-300"></div>
                    <div class="relative">
                        <button onclick="toggleProjectDropdown()" class="flex items-center text-gray-800 font-medium hover:text-indigo-600 transition-colors text-sm">
                            <?php echo htmlspecialchars($project['name']); ?>
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="projectDropdown" class="hidden absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                            <?php foreach ($allProjects as $p): ?>
                                <a href="kanban.php?project_id=<?php echo $p['id']; ?>" 
                                   class="flex items-center px-4 py-2 text-sm <?php echo $p['id'] == $projectId ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                    <span class="truncate"><?php echo htmlspecialchars($p['name']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs text-gray-500"><?php echo $taskCount; ?> 个任务</span>
                    <button onclick="showCreateTaskModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-1.5 px-3 rounded-lg transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        添加任务
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($project['name']); ?></h1>
                            <p class="text-xs text-gray-500 mt-0.5">创建于 <?php echo date('Y年m月d日', strtotime($project['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php if ($project['description']): ?>
                        <p class="text-gray-600 text-sm leading-relaxed pl-13 whitespace-pre-wrap"><?php echo htmlspecialchars($project['description']); ?></p>
                    <?php else: ?>
                        <p class="text-gray-400 text-sm italic pl-13">暂无项目描述</p>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-1 ml-4">
                    <button onclick="showProjectEditModal()" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="编辑项目">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-b from-yellow-50 to-yellow-100/50 rounded-xl border border-yellow-200 overflow-hidden">
                <div class="px-4 py-3 bg-yellow-100/50 border-b border-yellow-200 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-yellow-200 rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="font-semibold text-yellow-800 text-sm">新需求</h2>
                    </div>
                    <span id="count-new" class="bg-yellow-200 text-yellow-700 text-xs font-medium px-2 py-0.5 rounded-full">0</span>
                </div>
                <div id="column-new" class="p-3 space-y-2 column-content" data-status="new"></div>
            </div>

            <div class="bg-gradient-to-b from-blue-50 to-blue-100/50 rounded-xl border border-blue-200 overflow-hidden">
                <div class="px-4 py-3 bg-blue-100/50 border-b border-blue-200 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-200 rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h2 class="font-semibold text-blue-800 text-sm">进行中</h2>
                    </div>
                    <span id="count-doing" class="bg-blue-200 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full">0</span>
                </div>
                <div id="column-doing" class="p-3 space-y-2 column-content" data-status="doing"></div>
            </div>

            <div class="bg-gradient-to-b from-green-50 to-green-100/50 rounded-xl border border-green-200 overflow-hidden">
                <div class="px-4 py-3 bg-green-100/50 border-b border-green-200 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-200 rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="font-semibold text-green-800 text-sm">已完成</h2>
                    </div>
                    <span id="count-done" class="bg-green-200 text-green-700 text-xs font-medium px-2 py-0.5 rounded-full">0</span>
                </div>
                <div id="column-done" class="p-3 space-y-2 column-content" data-status="done"></div>
            </div>
        </div>
    </div>

    <div id="taskModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 transform transition-all">
            <h2 id="taskModalTitle" class="text-lg font-bold text-gray-800 mb-4">新建任务</h2>
            <form id="taskForm" onsubmit="saveTask(event)">
                <input type="hidden" id="taskId" name="id">
                <input type="hidden" id="taskProjectId" name="project_id" value="<?php echo $projectId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="mb-4">
                    <label for="taskTitle" class="block text-sm font-medium text-gray-700 mb-1.5">任务标题</label>
                    <input type="text" id="taskTitle" name="title" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="请输入任务标题">
                </div>
                <div class="mb-4">
                    <label for="taskDesc" class="block text-sm font-medium text-gray-700 mb-1.5">任务描述</label>
                    <textarea id="taskDesc" name="description" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="请输入任务描述，支持换行"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <label for="taskStatus" class="block text-sm font-medium text-gray-700 mb-1.5">状态</label>
                        <select id="taskStatus" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="new">新需求</option>
                            <option value="doing">进行中</option>
                            <option value="done">已完成</option>
                        </select>
                    </div>
                    <div>
                        <label for="taskPriority" class="block text-sm font-medium text-gray-700 mb-1.5">优先级</label>
                        <select id="taskPriority" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="0">普通</option>
                            <option value="1">重要</option>
                            <option value="2">紧急</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideTaskModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors text-sm">取消</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm">保存</button>
                </div>
            </form>
        </div>
    </div>

    <div id="projectModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
            <h2 id="projectModalTitle" class="text-lg font-bold text-gray-800 mb-4">编辑项目</h2>
            <form id="projectForm" onsubmit="saveProject(event)">
                <input type="hidden" id="projectId" name="id" value="<?php echo $project['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="mb-4">
                    <label for="projectName" class="block text-sm font-medium text-gray-700 mb-1.5">项目名称</label>
                    <input type="text" id="projectName" name="name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        value="<?php echo htmlspecialchars($project['name']); ?>">
                </div>
                <div class="mb-5">
                    <label for="projectDesc" class="block text-sm font-medium text-gray-700 mb-1.5">项目描述</label>
                    <textarea id="projectDesc" name="description" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="请输入项目描述，支持换行"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideProjectModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors text-sm">取消</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const projectId = <?php echo $projectId; ?>;
        let columns = {};

        function initSortable() {
            ['new', 'doing', 'done'].forEach(status => {
                const column = document.getElementById('column-' + status);
                columns[status] = column;
                new Sortable(column, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function(evt) {
                        const item = evt.item;
                        updateTaskStatus(item.dataset.taskId, evt.to.dataset.status);
                    }
                });
            });
        }

        function updateTaskStatus(taskId, status) {
            fetch('api/tasks.php?id=' + taskId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) updateCounts();
                else { showToast(data.message || '更新失败', 'error'); loadTasks(); }
            })
            .catch(() => { showToast('网络错误', 'error'); loadTasks(); });
        }

        function loadTasks() {
            fetch('api/tasks.php?project_id=' + projectId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        ['new', 'doing', 'done'].forEach(status => {
                            const column = document.getElementById('column-' + status);
                            column.innerHTML = '';
                            data.data[status].forEach(task => column.appendChild(createTaskCard(task)));
                        });
                        updateCounts();
                    }
                });
        }

        function createTaskCard(task) {
            const card = document.createElement('div');
            card.className = 'task-card bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-all';
            card.dataset.taskId = task.id;
            
            const priorityConfig = {
                2: { class: 'bg-red-100 text-red-700', label: '紧急' },
                1: { class: 'bg-orange-100 text-orange-700', label: '重要' },
                0: { class: 'bg-gray-100 text-gray-600', label: '普通' }
            };
            const p = priorityConfig[task.priority] || priorityConfig[0];
            
            card.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full ${p.class}">${p.label}</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-400">${formatTime(task.created_at)}</span>
                    </div>
                </div>
                <h3 class="font-medium text-gray-800 text-sm mb-1 leading-tight whitespace-pre-wrap">${task.title}</h3>
                ${task.description ? `<p class="text-xs text-gray-500 whitespace-pre-wrap line-clamp-2 mb-2">${task.description}</p>` : ''}
                <div class="flex items-center justify-between">
                    <div class="flex space-x-1">
                        <button onclick='editTask(${task.id}, ${JSON.stringify(task.title)}, ${JSON.stringify(task.description || "")}, "${task.status}", ${task.priority})' class="p-1 text-gray-400 hover:text-indigo-600 rounded">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button onclick="deleteTask(${task.id})" class="p-1 text-gray-400 hover:text-red-600 rounded">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <span class="text-xs text-gray-400">${task.updated_at ? '更新于 ' + formatTime(task.updated_at) : ''}</span>
                </div>
            `;
            return card;
        }

        function updateCounts() {
            ['new', 'doing', 'done'].forEach(status => {
                document.getElementById('count-' + status).textContent = document.getElementById('column-' + status).children.length;
            });
        }

        function showCreateTaskModal() {
            document.getElementById('taskModalTitle').textContent = '新建任务';
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
            document.getElementById('taskStatus').value = 'new';
            document.getElementById('taskModal').classList.remove('hidden');
            document.getElementById('taskModal').classList.add('flex');
        }

        function showProjectEditModal() {
            document.getElementById('projectModal').classList.remove('hidden');
            document.getElementById('projectModal').classList.add('flex');
        }

        function hideProjectModal() {
            document.getElementById('projectModal').classList.add('hidden');
            document.getElementById('projectModal').classList.remove('flex');
        }

        function saveProject(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('projectForm'));
            fetch('api/projects.php?id=' + projectId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message || '保存失败');
            });
        }

        function editTask(id, title, description, status, priority) {
            document.getElementById('taskModalTitle').textContent = '编辑任务';
            document.getElementById('taskId').value = id;
            document.getElementById('taskTitle').value = title;
            document.getElementById('taskDesc').value = description;
            document.getElementById('taskStatus').value = status;
            document.getElementById('taskPriority').value = priority;
            document.getElementById('taskModal').classList.remove('hidden');
            document.getElementById('taskModal').classList.add('flex');
        }

        function hideTaskModal() {
            document.getElementById('taskModal').classList.add('hidden');
            document.getElementById('taskModal').classList.remove('flex');
        }

        function saveTask(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('taskForm'));
            const id = formData.get('id');
            const data = { project_id: projectId, title: formData.get('title'), description: formData.get('description'), status: formData.get('status'), priority: formData.get('priority') };
            
            fetch('api/tasks.php' + (id ? '?id=' + id : ''), {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { hideTaskModal(); loadTasks(); }
                else alert(data.message || '保存失败');
            });
        }

        function deleteTask(id) {
            if (!confirm('确定要删除这个任务吗？')) return;
            fetch('api/tasks.php?id=' + id, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => { if (data.success) loadTasks(); else alert(data.message || '删除失败'); });
        }

        function toggleProjectDropdown() { document.getElementById('projectDropdown').classList.toggle('hidden'); }

        function logout() {
            fetch('api/logout.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => { if (data.success) window.location.href = 'index.php'; });
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg text-white text-sm shadow-lg z-50 ${type === 'error' ? 'bg-red-500' : 'bg-indigo-500'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        function formatTime(dateStr) {
            if (!dateStr) return '未知';
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return '刚刚';
            if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前';
            if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前';
            if (diff < 604800000) return Math.floor(diff / 86400000) + '天前';
            
            return date.toLocaleDateString('zh-CN');
        }

        ['taskModal', 'projectModal'].forEach(id => {
            document.getElementById(id).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        });

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('projectDropdown');
            const button = e.target.closest('button[onclick="toggleProjectDropdown()"]');
            if (!button && !dropdown.contains(e.target)) dropdown.classList.add('hidden');
        });

        initSortable();
        loadTasks();
    </script>
</body>
</html>