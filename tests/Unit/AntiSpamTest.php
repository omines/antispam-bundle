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
use Omines\AntiSpamBundle\Quarantine\Driver\FileQuarantineDriver;
use Omines\AntiSpamBundle\Quarantine\Driver\QuarantineDriverInterface;
use Omines\AntiSpamBundle\Quarantine\Quarantine;
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
        $this->assertNull(AntiSpam::getLastResult());
        $this->assertFalse(AntiSpam::isSpam());

        $mock = $this->createMock(AntiSpamFormResult::class);
        $mock
            ->expects($this->once())
            ->method('isSpam')
            ->willReturn(true)
        ;

        AntiSpam::setLastResult($mock);
        $this->assertTrue(AntiSpam::isSpam());

        /** @var AntiSpam $antispam */
        $antispam = static::getContainer()->get(AntiSpam::class);
        $antispam->disable();
        $antispam->reset();

        $this->assertNull(AntiSpam::getLastResult());
        $this->assertTrue($antispam->isEnabled());
    }

    public function testConfigurationDefaultsAreExpanded(): void
    {
        $antispam = static::getContainer()->get(AntiSpam::class);
        $this->assertInstanceOf(AntiSpam::class, $antispam);

        $this->assertTrue($antispam->isEnabled());
        $this->assertFalse($antispam->getPassive());
        $this->assertFalse($antispam->getStealth());

        $antispam->disable();
        $this->assertFalse($antispam->isEnabled());
        $antispam->enable();
        $this->assertTrue($antispam->isEnabled());

        $quarantine = static::getContainer()->get(Quarantine::class);
        $this->assertInstanceOf(Quarantine::class, $quarantine);

        $driver = static::getContainer()->get(QuarantineDriverInterface::class);
        $this->assertInstanceOf(FileQuarantineDriver::class, $driver);
        $this->assertSame(14, $driver->getMaxDays());
        $this->assertSame(dirname(__DIR__) . '/Fixture/var/quarantine', $driver->getDir());
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
        $this->assertFalse($antispam->getPassive());
        $this->assertFalse($antispam->getStealth());

        $profile = $antispam->getProfile('test1');
        $this->assertSame('test1', $profile->getName());
        $this->assertNotEmpty($profile->getConfig());
        $this->assertFalse($profile->getStealth());
        $this->assertFalse($profile->getPassive());
    }

    public function testProfileCachesConstraints(): void
    {
        /** @var AntiSpam $antispam */
        $antispam = static::getContainer()->get(AntiSpam::class);
        $profile = $antispam->getProfile('test1');
        $this->assertSame($profile->getTextTypeConstraints(), $profile->getTextTypeConstraints());
    }
}
