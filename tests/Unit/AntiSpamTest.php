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
        $this->assertFalse($antispam->isPassive());
        $this->assertFalse($antispam->isStealth());

        $profile = $antispam->getProfile('test1');
        $this->assertSame('test1', $profile->getName());
        $this->assertNotEmpty($profile->getConfig());
    }
}
