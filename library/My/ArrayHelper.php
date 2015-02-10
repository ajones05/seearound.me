<?php

/**
 * Array hepler class.
 */
class My_ArrayHelper
{
	/**
	 * Searches the array for a given value.
	 *
	 * @param	array 	$needle
	 * @param	array	$haystack
	 * @param	integer	$limit
	 *
	 * @return	array
	 */
	public static function search(array $needle, array $haystack, $limit = null)
	{
		$result = array();

		$indexes = array_keys($needle);

		if (count($indexes))
		{
			foreach ($haystack as $arr)
			{
				$valid = true;

				foreach ($indexes as $index)
				{
					if (!isset($arr[$index]) || !isset($needle[$index]) || $arr[$index] !== $needle[$index])
					{
						$valid = false;
					}
				}

				if ($valid)
				{
					$result[] = $arr;

					if ($limit != null && count($result) >= $limit)
					{
						break;
					}
				}
			}
		}

		return $result;
	}
}
