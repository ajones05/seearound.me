<?php
/**
 * Time helper class.
 */
class My_Time
{
	/**
	 * @const	string
	 */
	const SQL = 'Y-m-d H:i:s';

	/**
	 * @const	string
	 */
	const OUTPUT = 'F j \a\t h:ia';

	/**
	 * Converts timestamp to time ago.
	 *
	 * @param	mixed $date
	 * @param	array $options
	 * @return	string
	 */
	public static function time_ago($date, array $options = [])
	{
		if (!$date instanceof DateTime)
		{
			$date = new DateTime($date);
		}

		$now = new DateTime;
		$diff = $now->diff($date);

		if (self::isNowDiff($diff))
		{
			return 'Just now';
		}

		$diffSeconds = $now->getTimestamp() - $date->getTimestamp();

		if ($diffSeconds / 60 >= 60)
		{
			$today = (new DateTime)->setTime(0, 0, 0);

			if ($date <= $today && $date > $today->modify('-1 day'))
			{
				return 'Yesterday';
			}
		}

		$prefix = !empty($options['ago']) ? ' ago' : '';
		$minutes = self::minutesInterval($diff);

		if ($minutes)
		{
			return self::constructTimeAgo($minutes, 'minute', $prefix);
		}

		$hours = self::hoursInterval($diff);

		if ($hours)
		{
			return self::constructTimeAgo($hours, 'hour', $prefix);
		}

		$days = self::daysInterval($diff);

		if ($days)
		{
			return self::constructTimeAgo($days, 'day', $prefix);
		}

		$weeks = self::weeksInterval($diff);

		if ($weeks)
		{
			return self::constructTimeAgo($weeks, 'week', $prefix);
		}

		$months = self::monthsInterval($diff);

		if ($months)
		{
			return self::constructTimeAgo($months, 'month', $prefix);
		}

		$years = self::yearsInterval($diff);
		return self::constructTimeAgo($years, 'year', $prefix);
	}

	/**
	 *  Constructs the actual "time ago" output.
	 *
	 *  @param	integer $value
	 *  @param	string	$interval
	 *  @param 	prefix	$string
	 *  @return	string
	 */
	public static function constructTimeAgo($value, $interval, $prefix = '')
	{
		return $value . ' ' . $interval .
			My_StringHelper::multiplePrefix($value) . $prefix;
	}

	/**
	 * Is date limit by day
	 * @param DateInterval $diff
	 * @return bool
	 */
	public static function isDailyDiff($diff)
	{
		if ($diff->y == 0 && $diff->m == 0 &&
			($diff->d == 0 || ($diff->d == 1 && $diff->h == 0 && $diff->i == 0)))
		{
			return true;
		}
		return false;
	}

	/**
	 * Is date limit by hour
	 * @param DateInterval $diff
	 * @return bool
	 */
	public static function isHourlyDiff($diff)
	{
		if (self::isDailyDiff($diff) && $diff->d == 0 &&
			($diff->h == 0 || ($diff->h == 1 && $diff->i == 0)))
		{
			return true;
		}
		return false;
	}

	/**
	 * @param DateInterval $diff
	 * @return bool
	 */
	public static function isNowDiff(DateInterval $diff)
	{
		if (self::isHourlyDiff($diff) && $diff->h == 0 &&
			$diff->i == 0 && $diff->s <= 59)
		{
			return true;
		}
		return false;
	}

	/**
	 * Number of minutes related to the interval or false if more.
	 * @param DateInterval $diff
	 * @return integer|false
	 */
	public static function minutesInterval(DateInterval $diff)
	{
		if (self::isHourlyDiff($diff))
		{
			return $diff->i;
		}
		return false;
	}

	/**
	 * Number of hours related to the interval or false if more.
	 * @param DateInterval $diff
	 * @return integer|false
	 */
	public static function hoursInterval(DateInterval $diff)
	{
		if (self::isDailyDiff($diff))
		{
			return $diff->h;
		}
		return false;
	}

	/**
	 * Number of days related to the interval or false if more.
	 * @param DateInterval $diff
	 * @return integer|false
	 */
	public static function daysInterval(DateInterval $diff)
	{
		if ($diff->days <= 6)
		{
			return $diff->days;
		}
		return false;
	}

	/**
	 * Get Number of weeks
	 * @param DateInterval $diff
	 * @return integer|false
	 */
	public static function weeksInterval(DateInterval $diff)
	{
		if ($diff->days < 30)
		{
			return (int) floor($diff->days / 7);
		}
		return false;
	}

	/**
	 * Get Number of months
	 * @param DateInterval $diff
	 * @return integer|false
	 */
	public static function monthsInterval(DateInterval $diff)
	{
		if ($diff->days >= 365)
		{
			return false;
		}

		$x = (int) ceil($diff->days / 30.417);
		return $x === 0 ? 1 : $x;
	}

	/**
	 * Get Number of years
	 * @param DateInterval $diff
	 * @return integer|false
	 */
	public static function yearsInterval(DateInterval $diff)
	{
		return (int) ceil($diff->days / 365);
	}
}
