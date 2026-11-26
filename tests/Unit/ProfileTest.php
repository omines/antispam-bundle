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
use Omines\AntiSpamBundle\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\RateLimiter\CompoundLimiter;

#[CoversClass(Profile::class)]
class ProfileTest extends KernelTestCase
{
    public function testProfileWithoutLimitersHasNoRateLimiter(): void
    {
        $profile = $this->getProfile('empty');

        $limiter = $profile->getRateLimiter('127.0.0.1');

        self::assertFalse($profile->hasRateLimiter());
        self::assertNull($limiter);
    }

    public function testProfileWithOneLimiterUsesItDirectly(): void
    {
        $profile = $this->getProfile('rate_limited');

        $limiter = $profile->getRateLimiter('127.0.0.1');

        self::assertTrue($profile->hasRateLimiter());
        self::assertNotNull($limiter);
        self::assertNotInstanceOf(CompoundLimiter::class, $limiter);
    }

    public function testProfileWithMultipleLimitersCombinesThem(): void
    {
        $profile = $this->getProfile('rate_limited_compound');

        $limiter = $profile->getRateLimiter('127.0.0.1');

        self::assertInstanceOf(CompoundLimiter::class, $limiter);
    }

    private function getProfile(string $name): Profile
    {
        $antiSpam = static::getContainer()->get(AntiSpam::class);
        self::assertInstanceOf(AntiSpam::class, $antiSpam);

        return $antiSpam->getProfile($name);
    }
}
