<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueUser extends Constraint
{
    public $message = 'Un compte existe déjà avec ce nom et prénom.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
