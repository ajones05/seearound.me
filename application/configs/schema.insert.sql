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
	(NOW(), NOW(), 'mediaversion', '', 'Media version /assets-XXX/media-file.path'),
	(NOW(), NOW(), 'server_requestScheme', '', '$_SERVER["request_scheme"]'),
	(NOW(), NOW(), 'server_httpHost', '', '$_SERVER["http_host"]'),
	(NOW(), NOW(), 'fb_apiVersion', '', 'Facebook application API version'),
	(NOW(), NOW(), 'fb_appId', '', 'Facebook application ID'),
	(NOW(), NOW(), 'fb_appSecret', '', 'Facebook application secret'),
	(NOW(), NOW(), 'fb_oaklandPageId', '', 'Facebook Oakland page ID'),
	(NOW(), NOW(), 'fb_oaklandAccessToken', '', 'Facebook Oakland page access token'),
	(NOW(), NOW(), 'twitter_oaklandToken', '', 'Twitter Oakland page token'),
	(NOW(), NOW(), 'twitter_oaklandTokenSecret', '', 'Twitter Oakland page token secret'),
	(NOW(), NOW(), 'twitter_oaklandApiKey', '', 'Twitter Oakland page api key'),
	(NOW(), NOW(), 'twitter_oaklandApiSecret', '', 'Twitter Oakland page api secret'),
	(NOW(), NOW(), 'twitter_berkeleyToken', '', 'Twitter Berkeley page token'),
	(NOW(), NOW(), 'twitter_berkeleyTokenSecret', '', 'Twitter Berkeley page token secret'),
	(NOW(), NOW(), 'twitter_berkeleyApiKey', '', 'Twitter Berkeley page api key'),
	(NOW(), NOW(), 'twitter_berkeleyApiSecret', '', 'Twitter Berkeley page api secret'),
	(NOW(), NOW(), 'twitter_sfToken', '', 'Twitter San Srancisco page token'),
	(NOW(), NOW(), 'twitter_sfTokenSecret', '', 'Twitter San Srancisco page token secret'),
	(NOW(), NOW(), 'twitter_sfApiKey', '', 'Twitter San Srancisco page api key'),
	(NOW(), NOW(), 'twitter_sfApiSecret', '', 'Twitter San Srancisco page api secret'),
	(NOW(), NOW(), 'post_bodyMaxLength', '', 'Max length for post body'),
	(NOW(), NOW(), 'fb_sfPageId', '', 'Facebook San Srancisco page ID'),
	(NOW(), NOW(), 'fb_sfAccessToken', '', 'Facebook San Srancisco page access token'),
	(NOW(), NOW(), 'fb_berkeleyPageId', '', 'Facebook Berkeley page ID'),
	(NOW(), NOW(), 'fb_berkeleyAccessToken', '', 'Facebook Berkeley page access token'),
	(NOW(), NOW(), 'google_analyticsAccount', '', 'Google analytics page property ID'),
	(NOW(), NOW(), 'google_mapsKey', '', 'Google maps API key https://developers.google.com/maps/documentation/javascript/get-api-key'),
	(NOW(), NOW(), 'site_titlePrefix', '', 'Site header title'),
	(NOW(), NOW(), 'api_enable', '', 'Enable mobile api');
