<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixture\EventSubscriber;

use Omines\AntiSpamBundle\AntiSpamEvents;
use Omines\AntiSpamBundle\Event\FormProcessedEvent;
use Omines\AntiSpamBundle\Event\FormViolationEvent;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AntiSpamEvents::VALIDATOR_VIOLATION => 'onValidatorViolation',
            AntiSpamEvents::FORM_PROCESSED => 'onFormProcessed',
            AntiSpamEvents::FORM_VIOLATION => 'onFormViolation',
        ];
    }

    public function onValidatorViolation(ValidatorViolationEvent $event): void
    {
        // Cancel any event that contains the word in uppercase
        if (str_contains($event->getValue(), 'CANCEL')) {
            $event->cancel();
        }
    }

    public function onFormProcessed(FormProcessedEvent $event): void
    {
    }

    public function onFormViolation(FormViolationEvent $event): void
    {
    }
}
