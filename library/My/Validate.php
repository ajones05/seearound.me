<?php

/**
 * Validte class helper.
 */
class My_Validate
{
	/**
	 * Checks whether a string consists of digits only (no dots or dashes).
	 *
	 * @param   string  $value
	 * 
	 * @return	boolean
	 */
	public static function digit($value)
	{
		return preg_match("/^\d+$/", $value);
	}

	/**
	 * Checks if latitude value is valid.
	 *
	 * @param	mixed	$value
	 *
	 * @return	boolean
	 */
	public static function latitude($value)
	{
		return preg_match("/-?\d+(\.\d+)?/", $value) && abs($value) <= 90;
	}

	/**
	 * Checks if longitude value is valid.
	 *
	 * @param	mixed	$value
	 *
	 * @return	boolean
	 */
	public static function longitude($value)
	{
		return preg_match("/-?\d+(\.\d+)?/", $value) && abs($value) <= 180;
	}
}
