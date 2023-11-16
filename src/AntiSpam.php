<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle;

use Omines\AntiSpamBundle\Exception\InvalidProfileException;
use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @phpstan-type FileQuarantineOptions array{dir: string, max_days: int}
 * @phpstan-type QuarantineOptions array{file: ?FileQuarantineOptions}
 * @phpstan-type GlobalOptions array{passive: bool, stealth: bool, enabled: bool, quarantine: QuarantineOptions}
 */
class AntiSpam implements ResetInterface
{
    private static ?AntiSpamFormResult $lastResult = null;

    private bool $enabled;

    /**
     * @param GlobalOptions $options
     * @param ServiceLocator<Profile> $profiles
     */
    public function __construct(
        #[TaggedLocator('antispam.profile')]
        private readonly ServiceLocator $profiles,
        private readonly array $options,
    ) {
        $this->reset();
    }

    public function getProfile(string $name): Profile
    {
        $id = "antispam.profile.$name";
        if (!$this->profiles->has($id) || (!($profile = $this->profiles->get($id)) instanceof Profile)) {
            throw new InvalidProfileException(sprintf('There is no antispam profile "%s" defined, did you use the correct profile name from your antispam.yaml configuration file?', $name));
        }

        return $profile;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * @return QuarantineOptions
     */
    public function getQuarantineConfig(): array
    {
        return $this->options['quarantine'];
    }

    public function getPassive(): bool
    {
        return $this->options['passive'];
    }

    public function getStealth(): bool
    {
        return $this->options['stealth'];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public static function getLastResult(): ?AntiSpamFormResult
    {
        return self::$lastResult;
    }

    public static function isSpam(): bool
    {
        return self::getLastResult()?->isSpam() ?? false;
    }

    public function reset(): void
    {
        self::$lastResult = null;

        $this->enabled = $this->options['enabled'];
    }

    public static function setLastResult(AntiSpamFormResult $lastResult): void
    {
        self::$lastResult = $lastResult;
    }
}
