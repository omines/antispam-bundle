<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\Event\FormViolationEvent;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;
use Omines\AntiSpamBundle\EventSubscriber\PassiveModeSubscriber;
use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(PassiveModeSubscriber::class)]
class EventTest extends KernelTestCase
{
    public function testBuiltInEventsHaveCorrectPriority(): void
    {
        $passiveModeEvents = PassiveModeSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(FormViolationEvent::class, $passiveModeEvents);
        self::assertArrayHasKey(ValidatorViolationEvent::class, $passiveModeEvents);

        $subscriptions = [$passiveModeEvents];
        foreach ($subscriptions as $subscription) {
            foreach ($subscription as $event => $details) {
                self::assertIsArray($details);
                self::assertLessThan(0, $details[1]);
            }
        }
    }

    public function testPassiveValidatorsAreCancelled(): void
    {
        $validator = static::getContainer()->get(ValidatorInterface::class);
        assert($validator instanceof ValidatorInterface);

        self::assertNotEmpty($validator->validate('<strong>HTML</strong>', new BannedMarkup(passive: false)));
        self::assertEmpty($validator->validate('<strong>HTML</strong>', new BannedMarkup(passive: true)));
    }

    public function testGlobalPassiveModeIsRespected(): void
    {
        $antispam = $this->createMock(AntiSpam::class);
        $antispam->expects($this->once())->method('getPassive')->willReturn(true);

        $subscriber = new PassiveModeSubscriber($antispam);
        $event = new FormViolationEvent(self::createStub(AntiSpamFormResult::class));
        $subscriber->onFormViolation($event);
        self::assertTrue($event->isCancelled());
    }
}
