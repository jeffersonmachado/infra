DROP TABLE IF EXISTS `#__com_blogfactory_blogs`;
CREATE TABLE `#__com_blogfactory_blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `photo` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `notification_report` tinyint(1) NOT NULL,
  `notification_comment` tinyint(1) NOT NULL,
  `export` mediumtext NOT NULL,
  `params` mediumtext NOT NULL,
  `published` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_bookmarks`;
CREATE TABLE `#__com_blogfactory_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `ordering` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_comments`;
CREATE TABLE `#__com_blogfactory_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `text` mediumtext NOT NULL,
  `unread` tinyint(1) NOT NULL,
  `votes_up` int(11) NOT NULL,
  `votes_down` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `approved` tinyint(1) NOT NULL,
  `reported` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_followers`;
CREATE TABLE `#__com_blogfactory_followers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_blog_id` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_subscriptions`;
CREATE TABLE `#__com_blogfactory_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__com_blogfactory_notifications`;
CREATE TABLE `#__com_blogfactory_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `groups` mediumtext NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `published` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_posts`;
CREATE TABLE `#__com_blogfactory_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `visibility` tinyint(1) NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `metadata` mediumtext NOT NULL,
  `comments` tinyint(1) NOT NULL,
  `pingbacks` tinyint(1) NOT NULL,
  `sent_pings` tinyint(1) NOT NULL,
  `sent_notifications` tinyint(1) NOT NULL,
  `sent_pingbacks` mediumtext NOT NULL,
  `published` tinyint(1) NOT NULL,
  `publish_up` datetime NOT NULL,
  `publish_down` datetime NOT NULL,
  `votes_up` int(11) NOT NULL,
  `votes_down` int(11) NOT NULL,
  `archived` tinyint(1) NOT NULL,
  `approved` tinyint(1) NOT NULL,
  `exported_to_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_blog_id` (`blog_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_post_tag_map`;
CREATE TABLE `#__com_blogfactory_post_tag_map` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  KEY `idx_post_id` (`post_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_profiles`;
CREATE TABLE `#__com_blogfactory_profiles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `subscription_notifications` tinyint(1) NOT NULL,
  `avatar_source` varchar(20) NOT NULL,
  `avatar` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_reports`;
CREATE TABLE `#__com_blogfactory_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_user_id` int(11) NOT NULL,
  `item` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` mediumtext NOT NULL,
  `read` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_revisions`;
CREATE TABLE `#__com_blogfactory_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_tags`;
CREATE TABLE `#__com_blogfactory_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__com_blogfactory_votes`;
CREATE TABLE `#__com_blogfactory_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `#__com_blogfactory_bookmarks` (`id`, `title`, `link`, `thumbnail`, `ordering`, `published`) VALUES
(1,	'Digg',	'http://digg.com/submit?url=%%url%%&title=%%title%%',	'1-digg.png',	4,	1),
(2,	'Reddit',	'http://www.reddit.com/submit?url=%%url%%&title=%%title%%',	'2-reddit.png',	2,	1),
(3,	'Facebook',	'http://www.facebook.com/share.php?u=%%url%%',	'3-facebook.png',	3,	1),
(4,	'Twitter',	'http://twitter.com/home?status=%%title%%%0A%0A %%url%%',	'4-twitter.png',	5,	1);
