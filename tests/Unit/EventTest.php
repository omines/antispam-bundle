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
use Omines\AntiSpamBundle\Event\FormProcessedEvent;
use Omines\AntiSpamBundle\Event\FormViolationEvent;
use Omines\AntiSpamBundle\EventSubscriber\PassiveModeSubscriber;
use Omines\AntiSpamBundle\EventSubscriber\QuarantineSubscriber;
use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(PassiveModeSubscriber::class)]
#[CoversClass(QuarantineSubscriber::class)]
class EventTest extends KernelTestCase
{
    public function testBuiltInEventsHaveCorrectPriority(): void
    {
        $subscriptions = [
            PassiveModeSubscriber::getSubscribedEvents(),
            QuarantineSubscriber::getSubscribedEvents(),
        ];
        foreach ($subscriptions as $subscription) {
            foreach ($subscription as $event => $details) {
                $this->assertIsArray($details);
                $this->assertLessThan(0, $details[1]);
            }
        }
    }

    /**
     * @return array<string, mixed>[][]
     */
    public static function provideBuiltInEvents(): array
    {
        return [
            [PassiveModeSubscriber::getSubscribedEvents()],
            [QuarantineSubscriber::getSubscribedEvents()],
        ];
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

        $subscriber = new PassiveModeSubscriber($antispam);
        $event = new FormViolationEvent($this->createMock(AntiSpamFormResult::class));
        $subscriber->onFormViolation($event);
        $this->assertTrue($event->isCancelled());
    }

    #[DataProvider('provideOnlySpamVariants')]
    public function testOnlySpamWillNotLogHam(bool $onlySpam, bool $isSpam): void
    {
        $antispam = $this->createMock(AntiSpam::class);
        $antispam->expects($this->once())->method('getQuarantineConfig')->willReturn([
            'only_spam' => $onlySpam,
            'file' => [
                'dir' => '/tmp',
            ],
        ]);
        $result = $this->createMock(AntiSpamFormResult::class);
        $result->expects($this->once())->method('hasAntiSpamErrors')->willReturn($isSpam);

        $constraint = ($onlySpam && !$isSpam) ? $this->never() : $this->once();
        $result->expects($constraint)->method('asArray');

        $subscriber = new QuarantineSubscriber($antispam);
        $event = new FormProcessedEvent($result);
        $subscriber->onFormProcessed($event);
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function provideOnlySpamVariants(): array
    {
        return [
            'only spam with spam' => [true, true],
            'only spam with ham' => [true, false],
            'also ham with spam' => [false, true],
            'also ham with ham' => [false, false],
        ];
    }
}
