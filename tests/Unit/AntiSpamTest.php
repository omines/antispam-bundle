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
use Omines\AntiSpamBundle\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AntiSpam::class)]
#[CoversClass(Profile::class)]
class AntiSpamTest extends KernelTestCase
{
    public function testConfigurationDefaultsAreExpanded(): void
    {
        $antispam = static::getContainer()->get(AntiSpam::class);
        $this->assertInstanceOf(AntiSpam::class, $antispam);

        $config = $antispam->getQuarantineConfig();
        $this->assertSame(14, $config['file']['max_days']);
        $this->assertSame(dirname(__DIR__) . '/Fixture/var/quarantine', $config['file']['dir']);
        $this->assertArrayNotHasKey('email', $config);
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
