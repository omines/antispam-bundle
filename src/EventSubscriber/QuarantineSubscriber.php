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
use Omines\AntiSpamBundle\Event\FormProcessedEvent;
use Omines\AntiSpamBundle\Quarantine\Quarantine;
use Omines\AntiSpamBundle\Quarantine\QuarantineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuarantineSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Quarantine $quarantine)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AntiSpamEvents::FORM_PROCESSED => ['onFormProcessed', -512],
        ];
    }

    public function onFormProcessed(FormProcessedEvent $event): void
    {
        $this->quarantine->add(QuarantineItem::fromResult($event->getResult()));
    }
}
