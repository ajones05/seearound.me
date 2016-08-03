<?php
if (preg_match('/^\/mobile(\/|\?|$)/', $_SERVER['REQUEST_URI']))
{
	header('Access-Control-Allow-Origin: *');
	die(json_encode([
		'status' => 0,
		'message' => 'This site is down for maintenance'
	]));
}
?>
<html>
<head>
	<style>
		body {
			color: #333333;
			font-family: Arial,Helvetica,Sans Serif;
			font-size: 78%;
			margin: 0;
			padding: 0;
			text-align: center;
		}

		.outline {
			background: #ffffff;
			border: 1px solid #cccccc;
			margin: 200px auto 20px;
			padding: 20px;
			width: 400px;
		}
	</style>
</head>
<body>
	<div id="frame" class="outline">
		<p>This site is down for maintenance.<br />Please check back again soon.</p>
	</div>
</body>
</html>
