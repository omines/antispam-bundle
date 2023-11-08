<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Validator\Constraints;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\Form\AntiSpamFormError;
use Omines\AntiSpamBundle\Profile;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AntiSpamConstraintValidator extends ConstraintValidator
{
    private const STEALTHED_TRANSLATION_KEY = 'validator.stealthed';

    public function __construct(
        protected readonly AntiSpam $antiSpam,
        protected readonly TranslatorInterface $translator,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param array<string, string> $parameters
     */
    protected function failValidation(AntiSpamConstraint $constraint, string $messageTemplate, array $parameters, string $invalidValue): void
    {
        if (null === ($stealth = $constraint->stealth)) {
            $stealth = (null === ($profile = $this->getProfile())) ? $this->antiSpam->getStealth() : $profile->getStealth();
        }
        if ($stealth) {
            // Stealthed errors go on the root form if we have one in the context
            if (null !== ($form = $this->context->getRoot()) && $form instanceof FormInterface) {
                $formError = new AntiSpamFormError($messageTemplate, $messageTemplate, $parameters, null);
                $form->addError($formError);
            } else {
                // Put a stealthed validation on the validator if not in form context
                $this->context->buildViolation(self::STEALTHED_TRANSLATION_KEY)
                    ->setInvalidValue($invalidValue)
                    ->setTranslationDomain('antispam')
                    ->addViolation();
            }
        } else {
            $this->context->buildViolation($messageTemplate, $parameters)
                ->setInvalidValue($invalidValue)
                ->setTranslationDomain('antispam')
                ->addViolation();
        }
    }

    protected function getProfile(): ?Profile
    {
        if (($form = $this->context->getObject()) instanceof FormInterface) {
            do {
                if (null !== ($profile = $form->getConfig()->getOption('antispam_profile'))) {
                    assert($profile instanceof Profile);

                    return $profile;
                }
            } while ($form = $form->getParent());
        }

        return null;
    }
}
