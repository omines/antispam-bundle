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
use Omines\AntiSpamBundle\Exception\InvalidProfileException;
use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Omines\AntiSpamBundle\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AntiSpam::class)]
#[CoversClass(Profile::class)]
class AntiSpamTest extends KernelTestCase
{
    #[RunInSeparateProcess]
    public function testStaticFunctions(): void
    {
        self::assertNull(AntiSpam::getLastResult());
        self::assertFalse(AntiSpam::isSpam());

        $mock = $this->createMock(AntiSpamFormResult::class);
        $mock
            ->expects($this->once())
            ->method('isSpam')
            ->willReturn(true)
        ;

        AntiSpam::setLastResult($mock);
        self::assertTrue(AntiSpam::isSpam());

        /** @var AntiSpam $antispam */
        $antispam = static::getContainer()->get(AntiSpam::class);
        $antispam->disable();
        $antispam->reset();

        self::assertNull(AntiSpam::getLastResult());
        self::assertTrue($antispam->isEnabled());
    }

    public function testConfigurationDefaultsAreExpanded(): void
    {
        $antispam = static::getContainer()->get(AntiSpam::class);
        self::assertInstanceOf(AntiSpam::class, $antispam);

        self::assertTrue($antispam->isEnabled());
        self::assertFalse($antispam->getPassive());
        self::assertFalse($antispam->getStealth());

        $antispam->disable();
        self::assertFalse($antispam->isEnabled());
        $antispam->enable();
        self::assertTrue($antispam->isEnabled());
    }

    public function testUnknownProfileThrows(): void
    {
        $this->expectException(InvalidProfileException::class);

        /** @var AntiSpam $antispam */
        $antispam = static::getContainer()->get(AntiSpam::class);
        $antispam->getProfile('non_existent_service_name');
    }

    public function testConfigAndNameArePassedToProfile(): void
    {
        /** @var AntiSpam $antispam */
        $antispam = static::getContainer()->get(AntiSpam::class);
        self::assertFalse($antispam->getPassive());
        self::assertFalse($antispam->getStealth());

        $profile = $antispam->getProfile('test1');
        self::assertSame('test1', $profile->getName());
        self::assertFalse($profile->getStealth());
        self::assertFalse($profile->getPassive());

        $config = $profile->getConfig();
        self::assertFalse($config['stealth']);
        self::assertFalse($config['passive']);

        $config['passive'] = true;
        $profile->setConfig($config);
        self::assertTrue($profile->getPassive());
    }

    public function testProfileCachesConstraints(): void
    {
        /** @var AntiSpam $antispam */
        $antispam = static::getContainer()->get(AntiSpam::class);
        $profile = $antispam->getProfile('test1');
        self::assertSame($profile->getTextTypeConstraints(), $profile->getTextTypeConstraints());
    }
}
