<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\EventSubscriber;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\Event\FormViolationEvent;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PassiveModeSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AntiSpam $antiSpam)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormViolationEvent::class => ['onFormViolation', -512],
            ValidatorViolationEvent::class => ['onValidatorViolation', -512],
        ];
    }

    public function onFormViolation(FormViolationEvent $event): void
    {
        $profile = $event->getResult()->getProfile();
        if ($profile?->getPassive() || ((null === $profile) && $this->antiSpam->getPassive())) {
            $event->cancel();
        }
    }

    public function onValidatorViolation(ValidatorViolationEvent $event): void
    {
        if (true === ($event->getConstraint()->passive ?? $this->antiSpam->getPassive())) {
            $event->cancel();
        }
    }
}
