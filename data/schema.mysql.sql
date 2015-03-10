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

--
-- Table structure for table `friends`
--
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
