-- MC Builder 论坛数据库结构
-- 
-- 创建时间: 2025-07-23
-- MySQL版本: 5.7+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- 用户表
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_steve.png',
  `join_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `posts_count` int(11) DEFAULT 0,
  `mc_username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_level` enum('新手矿工','石器时代','铁器专家','钻石大师','红石工程师','建筑大师') COLLATE utf8mb4_unicode_ci DEFAULT '新手矿工',
  `last_active` timestamp NULL DEFAULT NULL,
  `signature` text COLLATE utf8mb4_unicode_ci,
  `is_banned` tinyint(1) DEFAULT 0,
  `ban_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_posts_count` (`posts_count`),
  KEY `idx_user_level` (`user_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 论坛分类表
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '📁',
  `sort_order` int(11) DEFAULT 0,
  `thread_count` int(11) DEFAULT 0,
  `post_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 默认分类数据
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `sort_order`) VALUES
(1, '综合讨论', '关于Minecraft的一切话题，新手问答，游戏心得分享', '🏠', 1),
(2, '建筑展示', '展示你的精美建筑作品，分享建造心得和技巧', '🏗️', 2),
(3, '红石科技', '红石电路和自动化装置的设计与分享', '🔴', 3),
(4, '生存攻略', '生存模式的技巧心得，怪物农场，陷阱设计', '⚔️', 4),
(5, '模组推荐', 'MOD介绍和使用教程，模组包推荐', '🔧', 5),
(6, '服务器交流', '服务器宣传和玩家招募，联机游戏组队', '🌐', 6);

-- --------------------------------------------------------

--
-- 帖子表
--

CREATE TABLE `threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `views` int(11) DEFAULT 0,
  `replies` int(11) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `is_hidden` tinyint(1) DEFAULT 0,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `last_reply_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_last_reply` (`last_reply_at`),
  KEY `idx_pinned` (`is_pinned`),
  KEY `idx_hidden` (`is_hidden`),
  CONSTRAINT `threads_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `threads_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 回复表
--

CREATE TABLE `replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_hidden` tinyint(1) DEFAULT 0,
  `reply_to_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `thread_id` (`thread_id`),
  KEY `author_id` (`author_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_hidden` (`is_hidden`),
  KEY `reply_to_id` (`reply_to_id`),
  CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `replies_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `replies_ibfk_3` FOREIGN KEY (`reply_to_id`) REFERENCES `replies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 私信表
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT 0,
  `is_deleted_by_sender` tinyint(1) DEFAULT 0,
  `is_deleted_by_receiver` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_read` (`is_read`),
  CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 在线用户表
--

CREATE TABLE `online_users` (
  `user_id` int(11) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 系统设置表
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 默认设置数据
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'MC Builder 论坛', 'string', '网站名称'),
('site_description', '一个专为Minecraft玩家打造的交流社区', 'string', '网站描述'),
('allow_registration', '1', 'boolean', '是否允许新用户注册'),
('posts_per_page', '20', 'number', '每页显示帖子数量'),
('enable_email_verification', '0', 'boolean', '是否启用邮箱验证'),
('maintenance_mode', '0', 'boolean', '维护模式'),
('default_user_level', '新手矿工', 'string', '新用户默认等级'),
('max_upload_size', '5242880', 'number', '最大上传文件大小(字节)');

-- --------------------------------------------------------

--
-- 访问日志表
--

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip` (`ip_address`),
  CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 创建视图：论坛统计
--

CREATE VIEW `forum_stats` AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE is_banned = 0) as total_users,
    (SELECT COUNT(*) FROM threads WHERE is_hidden = 0) as total_threads,
    (SELECT COUNT(*) FROM replies WHERE is_hidden = 0) as total_replies,
    (SELECT COUNT(*) FROM users WHERE last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE)) as online_users,
    (SELECT username FROM users ORDER BY join_date DESC LIMIT 1) as newest_user;

-- --------------------------------------------------------

--
-- 创建触发器：更新统计数据
--

DELIMITER $$

-- 新帖子时更新分类统计
CREATE TRIGGER `update_category_stats_insert` AFTER INSERT ON `threads`
FOR EACH ROW BEGIN
    UPDATE categories 
    SET thread_count = thread_count + 1, 
        post_count = post_count + 1 
    WHERE id = NEW.category_id;
END$$

-- 删除帖子时更新分类统计
CREATE TRIGGER `update_category_stats_delete` AFTER DELETE ON `threads`
FOR EACH ROW BEGIN
    UPDATE categories 
    SET thread_count = thread_count - 1,
        post_count = post_count - 1 - OLD.replies
    WHERE id = OLD.category_id;
END$$

-- 新回复时更新统计
CREATE TRIGGER `update_thread_stats_insert` AFTER INSERT ON `replies`
FOR EACH ROW BEGIN
    UPDATE threads 
    SET replies = replies + 1,
        last_reply_at = NEW.created_at,
        last_reply_user_id = NEW.author_id
    WHERE id = NEW.thread_id;
    
    UPDATE categories 
    SET post_count = post_count + 1 
    WHERE id = (SELECT category_id FROM threads WHERE id = NEW.thread_id);
END$$

-- 删除回复时更新统计
CREATE TRIGGER `update_thread_stats_delete` AFTER DELETE ON `replies`
FOR EACH ROW BEGIN
    UPDATE threads 
    SET replies = replies - 1
    WHERE id = OLD.thread_id;
    
    UPDATE categories 
    SET post_count = post_count - 1 
    WHERE id = (SELECT category_id FROM threads WHERE id = OLD.thread_id);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- 创建索引优化查询性能
--

-- 复合索引
CREATE INDEX `idx_threads_category_created` ON `threads` (`category_id`, `created_at` DESC);
CREATE INDEX `idx_threads_author_created` ON `threads` (`author_id`, `created_at` DESC);
CREATE INDEX `idx_replies_thread_created` ON `replies` (`thread_id`, `created_at` ASC);

-- 全文搜索索引
ALTER TABLE `threads` ADD FULLTEXT(`title`, `content`);
ALTER TABLE `replies` ADD FULLTEXT(`content`);

-- ----------------------------
-- 问题反馈表
-- ----------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(50) DEFAULT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('未处理','已处理') DEFAULT '未处理'
);

-- ----------------------------
-- 帖子点赞表
-- ----------------------------
CREATE TABLE IF NOT EXISTS thread_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (thread_id, user_id)
);

COMMIT;
