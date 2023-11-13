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
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class AntiSpamFormResult
{
    /** @var array<AntiSpamFormError> */
    private array $antiSpamErrors = [];

    /** @var array<FormError> */
    private array $formErrors = [];

    public function __construct(
        private readonly FormInterface $form,
        private readonly ?Profile $profile = null,
    ) {
        if (!$form->isSubmitted()) {
            throw new \LogicException(sprintf('%s can only be constructed from a submitted form', self::class));
        }
        foreach ($form->getErrors() as $error) {
            if ($error instanceof AntiSpamFormError) {
                $this->antiSpamErrors[] = $error;
            } else {
                $this->formErrors[] = $error;
            }
        }
    }

    public function clearAntiSpamErrors(): void
    {
        if (!$this->form instanceof ClearableErrorsInterface) {
            throw new \LogicException(sprintf('You cannot invoke %s on a form that does not implement %s', __METHOD__, ClearableErrorsInterface::class));
        }
        $this->form->clearErrors();
        foreach ($this->formErrors as $error) {
            $this->form->addError($error);
        }
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function hasAntiSpamErrors(): bool
    {
        return !empty($this->antiSpamErrors);
    }
}
