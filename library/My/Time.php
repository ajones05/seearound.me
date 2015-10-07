<?php
/**
 * Time helper class.
 */
class My_Time
{
	/**
	 * Converts timestamp to time ago.
	 *
	 * @param	string	$date
	 *
	 * @return	string
	 */
	public static function time_ago($date)
	{
		$now = new DateTime;
		$date = new DateTime($date);
		$diff = $now->getTimestamp() - $date->getTimestamp();

		$minutes = round($diff / 60);

		if ($minutes < 1)
		{
			return 'Just now';
		}

		if ($minutes < 60)
		{
			$output = $minutes . ' minute';

			if ($minutes != 1)
			{
				$output .= 's';
			}

			return $output;
		}

		$today = (new DateTime)->setTime(0, 0, 0);

		if ($date > $today)
		{
			$hours = round($diff / 3600);

			$output = $hours . ' hour';

			if ($hours != 1)
			{
				$output .= 's';
			}

			return $output;
		}

		$yesterday = (new DateTime)->modify('-1 day')->setTime(0, 0, 0);

		if ($date > $yesterday)
		{
			return 'Yesterday at ' . $date->format('h:ia');
		}

		return $date->format('F j \a\t h:ia');
	}
}
