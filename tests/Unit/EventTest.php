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
use Omines\AntiSpamBundle\EventSubscriber\PassiveModeEventSubscriber;
use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(PassiveModeEventSubscriber::class)]
class EventTest extends KernelTestCase
{
    public function testBuiltInEventsHaveNegativePriority(): void
    {
        foreach (PassiveModeEventSubscriber::getSubscribedEvents() as $event => $details) {
            $this->assertIsArray($details);
            $this->assertLessThan(0, $details[1]);
        }
    }

    public function testPassiveValidatorsAreCancelled(): void
    {
        $validator = static::getContainer()->get(ValidatorInterface::class);
        assert($validator instanceof ValidatorInterface);

        $this->assertNotEmpty($validator->validate('<strong>HTML</strong>', new BannedMarkup(passive: false)));
        $this->assertEmpty($validator->validate('<strong>HTML</strong>', new BannedMarkup(passive: true)));
    }

    public function testGlobalPassiveModeIsRespected(): void
    {
        $antispam = $this->createMock(AntiSpam::class);
        $antispam->expects($this->once())->method('getPassive')->willReturn(true);

        $handler = new PassiveModeEventSubscriber($antispam);
        $event = new FormViolationEvent($this->createMock(AntiSpamFormResult::class));
        $handler->onFormViolation($event);
        $this->assertTrue($event->isCancelled());
    }
}
