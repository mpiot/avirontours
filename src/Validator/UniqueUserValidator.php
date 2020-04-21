<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUserValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $users = $this->userRepository->findBy(['firstName' => $value->firstName, 'lastName' => $value->lastName]);

        if (!empty($users)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('firstName')
                ->addViolation();
        }
    }
}
