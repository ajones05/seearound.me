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
		$estimate_time = time() - strtotime($date);

		if ($estimate_time < 1)
		{
			return 'less than 1 second ago';
		}

		$condition = array(
			315705600 => 'decade',
			31104000 => 'year',
			2592000 => 'month',
			604800 => 'week',
			86400 => 'day',
			3600 => 'hour',
			60 => 'minute',
			1 => 'second'
		);

		foreach ($condition as $secs => $str)
		{
			$d = $estimate_time / $secs;

			if ($d >= 1)
			{
				$r = round($d);
				return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
			}
		}
	}
}
