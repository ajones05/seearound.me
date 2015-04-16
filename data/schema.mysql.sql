--
-- Table structure for table `address`
--
ALTER TABLE `address` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

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

--
-- Table structure for table `friends`
--
ALTER TABLE `friends` ENGINE = InnoDB;
ALTER TABLE `friends` CHANGE `sender_id` `sender_id` INT(10) NOT NULL;
ALTER TABLE `friends` CHANGE `reciever_id` `reciever_id` INT(10) NOT NULL;
ALTER TABLE `friends` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `friends` ADD FOREIGN KEY `user_data_fk_2`(`reciever_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `invite_status`
--
ALTER TABLE `invite_status` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `login_status`
--
ALTER TABLE `login_status` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `message`
--
ALTER TABLE `message` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `message` ADD FOREIGN KEY `user_data_fk_2`(`receiver_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `message_reply`
--
ALTER TABLE `message_reply` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `message_reply` ADD FOREIGN KEY `user_data_fk_2`(`receiver_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `message_reply` ADD FOREIGN KEY `user_data_fk_3`(`message_id`) REFERENCES `message`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `news`
--
ALTER TABLE `news` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `news` CHANGE `created_date` `created_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `news` ADD `news_html` LONGTEXT NULL DEFAULT NULL AFTER `news`;
ALTER TABLE `news` CHANGE `news` `news` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `news` CHANGE `news_html` `news_html` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

--
-- Table structure for table `votings`
--
ALTER TABLE `votings` ADD FOREIGN KEY `user_data_fk_1`(`user_id`) REFERENCES `user_data`(`id`) ON DELETE CASCADE;
ALTER TABLE `votings` ADD FOREIGN KEY `user_data_fk_2`(`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE;
ALTER TABLE `votings` ADD FOREIGN KEY `user_data_fk_3`(`comments_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE;

--
-- Table structure for table `facebook_temp_users`
--
ALTER TABLE `facebook_temp_users` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE SET NULL;

--
-- Table structure for table `email_invites`
--
ALTER TABLE `email_invites` ADD FOREIGN KEY `user_data_fk_1`(`sender_id`) REFERENCES `user_data`(`id`) ON DELETE SET NULL;
