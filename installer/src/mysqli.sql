
DROP TABLE IF EXISTS `{PREFIX}categories`;

-- split --

CREATE TABLE `{PREFIX}categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `url_name` varchar(200) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}categories` (`id`, `name`, `url_name`, `description`) VALUES
  (1, 'Uncategorized', 'uncategorized', 'Uncategorized');


-- split --

DROP TABLE IF EXISTS `{PREFIX}comments`;

-- split --

CREATE TABLE `{PREFIX}comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `author` varchar(50) DEFAULT NULL,
  `author_email` varchar(100) DEFAULT NULL,
  `author_ip` varchar(100) NOT NULL,
  `content` text,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modded` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- split --

DROP TABLE IF EXISTS `{PREFIX}contacts`;

-- split --

CREATE TABLE `{PREFIX}contacts` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `sender_ip` varchar(50) NOT NULL,
  `message` varchar(200) NOT NULL,
  `send_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- split --

DROP TABLE IF EXISTS `{PREFIX}groups`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}groups` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `description` varchar(100) NOT NULL,
  `protected` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}groups` (`id`, `name`, `description`, `protected`) VALUES
  (1, 'admin', 'Administrator', 1),
  (2, 'members', 'General User', 1),
  (3, 'contributors', 'Contributor', 1),
  (4, 'editors', 'Editor', 1);

-- split --

DROP TABLE IF EXISTS `{PREFIX}groups_perms`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}groups_perms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perms_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


-- split --

DROP TABLE IF EXISTS `{PREFIX}group_permissions`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(150) NOT NULL,
  `protected` int(1) NOT NULL DEFAULT '0',
  `form_name` varchar(50) NOT NULL,
  `display_order` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}group_permissions` (`id`, `name`, `description`, `protected`, `form_name`, `display_order`) VALUES
  (1, 'users', 'Users', 1, '', 14),
  (2, 'posts', 'Posts', 1, '', 9),
  (3, 'pages', 'Pages', 1, '', 8),
  (4, 'links', 'Links', 1, '', 5),
  (5, 'social', 'Social', 1, '', 11),
  (6, 'comments', 'Comments', 1, '', 3),
  (7, 'navigation', 'Navigation', 1, '', 7),
  (8, 'themes', 'Themes', 1, '', 12),
  (9, 'settings', 'Settings', 1, '', 10),
  (10, 'updates', 'Updates', 1, '', 13),
  (11, 'dashboard', 'Dashboard', 1, '', 1),
  (12, 'cats', 'Categories', 1, '', 2),
  (13, 'lang', 'Language', 1, '', 4),
  (14, 'media', 'Media', 1, '', 6),
  (15, 'widgets', 'Widgets', 1, '', 15);

-- split --

DROP TABLE IF EXISTS `{PREFIX}languages`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(100) DEFAULT NULL,
  `abbreviation` varchar(7) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `author_website` varchar(255) NOT NULL,
  `is_default` enum('0','1') DEFAULT NULL,
  `is_avail` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}languages` (`id`, `language`, `abbreviation`, `author`, `author_website`, `is_default`, `is_avail`) VALUES
  (1, 'english', 'en', 'Enliven Applications', 'https://pubvana.com', '1', 1),
  (2, 'indonesian', 'id', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (3, 'arabic', 'ar', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (4, 'bulgarian', 'bg', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (5, 'czech', 'cs', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (6, 'french', 'fr', 'Enliven Applications', 'https://pubvana.com', '0', 1),
  (7, 'hungarian', 'hu', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (8, 'italian', 'it', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (9, 'latvian', 'lv', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (10, 'norwegian', 'no', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (11, 'polish', 'pl', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (12, 'portuguese', 'pt', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (13, 'simplified-chinese', 'zh-hans', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (14, 'slovak', 'sk', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (15, 'slovenian', 'sl', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (16, 'spanish', 'es', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (17, 'traditional-chinese', 'zh-hant', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (18, 'turkish', 'tr', 'Enliven Applications', 'https://pubvana.com', '0', 0),
  (19, 'ukranian', 'uk', 'Enliven Applications', 'https://pubvana.com', '0', 0);

-- split --

DROP TABLE IF EXISTS `{PREFIX}links`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `target` varchar(20) DEFAULT '_blank',
  `description` varchar(100) DEFAULT NULL,
  `visible` enum('yes','no') DEFAULT 'yes',
  `position` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}links` (`id`, `name`, `url`, `target`, `description`, `visible`, `position`) VALUES
  (1, 'Pubvana', 'http://pubvana.org', '_blank', 'Pubvana', 'yes', 3),
  (2, 'Pubvana Hosting', 'http://pubvana.com', '_blank', 'Pubvana Hosting', 'yes', 2),
  (3, 'Enliven Applications', 'http://enlivenapp.com', '_blank', 'Enliven Applications', 'yes', 1);


-- split --

DROP TABLE IF EXISTS `{PREFIX}login_attempts`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}login_attempts` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `login` varchar(100) NOT NULL,
  `time` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- split --

DROP TABLE IF EXISTS `{PREFIX}navigation`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}navigation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `external` enum('0','1') NOT NULL DEFAULT '0',
  `position` varchar(100) DEFAULT NULL,
  `parent_id` int(5) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}navigation` (`id`, `title`, `description`, `url`, `external`, `position`, `parent_id`) VALUES
  (1, 'Home', 'Home', '', '0', '0', '0'),
  (2, 'Welcome (page)', 'Welcome Page', 'pages/', '0', '1', '0');

-- split --

DROP TABLE IF EXISTS `{PREFIX}notifications`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_address` varchar(200) NOT NULL,
  `verify_code` varchar(200) NOT NULL,
  `verified` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- split --

DROP TABLE IF EXISTS `{PREFIX}pages`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `url_title` varchar(200) DEFAULT NULL,
  `author` int(11) DEFAULT '0',
  `date` date NOT NULL,
  `content` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_home` int(1) NOT NULL DEFAULT '0',
  `meta_title` varchar(200) NOT NULL,
  `meta_keywords` varchar(200) NOT NULL,
  `meta_description` varchar(200) NOT NULL,
  `date_modified` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}pages` (`id`, `title`, `url_title`, `author`, `date`, `content`, `status`, `is_home`, `meta_title`, `meta_keywords`, `meta_description`, `date_modified`) VALUES
  (1, 'Welcome to Pubvana', 'welcome-to-pubvana', 1, '2016-12-22', '### Welcome\r\n\r\nIf you can see this page, Pubvana was successfully installed.\r\n\r\nIf you need help, don\'t hesitate and visit the Pubvana website.\r\n\r\n*Since this is just an example post, feel free to delete it.*', 'active', 1, 'Pubvana Home Page', 'Pubvana, Blog, CMS, Website, personal, small business', 'The Pubvana Homepage', NULL);


-- split --

DROP TABLE IF EXISTS `{PREFIX}posts`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` int(11) NOT NULL DEFAULT '0',
  `date_posted` date DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `url_title` varchar(200) NOT NULL,
  `excerpt` text NOT NULL,
  `content` longtext NOT NULL,
  `feature_image` varchar(255) DEFAULT NULL,
  `allow_comments` enum('0','1') NOT NULL DEFAULT '1',
  `sticky` enum('0','1') NOT NULL DEFAULT '0',
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `meta_title` varchar(200) NOT NULL,
  `meta_keywords` varchar(200) NOT NULL,
  `meta_description` varchar(200) NOT NULL,
  `date_modified` date DEFAULT NULL,
  `post_count` int(20) DEFAULT '0',
  `featured` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}posts` (`id`, `author`, `date_posted`, `title`, `url_title`, `excerpt`, `content`, `feature_image`, `allow_comments`, `sticky`, `status`, `meta_title`, `meta_keywords`, `meta_description`, `date_modified`, `post_count`, `featured`) VALUES
  (1, 1, '2018-03-16', 'Welcome to Pubvana', 'welcome-to-pubvana', 'Congratulations! If you can see this page, Pubvana was successfully installed. If you need help, don\'t hesitate and visit the Pubvana website.\r\n', '#### Congratulations!\r\n\r\nIf you can see this page, Pubvana was successfully installed.\r\n\r\nIf you need help, don\'t hesitate and visit the Pubvana website.\r\n\r\n*Since this is just an example post, feel free to delete it.*', NULL ,'1', '0', 'published', 'Pubvana Home Page', 'Pubvana, Blog, CMS, Website, personal, small business', 'The Pubvana Homepage', NULL, '0', '0');


-- split --

DROP TABLE IF EXISTS `{PREFIX}posts_to_categories`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}posts_to_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}posts_to_categories` (`id`, `post_id`, `category_id`) VALUES
  (1, 1, 1);

-- split --

DROP TABLE IF EXISTS `{PREFIX}redirects`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `old_slug` varchar(200) NOT NULL,
  `new_slug` varchar(200) NOT NULL,
  `type` varchar(4) NOT NULL DEFAULT 'post',
  `code` varchar(3) NOT NULL DEFAULT '301',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- split --

DROP TABLE IF EXISTS `{PREFIX}settings`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}settings` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `tab` varchar(50) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `options` varchar(200) NOT NULL,
  `required` int(1) NOT NULL DEFAULT '1',
  `order_by` int(5) DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- split --

  INSERT INTO `{PREFIX}settings` (`name`, `value`, `tab`, `field_type`, `options`, `required`, `order_by`) VALUES
('admin_email', '{USER-EMAIL}', 'email', 'text', '', 1, 0),
('allow_comments', '1', 'comments', 'dropdown', '1=yes|0=no', 1, 1),
('allow_registrations', 'true', 'users', 'dropdown', 'true=yes|false=no', 1, 0),
('base_controller', 'blog', 'general', 'dropdown', 'blog=blog|pages=pages', 1, 0),
('blog_description', 'Blogging and Small business CMS', 'general', 'text', '', 0, 0),
('category_list_limit', '10', 'limits', 'dropdown', '10=10|20=20|30=30', 1, 0),
('comment_system', 'local', 'comments', 'dropdown', 'local=Local|fb=Facebook', 1, 0),
('email_activation', 'true', 'users', 'dropdown', 'true=yes|false=no', 1, 0),
('facebook_id', '', 'comments', 'text', '', 0, 4),
('gAnalyticsPropId', '', 'google', 'text', '', 0, 0),
('links_per_box', '10', 'limits', 'dropdown', '10=10|20=20|30=30', 1, 0),
('mail_protocol', 'mail', 'email', 'dropdown', 'mail=mail|smtp=smtp|sendmail=sendmail', 1, 0),
('manual_activation', 'false', 'users', 'dropdown', 'true=yes|false=no', 1, 0),
('mod_non_user_comments', '1', 'comments', 'dropdown', '1=yes|0=no', 1, 2),
('mod_user_comments', '0', 'comments', 'dropdown', '1=yes|0=no', 1, 3),
('months_per_archive', '10', 'limits', 'dropdown', '10=10|20=20|30=30', 1, 0),
('posts_per_page', '10', 'limits', 'dropdown', '10=10|20=20|30=30', 1, 0),
('recaptcha_private_key', '', 'google', 'text', '', 0, 0),
('recaptcha_site_key', '', 'google', 'text', '', 0, 0),
('sendmail_path', '/usr/sbin/sendmail', 'email', 'text', '', 0, 0),
('server_email', '{USER-EMAIL}', 'email', 'text', '', 1, 0),
('site_name', 'Pubvana', 'general', 'text', '', 1, 0),
('smtp_crypto', 'tls', 'email', 'dropdown', 'tls=TLS|ssl=SSL', 0, 0),
('smtp_host', '', 'email', 'text', '', 0, 0),
('smtp_pass', '', 'email', 'text', '', 0, 0),
('smtp_port', '', 'email', 'text', '', 0, 0),
('smtp_user', '', 'email', 'text', '', 0, 0),
('use_honeypot', '0', 'captcha', 'dropdown', '1=yes|0=no', 1, 0),
('use_recaptcha', '0', 'google', 'dropdown', '1=yes|0=no', 1, 0);

-- split --

DROP TABLE IF EXISTS `{PREFIX}sidebar`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}sidebar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `file` varchar(100) NOT NULL,
  `status` enum('enabled','disabled') NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}sidebar` (`id`, `title`, `file`, `status`, `position`) VALUES
  (1, 'Search', 'search', 'enabled', '1'),
  (2, 'Archive', 'archive', 'enabled', '2'),
  (3, 'Categories', 'categories', 'enabled', '3'),
  (4, 'Tag_cloud', 'tag_cloud', 'enabled', '4'),
  (5, 'Feeds', 'feeds', 'enabled', '5'),
  (6, 'Links', 'links', 'enabled', '6'),
  (7, 'Other', 'other', 'enabled', '7');

-- split --

DROP TABLE IF EXISTS `{PREFIX}social`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}social` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL,
  `enabled` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}social` (`id`, `name`, `url`, `enabled`) VALUES
  (1, 'Facebook', NULL, 0),
  (2, 'Twitter', NULL, 0);

-- split --

DROP TABLE IF EXISTS `{PREFIX}templates`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(200) NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `author_email` varchar(100) NOT NULL,
  `path` varchar(100) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `is_default` enum('0','1') DEFAULT '1',
  `is_active` varchar(1) NOT NULL DEFAULT '0',
  `is_admin` varchar(1) NOT NULL DEFAULT '0',
  `version` varchar(10) NOT NULL DEFAULT '1.0.0',
  `has_options` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}templates` (`id`, `name`, `description`, `author`, `author_email`, `path`, `image`, `is_default`, `is_active`, `is_admin`, `version`, `has_options`) VALUES
(1, 'Default', 'The default theme for Pubvana', 'Enliven Applications', 'info@pubvana.org', 'default', 'default.png', '1', '1', '0', '1.0.0', 1),
(2, 'Default Admin', 'The default Admin theme for Pubvana', 'Enliven Applications', 'info@pubvana.org', 'default_admin', 'default_admin.png', '1', '1', '1', '1.0.0', 0);

-- split --

DROP TABLE IF EXISTS `{PREFIX}theme_options`;

-- split --

CREATE TABLE `{PREFIX}theme_options` (
  `id` int(11) UNSIGNED NOT NULL,
  `theme_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(200) NOT NULL,
  `type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}theme_options` (`id`, `theme_id`, `name`, `value`, `type`) VALUES
(1, 1, 'main_background', 'bg_suburb.jpg', 'images');

-- split --

DROP TABLE IF EXISTS `{PREFIX}users`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}users` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `activation_code` varchar(40) DEFAULT NULL,
  `forgotten_password_code` varchar(40) DEFAULT NULL,
  `forgotten_password_time` int(11) UNSIGNED DEFAULT NULL,
  `remember_code` varchar(40) DEFAULT NULL,
  `created_on` int(11) UNSIGNED NOT NULL,
  `last_login` int(11) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) UNSIGNED DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO {PREFIX}users (`id`, `ip_address`, `username`, `password`, `salt`, `email`, `activation_code`, `forgotten_password_code`, `forgotten_password_time`, `remember_code`, `created_on`, `last_login`, `active`, `first_name`, `last_name`, `company`, `phone`) VALUES
  (1, '127.0.0.1', '{USER-NAME}', '{PASSWORD}', '{SALT}', '{USER-EMAIL}', NULL, NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, '{FIRST-NAME}', '{LAST-NAME}', NULL, NULL);

-- split --

DROP TABLE IF EXISTS `{PREFIX}users_groups`;

-- split --

CREATE TABLE IF NOT EXISTS `{PREFIX}users_groups` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `group_id` mediumint(8) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_{PREFIX}users_groups_users1_idx` (`user_id`),
  KEY `fk_{PREFIX}users_groups_groups1_idx` (`group_id`),
  CONSTRAINT `uc_{PREFIX}users_groups` UNIQUE (`user_id`, `group_id`),
  CONSTRAINT `fk_{PREFIX}users_groups_users1` FOREIGN KEY (`user_id`) REFERENCES `{PREFIX}users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_{PREFIX}users_groups_groups1` FOREIGN KEY (`group_id`) REFERENCES `{PREFIX}groups` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}users_groups` (`id`, `user_id`, `group_id`) VALUES
  (1, 1, 1);

-- split --

DROP TABLE IF EXISTS `{PREFIX}widgets`;

-- split --

CREATE TABLE `{PREFIX}widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(200) NOT NULL,
  `author` varchar(50) NOT NULL,
  `author_email` varchar(50) NOT NULL,
  `author_website` varchar(50) NOT NULL,
  `version` varchar(50) NOT NULL,
  `slug` varchar(30) DEFAULT NULL,
  `options` text,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- split --


INSERT INTO `{PREFIX}widgets` (`id`, `name`, `description`, `author`, `author_email`, `author_website`, `version`, `slug`, `options`, `content`) VALUES
(1, 'HTML', 'Add arbitrary HTML to your site', 'Enliven Applications', 'info@pubvana.org', 'https://pubvana.com', '1.0.0', 'html', 'a:0:{}', ''),
(2, 'Pubvana Links', 'Add Links to your website. Admin -> Links', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.0', 'pubvana_links', 'a:1:{s:8:\"numlinks\";a:5:{s:10:\"field_type\";s:8:\"dropdown\";s:7:\"default\";s:1:\"5\";s:5:\"label\";s:15:\"Number of Links\";s:9:\"help_text\";s:55:\"Choose the maximum number of links to display publicly.\";s:7:\"options\";s:18:\"1|3|5|7|9|10|15|20\";}}', ''),
(3, 'Pubvana Archives', 'Add Archived (Older) posts to your website.', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.0', 'pubvana_archives', 'a:1:{s:11:\"numarchives\";a:5:{s:10:\"field_type\";s:8:\"dropdown\";s:7:\"default\";s:1:\"5\";s:5:\"label\";s:15:\"Number of Links\";s:9:\"help_text\";s:56:\"Choose the maximum number of months to display publicly.\";s:7:\"options\";s:18:\"1|3|5|7|9|10|15|20\";}}', ''),
(4, 'Pubvana Categories', 'Display blog categories on your website. Admin -> Categories', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.0', 'pubvana_categories', 'a:1:{s:7:\"numcats\";a:5:{s:10:\"field_type\";s:8:\"dropdown\";s:7:\"default\";s:1:\"5\";s:5:\"label\";s:20:\"Number of Categories\";s:9:\"help_text\";s:60:\"Choose the maximum number of categories to display publicly.\";s:7:\"options\";s:18:\"1|3|5|7|9|10|15|20\";}}', ''),
(5, 'Pubvana Recent Posts', 'Show the most recent posts of your blog. Admin -> Posts', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.0', 'pubvana_recent_posts', 'a:1:{s:8:\"numposts\";a:5:{s:10:\"field_type\";s:8:\"dropdown\";s:7:\"default\";s:1:\"5\";s:5:\"label\";s:15:\"Number of Posts\";s:9:\"help_text\";s:55:\"Choose the maximum number of posts to display publicly.\";s:7:\"options\";s:18:\"1|3|5|7|9|10|15|20\";}}', ''),
(6, 'Login Widget', 'Widget to login to your website', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.1', 'pubvana_login', 'a:5:{s:14:\"lang_login_btn\";a:4:{s:10:\"field_type\";s:4:\"text\";s:7:\"default\";s:5:\"Login\";s:5:\"label\";s:12:\"Login Button\";s:9:\"help_text\";s:46:\"Change the language used for the login button.\";}s:17:\"lang_remember_btn\";a:4:{s:10:\"field_type\";s:4:\"text\";s:7:\"default\";s:11:\"Remember Me\";s:5:\"label\";s:16:\"Remember Me Text\";s:9:\"help_text\";s:50:\"Change the language used for Remember Me checkbox.\";}s:15:\"lang_forgot_btn\";a:4:{s:10:\"field_type\";s:4:\"text\";s:7:\"default\";s:21:\"Forgot Your Password?\";s:5:\"label\";s:25:\"Forgotten Password Button\";s:9:\"help_text\";s:57:\"Change the language used for the Forgotten Password link.\";}s:16:\"lang_place_ident\";a:4:{s:10:\"field_type\";s:4:\"text\";s:7:\"default\";s:15:\"you@example.com\";s:5:\"label\";s:20:\"Identity Placeholder\";s:9:\"help_text\";s:51:\"Change the language used for the Email Placeholder.\";}s:15:\"lang_pass_ident\";a:4:{s:10:\"field_type\";s:4:\"text\";s:7:\"default\";s:8:\"Password\";s:5:\"label\";s:20:\"Password Placeholder\";s:9:\"help_text\";s:54:\"Change the language used for the Password Placeholder.\";}}', ''),
(7, 'Pubvana Popular Posts', 'Show the most popular posts of your blog. Admin -> Posts', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.0', 'pubvana_popular_posts', 'a:1:{s:8:\"numposts\";a:5:{s:10:\"field_type\";s:8:\"dropdown\";s:7:\"default\";s:1:\"5\";s:5:\"label\";s:15:\"Number of Posts\";s:9:\"help_text\";s:55:\"Choose the maximum number of posts to display publicly.\";s:7:\"options\";s:18:\"1|3|5|7|9|10|15|20\";}}', ''),
(8, 'Featured Post', 'Shows the featured posts of your blog. Set featured post in Admin -> Posts', 'Enliven Applications', 'info@pubvana.org', 'https://enlivenapp.com', '1.0.0', 'pubvana_featured_post', 'a:0:{}', '');


-- split --


DROP TABLE IF EXISTS `{PREFIX}widget_areas`;

-- split --

CREATE TABLE `{PREFIX}widget_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `theme_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- split --

INSERT INTO `{PREFIX}widget_areas` (`id`, `name`, `theme_id`) VALUES
(1, 'home_left', 1),
(2, 'home_middle_left', 1),
(3, 'home_middle_right', 1),
(4, 'home_right', 1);

-- split --

DROP TABLE IF EXISTS `{PREFIX}widget_instances`;

-- split --

CREATE TABLE `{PREFIX}widget_instances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `widget_area_id` int(11) NOT NULL,
  `widget_id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `show_title` int(1) NOT NULL DEFAULT '1',
  `options` text,
  `content` text,
  `order` int(1) NOT NULL,
  `active` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
