<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Form;

use Omines\AntiSpamBundle\Profile;
use Omines\AntiSpamBundle\Validator\Constraints\AntiSpamConstraint;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;

class AntiSpamFormResult
{
    /** @var FormError[] */
    private array $antiSpamErrors = [];

    /** @var FormError[] */
    private array $formErrors = [];

    public function __construct(
        private readonly FormInterface $form,
        private readonly \DateTimeImmutable $timestamp,
        private readonly ?Request $request = null,
        private readonly ?Profile $profile = null,
    ) {
        if (!$form->isSubmitted()) {
            throw new \LogicException(sprintf('%s can only be constructed from a submitted form', self::class));
        }
        foreach ($form->getErrors(true) as $error) {
            if (self::isAntiSpamError($error)) {
                $this->antiSpamErrors[] = $error;
            } else {
                $this->formErrors[] = $error;
            }
        }
    }

    public function clearAntiSpamErrors(): void
    {
        $this->recursiveClearAntiSpamErrors($this->form);
    }

    private function recursiveClearAntiSpamErrors(FormInterface $form): void
    {
        if ($form instanceof ClearableErrorsInterface) {
            $errors = $form->getErrors();
            $form->clearErrors();
            foreach ($errors as $error) {
                if (!self::isAntiSpamError($error)) {
                    $form->addError($error);
                }
            }
            foreach ($form->all() as $child) {
                $this->recursiveClearAntiSpamErrors($child);
            }
        }
    }

    /**
     * @return FormError[]
     */
    public function getAntiSpamErrors(): array
    {
        return $this->antiSpamErrors;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * @return FormError[]
     */
    public function getFormErrors(): array
    {
        return $this->formErrors;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function hasAntiSpamErrors(): bool
    {
        return !empty($this->antiSpamErrors);
    }

    /**
     * Aliased for verbosity.
     */
    public function isSpam(): bool
    {
        return $this->hasAntiSpamErrors();
    }

    private static function isAntiSpamError(FormError $error): bool
    {
        if ($error instanceof AntiSpamFormError) {
            return true;
        } elseif (($cause = $error->getCause()) instanceof ConstraintViolation && ($cause->getConstraint() instanceof AntiSpamConstraint)) {
            return true;
        }

        return false;
    }
}
