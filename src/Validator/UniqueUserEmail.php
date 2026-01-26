<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class UniqueUserEmail extends Constraint
{
    public string $message = 'This email is already in use.';

    public function validatedBy(): string
    {
        return UniqueUserEmailValidator::class;
    }
}
