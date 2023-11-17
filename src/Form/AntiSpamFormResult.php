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
    /** @var array<FormError> */
    private array $antiSpamErrors = [];

    /** @var array<FormError> */
    private array $formErrors = [];

    public function __construct(
        private readonly FormInterface $form,
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

    /**
     * Aliased for verbosity.
     */
    public function isSpam(): bool
    {
        return $this->hasAntiSpamErrors();
    }

    /**
     * @return array<string, mixed>
     *
     * @infection-ignore-all useless to test for infections as long as this API is expanding
     * @todo add relevant tests when stabilized
     */
    public function asArray(): array
    {
        $array = [
            'is_spam' => $this->hasAntiSpamErrors(),
            'values' => $this->form->getData(),
            'antispam' => array_map(fn (FormError $error) => [
                'message' => $error->getMessage(),
                'cause' => $error->getCause(),
                'field' => $error->getOrigin()?->getName(),
            ], $this->antiSpamErrors),
            'other' => array_map(fn (FormError $error) => [
                'message' => $error->getMessage(),
                'field' => $error->getOrigin()?->getName(),
            ], $this->formErrors),
        ];

        if (null !== ($request = $this->request)) {
            $array['request'] = [
                'uri' => $request->getRequestUri(),
                'client_ip' => $request->getClientIp(),
                'referrer' => $request->headers->get('referer'),
                'user_agent' => $request->headers->get('user-agent'),
            ];
        }

        return $array;
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
