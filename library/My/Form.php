<?php
/**
 * Form hepler class.
 */
class My_Form
{
	/**
	 * Returns form error messages.
	 *
	 * @param Zend_Form $form
	 * @return string
	 */
	public static function outputErrors(Zend_Form $form)
	{
		$errorMsgs = [];

		foreach ($form->getMessages() as $field => $fieldErrors)
		{
			$errors = [];

			foreach ($fieldErrors as $validator => $error)
			{
				$errors[] = ltrim($error, '- ');
			}

			$errorMsgs[] = '"' . $field . '" - ' . implode(', ', $errors);
		}

		return implode(', ', $errorMsgs);
	}
}