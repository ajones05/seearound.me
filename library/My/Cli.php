<?php
/**
 * Cli class.
 */
class My_Cli
{
	/**
	 * Returns success message.
	 *
	 * @param	string	$msg
	 * @return	string
	 */
	public static function success($msg)
	{
		return \cli\Colors::colorize("%g" . date("c") . "\tSUCCESS\t\t{$msg}%n", true) . "\n";
	}

	/**
	 * Returns info message.
	 *
	 * @param	string	$msg
	 * @return	string
	 */
	public static function info($msg)
	{
		return \cli\Colors::colorize("%P" . date("c") . "\tINFO\t\t{$msg}%n", true) . "\n";
	}

	/**
	 * Returns notice message.
	 *
	 * @param	string	$msg
	 * @return	string
	 */
	public static function notice($msg)
	{
		return \cli\Colors::colorize("%Y" . date("c") . "\tNOTICE\t\t{$msg}%n", true) . "\n";
	}

	/**
	 * Returns debug message.
	 *
	 * @param	string	$msg
	 * @return	string
	 */
	public static function debug($msg)
	{
		return \cli\Colors::colorize("%C" . date("c") . "\tDEBUG\t\t{$msg}%n", true) . "\n";
	}

	/**
	 * Reutrns error message.
	 *
	 * @param	string	$msg
	 * @return	string
	 */
	public static function error($msg)
	{
		return \cli\Colors::colorize("%R" . date("c") . "\tERROR\t\t{$msg}%n", true) . "\n";
	}

	/**
	 * Displays message and stop scripts.
	 *
	 * @param	string	$msg
	 * @return	void
	 */
	public static function fatalError($msg)
	{
		die(\cli\Colors::colorize("%r" . date("c") . "\tFATAL ERROR\t{$msg}%n", true) . "\n");
	}
}
