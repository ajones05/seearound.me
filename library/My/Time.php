<?php
/**
 * Time helper class.
 */
class My_Time
{
	/**
	 * @var	string
	 */
	public static $mysqlFormat = 'Y-m-d H:i:s';

	/**
	 * Converts timestamp to time ago.
	 *
	 * @param	string $date
	 * @param	array $options
	 * @return	string
	 */
	public static function time_ago($date, array $options = array())
	{
		$now = new DateTime;
		$date = new DateTime($date);
		$diff = $now->getTimestamp() - $date->getTimestamp();

		$minute = round($diff / 60);

		if ($minute < 1)
		{
			return 'Just now';
		}

		$today = (new DateTime)->setTime(0, 0, 0);

		if ($minute >= 60 && $date <= $today)
		{
			$yesterday = (new DateTime)->modify('-1 day')->setTime(0, 0, 0);

			if ($date > $yesterday)
			{
				return 'Yesterday';
			}
		}

		$day = $date->diff($now)->format('%a');

		switch (true)
		{
			case $minute < 60:
				$interval = $minute;
				$label = 'minute';
				break;
			case $date > $today:
				$interval = round($diff / 3600);
				$label = 'hour';
				break;
			case $day < 7:
				$interval = $day;
				$label = 'day';
				break;
			case $day < 28:
				$interval = round($day / 7);
				$label = 'week';
				break;
			case $day < 365:
				$interval = $date->diff($now)->format('%m');
				$label = 'month';
				break;
			default:
				$interval = $date->diff($now)->format('%y');
				$label = 'year';
		}

		$output = $interval . ' ' . $label;

		if ($interval > 1)
		{
			$output .= 's';
		}

		if (My_ArrayHelper::getProp($options, 'ago', false))
		{
			$output .= ' ago';
		}

		return $output;
	}
}
