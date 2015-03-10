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

	/**
	 * Gets array's or object's prperty by key.
	 *
	 * @param	mixed	$src		Source array or object.
	 * @param	string	$key		Key to get property. Can be composite "level1.level2" or "level1->level2".
	 * @param	mixed	$default	Default value to return when nothing found.
	 *
	 * @return	mixed	Reference to property or $default if nothing found.
	 */
	public static function getProp($src, $key, $default=null)
	{
		$property = &$src;
		foreach (array_map("trim", explode(".", str_replace("->", ".", $key))) as $k)
		{
			if (is_array($property))
			{
				if (isset($property[$k]))
				{
					$property = &$property[$k];
					continue;
				}
			}
			else if(is_object($property))
			{
				if (isset($property->$k))
				{
					$property = &$property->$k;
					continue;
				}
			}

			return $default;
		}

		return $property;
	}
}
