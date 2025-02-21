<?php
namespace Validation\Rules;

/**
 * Latitude/longitude validation rule.
 */
class LatLng extends \Respect\Validation\Rules\AllOf
{
    public function validate($input)
    {
		if (!is_array($input))
		{
			$input = explode(',', $input);
		}

		if (count($input) != 2)
		{
			return false;
		}

        return \Respect\Validation\Validator::lat()->validate($input[0]) &&
			\Respect\Validation\Validator::lng()->validate($input[1]);
    }
}
