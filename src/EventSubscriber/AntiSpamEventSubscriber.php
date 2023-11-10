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

use Omines\AntiSpamBundle\AntiSpamEvents;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AntiSpamEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AntiSpamEvents::VALIDATOR_VIOLATION => ['onValidatorViolation', -256],
        ];
    }

    public function onValidatorViolation(ValidatorViolationEvent $event): void
    {
        if (true === $event->getConstraint()->passive) {
            $event->cancel();
        }
    }
}
