-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 项目表
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 任务表 - 完全兼容 MySQL 5.5+
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    project_id INT NOT NULL,
    status ENUM('new', 'doing', 'done') DEFAULT 'new',
    priority INT DEFAULT 0,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员用户 (密码: admin123)
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$eImiTXuWVxfM37uY4JANj.5L9fNG6ICQVn41F8MZCEpbdz/oCwCq')
ON DUPLICATE KEY UPDATE username=username;

-- 插入示例项目
INSERT INTO projects (name, description, user_id) VALUES 
('网站重构', '重新设计并开发公司官方网站', 1),
('移动App开发', '开发iOS和Android客户端', 1),
('API服务升级', '升级RESTful API至最新版本', 1)
ON DUPLICATE KEY UPDATE name=name;

-- 插入示例任务
INSERT INTO tasks (title, description, project_id, status, priority) VALUES
('设计首页原型', '完成首页UI/UX设计', 1, 'new', 1),
('实现用户登录', '支持邮箱和手机号登录', 1, 'doing', 2),
('数据库优化', '优化查询性能', 1, 'done', 3),

('iOS框架搭建', '初始化iOS项目结构', 2, 'new', 1),
('Android基础功能', '完成基础页面开发', 2, 'new', 2),
('API对接', '完成RESTful API对接', 2, 'doing', 3),

('接口文档编写', '更新API接口文档', 3, 'new', 1),
('性能测试', '进行压力测试', 3, 'doing', 2),
('部署上线', '完成生产环境部署', 3, 'done', 3);