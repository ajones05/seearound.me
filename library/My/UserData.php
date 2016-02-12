<?php
/**
 * User data class.
 */
class My_UserData
{
	/**
	 * @var	string
	 */
	protected static $_name;

	/**
	 * @var	array
	 */
	protected static $_data;

	/**
	 * Class constructor.
	 *
	 * @param	integer $user_id
	 * @return	boolean
	 */
	public function __construct($user_id = null)
	{
		if (!self::$_name)
		{
			if ($user_id === null)
			{
				$user_id = My_ArrayHelper::getProp(
					Zend_Auth::getInstance()->getIdentity(), 'user_id', 0);
			}
			self::$_name = md5(crypt('data', $user_id));
			$data = My_ArrayHelper::getProp($_COOKIE, self::$_name);
			self::$_data = $data ? json_decode(base64_decode($data), true) : [];
		}
	}

	/**
	 * Read data.
	 *
	 * @param	string $name
	 * @return	mixed
	 */
	public function __get($name)
	{
		return isset(self::$_data[$name]) ? self::$_data[$name] : null;
	}

	/**
	 * Writes data.
	 *
	 * @param	string $name
	 * @param	mixed $value
	 * @return	boolean
	 */
	public function __set($name, $value)
	{
		self::$_data[$name] = $value;
		$this->write(self::$_data);
	}

	/**
	 * Checks if data exists.
	 *
	 * @param	string $name
	 * @return	boolean
	 */
	public function __isset($name)
	{
		return isset(self::$_data[$name]);
	}

	/**
	 * Returns data.
	 *
	 * @return	array
	 */
	public function getData()
	{
		return self::$_data;
	}

	/**
	 * Overwrites data.
	 *
	 * @param	array $data
	 * @return	boolean
	 */
	public function write(array $data)
	{
		self::$_data = $data;
		return setcookie(self::$_name, base64_encode(json_encode($data)), 0, '/');
	}

	/**
	 * Clears data (set session expire to 1980-01-01).
	 *
	 * @return	boolean
	 */
	public function clear()
	{
		return setcookie(self::$_name, '', 315554400, '/');
	}
}
