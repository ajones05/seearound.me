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
