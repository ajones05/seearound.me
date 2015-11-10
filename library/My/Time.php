<?php
/**
 * Time helper class.
 */
class My_Time
{
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

		$minutes = round($diff / 60);

		if ($minutes < 1)
		{
			return 'Just now';
		}

		$putAgo = My_ArrayHelper::getProp($options, 'ago', false);

		if ($minutes < 60)
		{
			$output = $minutes . ' minute';

			if ($minutes != 1)
			{
				$output .= 's';
			}

			if ($putAgo)
			{
				$output .= ' ago';
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

			if ($putAgo)
			{
				$output .= ' ago';
			}

			return $output;
		}

		$short = My_ArrayHelper::getProp($options, 'short', false);
		$yesterday = (new DateTime)->modify('-1 day')->setTime(0, 0, 0);

		if ($date > $yesterday)
		{
			return 'Yesterday' . (!$short ? ' at ' . $date->format('h:ia') : '');
		}

		if ($short)
		{
			$interval = $date->diff($now)->format('%a');
			$output = $interval . ' day';

			if ($interval != 1)
			{
				$output .= 's';
			}

			if ($putAgo)
			{
				$output .= ' ago';
			}

			return $output;
		}
		
		return $date->format('F j \a\t h:ia');
	}
}
