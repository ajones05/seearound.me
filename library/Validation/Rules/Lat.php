<?php
namespace Validation\Rules;

/**
 * Latitude validation rule.
 * Same as:
 *     v::allOf(
 *        v::numeric(),
 *        v::between(-90, 90)
 *     );
 * Which is the same as:
 *     v::numeric()->between(-90, 90);
 */
class Lat extends \Respect\Validation\Rules\Numeric
{
    public function __construct()
    {
        parent::__construct(new \Respect\Validation\Rules\Between(-90, 90));
    }
}
