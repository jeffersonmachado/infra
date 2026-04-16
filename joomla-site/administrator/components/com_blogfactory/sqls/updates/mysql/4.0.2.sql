ALTER TABLE `#__com_blogfactory_followers` DROP `notification`;

DROP TABLE IF EXISTS `#__com_blogfactory_subscriptions`;
CREATE TABLE `#__com_blogfactory_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
