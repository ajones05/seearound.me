--
-- Insert data for table `address`
--
INSERT INTO `address` (`id`, `user_id`, `address`, `latitude`, `longitude`, `street_name`, `street_number`, `city`, `state`, `country`, `zip`)
	VALUES ('1', NULL, NULL, '37.2718745', '-119.2704153', 'Stump Springs Road', NULL, 'Lakeshore', 'CA', 'USA', '93634');

--
-- Insert data for table `votings`
--
DELETE FROM `votings` WHERE `news_count` = 0;

--
-- Insert data for table `image`
--
INSERT INTO `image` (`id`, `path`, `width`, `height`)
	VALUES ('1', 'www/images/img-prof200x200.jpg', '200', '200');

--
-- Insert data for table `image_thumb`
--
INSERT INTO `image_thumb` (`image_id`, `path`, `width`, `height`, `thumb_width`, `thumb_height`)
	VALUES ('1', 'uploads/4yzgqvirgl.jpg', '320', '320', '320', '320');

	--
	-- Insert data for table `setting`
	--
INSERT INTO `setting` (`created_at`, `updated_at`, `name`, `value`, `description`) VALUES
	(NOW(), NOW(), 'mediaversion', '', 'Media version /assets-XXX/media-file.path');
