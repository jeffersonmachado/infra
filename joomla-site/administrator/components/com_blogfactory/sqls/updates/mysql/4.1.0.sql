ALTER TABLE `#__com_blogfactory_blogs` CHANGE `import` `export` mediumtext NOT NULL AFTER `notification_comment`;

ALTER TABLE `#__com_blogfactory_posts` CHANGE `import_id` `exported_to_id` int(11) NOT NULL AFTER `approved`;
