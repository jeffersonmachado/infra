ALTER TABLE `#__alfcontact` ADD COLUMN `language` CHAR(7) NOT NULL AFTER `access`;
UPDATE `#__alfcontact` SET `language` = '*';