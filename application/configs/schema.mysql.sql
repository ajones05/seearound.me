--
-- Table structure for table `user_data`
--
ALTER TABLE `user_data` DROP `Profile_image`;
ALTER TABLE `user_data` CHANGE `Network_id` `Network_id` VARCHAR(2000) NULL DEFAULT NULL;
ALTER TABLE `user_data` CHANGE `is_admin` `is_admin` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `user_data` COLLATE 'utf8_general_ci';
ALTER TABLE `user_data` ADD `password_hash` varchar(255) NULL;
ALTER TABLE `user_data` ADD `image_id` INT(11) NULL;
ALTER TABLE `user_data` ADD FOREIGN KEY (`image_id`) REFERENCES `image`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `user_data` CHANGE `image_id` `image_id` int(11) NULL AFTER `id`;
ALTER TABLE `user_data` ADD `address_id` INT(11) NOT NULL AFTER `id`;
ALTER TABLE `user_data` ADD FOREIGN KEY (`address_id`) REFERENCES `address`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
CREATE TRIGGER user_before_del BEFORE DELETE ON user_data
	FOR EACH ROW DELETE FROM address WHERE address.id=OLD.address_id;

--
-- Table structure for table `user_confirm`
--
CREATE TABLE `user_confirm` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `type_id` smallint NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted` TINYINT(1) NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';

--
-- Table structure for table `address`
--
ALTER TABLE `address` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `address` CHANGE `address` `address` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 
ALTER TABLE `address` COLLATE 'utf8_general_ci';
ALTER TABLE `address` CHANGE `Id` `id` int(10) NOT NULL AUTO_INCREMENT;
ALTER TABLE `address` CHANGE `latitude` `latitude` double NOT NULL;
ALTER TABLE `address` CHANGE `longitude` `longitude` double NOT NULL;
ALTER TABLE `address` ADD `street_name` varchar(255) NULL;
ALTER TABLE `address` ADD `street_number` varchar(255) NULL AFTER `street_name`;
ALTER TABLE `address` ADD `city` varchar(255) NULL AFTER `street_number`;
ALTER TABLE `address` ADD `state` varchar(255) NULL AFTER `city`;
ALTER TABLE `address` ADD `country` varchar(255) NULL AFTER `state`;
ALTER TABLE `address` ADD `zip` varchar(255) NULL AFTER `country`;
ALTER TABLE `address` DROP FOREIGN KEY `address_ibfk_1`;
ALTER TABLE `address` DROP `user_id`;

--
-- Table structure for table `user_profile`
--
ALTER TABLE `user_profile` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `comments`
--
ALTER TABLE `comments` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `comments` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `comments` CHANGE `isdeleted` `isdeleted` INT(2) NOT NULL DEFAULT '0';
ALTER TABLE `comments` CHANGE `comment` `comment` VARCHAR(65535) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `comments` ADD FOREIGN KEY `comments_fk_2`(`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE;
ALTER TABLE `comments` ADD `notify` TINYINT NOT NULL DEFAULT '0' AFTER `updated_at`;
ALTER TABLE `comments` ADD `is_read` BIT NOT NULL DEFAULT 0 AFTER `notify`;
ALTER TABLE `comments` CHANGE `is_read` `is_read` TINYINT(1) NOT NULL DEFAULT '0';

--
-- Table structure for table `friends`
--
ALTER TABLE `friends` ENGINE = InnoDB;
ALTER TABLE `friends` CHANGE `sender_id` `sender_id` INT(10) NOT NULL;
ALTER TABLE `friends` CHANGE `reciever_id` `reciever_id` INT(10) NOT NULL;
ALTER TABLE `friends` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `friends` ADD FOREIGN KEY `user_data_fk_2`(`reciever_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `friends` CHANGE `status` `status` SMALLINT NOT NULL DEFAULT '0';
ALTER TABLE `friends` ADD `notify` TINYINT(1) NOT NULL DEFAULT '0' AFTER `udate`;
ALTER TABLE `friends` DROP `udate`;
ALTER TABLE `friends` DROP `cdate`;

--
-- Table structure for table `friend_log`
--
CREATE TABLE `friend_log` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`friend_id` INT(11) NOT NULL,
	`user_id` INT(11) NOT NULL,
	`status_id` SMALLINT NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (`friend_id`) REFERENCES `friends`(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

--
-- Table structure for table `invite_status`
--
ALTER TABLE `invite_status` ENGINE = InnoDB;
ALTER TABLE `invite_status` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `login_status`
--
ALTER TABLE `login_status` ENGINE = InnoDB;
ALTER TABLE `login_status` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `login_status` CHANGE `login_time` `login_time` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `login_status` CHANGE `logout_time` `logout_time` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `login_status` ADD `visit_time` TIMESTAMP NULL DEFAULT NULL AFTER `logout_time`;

--
-- Table structure for table `news`
--
ALTER TABLE `news` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `news` CHANGE `created_date` `created_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `news` ADD `news_html` LONGTEXT NULL DEFAULT NULL AFTER `news`;
ALTER TABLE `news` CHANGE `news` `news` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `news` CHANGE `news_html` `news_html` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `news` CHANGE `images` `image` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `news` DROP `news_html`;
ALTER TABLE `news` ADD `vote` INT(11) NOT NULL DEFAULT '0' AFTER `Address`;
ALTER TABLE `news` DROP `score`;
ALTER TABLE `news` ADD `comment` INT(11) NOT NULL DEFAULT '0' AFTER `vote`; 
ALTER TABLE `news` DROP `image`;
ALTER TABLE `news` ADD `image_id` INT(11) NULL;
ALTER TABLE `news` ADD FOREIGN KEY (`image_id`) REFERENCES `image`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `news` ADD `address_id` INT(11) NULL AFTER `id`;
ALTER TABLE `news` ADD FOREIGN KEY (`address_id`) REFERENCES `address`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
CREATE TRIGGER news_before_del BEFORE DELETE ON news
	FOR EACH ROW DELETE FROM address WHERE address.id=OLD.address_id;
ALTER TABLE `news`
	DROP `latitude`,
	DROP `longitude`,
	DROP `Address`;

--
-- Table structure for table `news_link`
--
CREATE TABLE `news_link` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`news_id` INT(11) NOT NULL,
	`link` VARCHAR(2000) NOT NULL,
	`title` VARCHAR(255) NULL,
	`description` VARCHAR(255) NULL,
	`author` VARCHAR(255) NULL,
	`image` VARCHAR(2000) NULL,
	`image_width` INT(10) NULL,
	`image_height` INT(10) NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY `news_link_fk_1`(`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `news_link` DROP `image`;
ALTER TABLE `news_link` DROP `image_width`;
ALTER TABLE `news_link` DROP `image_height`;
ALTER TABLE `news_link` ADD `image_id` INT(11) NULL;
ALTER TABLE `news_link` ADD FOREIGN KEY (`image_id`) REFERENCES `image`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Table structure for table `image`
--
CREATE TABLE `image` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`path` VARCHAR(2000) NULL,
	`width` INT(10) NULL,
	`height` INT(10) NULL,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

--
-- Table structure for table `image_thumb`
--
CREATE TABLE `image_thumb` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`image_id` INT(11) NOT NULL,
	`path` VARCHAR(2000) NULL,
	`width` INT(10) NULL,
	`height` INT(10) NULL,
	`thumb_width` INT(10) NULL,
	`thumb_height` INT(10) NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`image_id`) REFERENCES `image`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

--
-- Table structure for table `votings`
--
ALTER TABLE `votings` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `votings` ADD FOREIGN KEY `user_data_fk_2`(`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE;
ALTER TABLE `votings` ADD FOREIGN KEY `user_data_fk_3`(`comments_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE;
ALTER TABLE `votings` DROP FOREIGN KEY votings_ibfk_4;
ALTER TABLE `votings` DROP `comments_id`;
ALTER TABLE `votings` DROP `comments_count`;
ALTER TABLE `votings` DROP `type`;
ALTER TABLE `votings` DROP `news_count`;
ALTER TABLE `votings` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `news_id`;
ALTER TABLE `votings` ADD `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`;
ALTER TABLE `votings` ADD `vote` TINYINT NOT NULL DEFAULT '1' AFTER `id`;
ALTER TABLE `votings` CHANGE `vote` `vote` TINYINT(4) NOT NULL;
ALTER TABLE `votings` ADD `canceled` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `votings` ADD `is_read` BIT NOT NULL DEFAULT 0;
ALTER TABLE `votings` CHANGE `is_read` `is_read` TINYINT(1) NOT NULL DEFAULT '0';

--
-- Table structure for table `facebook_temp_users`
--
ALTER TABLE `facebook_temp_users` ENGINE = InnoDB;
ALTER TABLE `facebook_temp_users` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `email_invites`
--
ALTER TABLE `email_invites` ENGINE = InnoDB;
ALTER TABLE `email_invites` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE SET NULL;

--
-- Table structure for table `comment_user_notify`
--
CREATE TABLE `comment_user_notify` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`comment_id` INT(11) NOT NULL,
	`user_id` INT(11) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	FOREIGN KEY `comment_user_notify_ibfk_1`(`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
	FOREIGN KEY `comment_user_notify_ibfk_2`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

--
-- Table structure for table `conversation`
--
CREATE TABLE `conversation` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`from_id` INT(11) NOT NULL,
	`to_id` INT(11) NOT NULL,
	`subject` VARCHAR(250) NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`status` TINYINT(1) NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`from_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`to_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

--
-- Table structure for table `conversation_message`
--
CREATE TABLE `conversation_message` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`conversation_id` INT(11) NOT NULL,
	`from_id` INT(11) NOT NULL,
	`to_id` INT(11) NOT NULL,
	`body` VARCHAR(250) NULL,
	`is_read` TINYINT(1) NOT NULL,
	`is_first` TINYINT(1) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`status` TINYINT(1) NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`conversation_id`) REFERENCES `conversation`(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`from_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`to_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
