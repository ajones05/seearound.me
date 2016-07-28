<?php
/**
 * Query helper class.
 */
class My_Query
{
	/**
	 * Sets image thumb query.
	 *
	 * @param	Zend_Db_Select $query
	 * @param	array $thumbs [[width,height],...]
	 * @param	string $alias
	 * @return	Zend_Db_Select
	 */
	public static function setThumbsQuery(Zend_Db_Select &$query, array $thumbs, $alias)
	{
		if ($query instanceof Zend_Db_Table_Select)
		{
			$query->setIntegrityCheck(false);
		}

		$fields = [$alias . '_image_id' => $alias . '.image_id'];

		foreach ($thumbs as $thumb)
		{
			$prefix = $alias . '_' . implode('x', $thumb);
			$fields[$prefix . '_path'] = $prefix . '.path';
			$fields[$prefix . '_width'] = $prefix . '.width';
			$fields[$prefix . '_height'] = $prefix . '.height';

			$query->joinLeft([$prefix => 'image_thumb'],
				'(' . $prefix . '.image_id=' . $alias . '.image_id AND ' .
					$prefix . '.thumb_width=' . $thumb[0] . ' AND ' .
					$prefix . '.thumb_height=' . $thumb[1] . ')', '');
		}

		$query->columns($fields);

		return $query;
	}

	/**
	 * Returns image thumb.
	 *
	 * @param	mixed $data Array or Object
	 * @param	array $thumb [{WIDTH},{HEIGHT}]
	 * @param	string $alias
	 * @return	array
	 */
	public static function getThumb($data, array $thumb, $alias)
	{
		$prefix = $alias . '_' . $thumb[0] . 'x' . $thumb[1];

		if (My_ArrayHelper::getProp($data, $alias . '_image_id'))
		{
			return [
				'path' => My_ArrayHelper::getProp($data, $prefix . '_path'),
				'width' => My_ArrayHelper::getProp($data, $prefix . '_width'),
				'height' => My_ArrayHelper::getProp($data, $prefix . '_height')
			];
		}

		return false;
	}
}
