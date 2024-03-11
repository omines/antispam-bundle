<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @template T of ConstraintValidatorInterface
 */
abstract class ConstraintValidatorTestCase extends KernelTestCase
{
    private ExecutionContextInterface $context;
    private ValidatorInterface $validator;
    protected ConstraintValidatorInterface $constraintValidator;
    protected ConstraintViolationListInterface $lastViolations;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $translator = $container->get(TranslatorInterface::class);
        $validator = $container->get(ValidatorInterface::class);
        $constraintValidator = $container->get($this->getValidatorClass());
        assert($translator instanceof TranslatorInterface);
        assert($validator instanceof ValidatorInterface);
        assert($constraintValidator instanceof ConstraintValidatorInterface);

        $this->validator = $validator;
        $this->context = new ExecutionContext($validator, null, $translator);
        $this->constraintValidator = $constraintValidator;
        $this->constraintValidator->initialize($this->context);
    }

    protected function assertNoViolation(): void
    {
        if (!isset($this->lastViolations)) {
            $this->fail('No validation has been run yet!');
        }
        $this->assertEmpty($this->lastViolations, 'Failed asserting that validation causes no violations.');
    }

    protected function expectNoViolations(string $value, Constraint $constraint): void
    {
        $errors = $this->validate($value, $constraint);
        $this->assertCount(0, $errors, sprintf('Failed asserting that validating "%s" would cause no violations.', $value));
    }

    protected function expectViolations(string $value, Constraint $constraint, int $amount = 1): ConstraintViolationListInterface
    {
        $errors = $this->validate($value, $constraint);
        $this->assertCount($amount, $errors, sprintf('Failed asserting that validating "%s" would cause %d violation(s)', $value, $amount));

        return $errors;
    }

    /**
     * @return class-string<T>
     */
    abstract protected function getValidatorClass(): string;

    /** @phpstan-ignore-next-line Forwarded function into Symfony validator */
    public function validate(mixed $value, Constraint|array|null $constraints = null, string|GroupSequence|array|null $groups = null): ConstraintViolationListInterface
    {
        return $this->lastViolations = $this->validator->validate($value, $constraints, $groups);
    }
}
