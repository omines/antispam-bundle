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
use Omines\AntiSpamBundle\AntiSpamBundle;
use Omines\AntiSpamBundle\AntiSpamEvents;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;
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
     * @param array<string, mixed> $parameters
     */
    protected function failValidation(AntiSpamConstraint $constraint, string $messageTemplate, array $parameters, string $invalidValue): void
    {
        $event = new ValidatorViolationEvent($constraint, $invalidValue);
        if ($this->eventDispatcher->dispatch($event, AntiSpamEvents::VALIDATOR_VIOLATION)->isCancelled()) {
            return;
        }

        if ($constraint->stealth ?? $this->antiSpam->getStealth()) {
            $this->context->buildViolation(self::STEALTHED_TRANSLATION_KEY)
                ->setInvalidValue($invalidValue)
                ->setTranslationDomain(AntiSpamBundle::TRANSLATION_DOMAIN)
                ->setCause($this->translator->trans($messageTemplate, $parameters, AntiSpamBundle::TRANSLATION_DOMAIN))
                ->addViolation();
        } else {
            $this->context->buildViolation($messageTemplate, $parameters)
                ->setInvalidValue($invalidValue)
                ->setTranslationDomain(AntiSpamBundle::TRANSLATION_DOMAIN)
                ->addViolation();
        }
    }
}
