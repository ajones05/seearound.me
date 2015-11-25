<?php
namespace Validation\Rules;

/**
 * Longitude validation rule.
 * Same as:
 *     v::allOf(
 *        v::numeric(),
 *        v::between(-180, 180)
 *     );
 * Which is the same as:
 *     v::numeric()->between(-180, 180);
 */
class Lng extends \Respect\Validation\Rules\Numeric
{
    public function validate($input)
    {
        return parent::validate($input) &&
			(new \Respect\Validation\Rules\Between(-180, 180))->validate($input);
    }
}
