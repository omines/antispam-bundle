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
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @phpstan-type FileQuarantineOptions array{dir: string, max_days: int}
 * @phpstan-type QuarantineOptions array{file: FileQuarantineOptions}
 * @phpstan-type GlobalOptions array{passive: bool, stealth: bool, quarantine: QuarantineOptions}
 */
class AntiSpam
{
    /**
     * @param GlobalOptions $options
     * @param ServiceLocator<Profile> $profiles
     */
    public function __construct(
        #[TaggedLocator('antispam.profile')]
        private readonly ServiceLocator $profiles,
        private readonly array $options,
    ) {
    }

    public function getProfile(string $name): Profile
    {
        $id = "antispam.profile.$name";
        if (!$this->profiles->has($id)) {
            throw new InvalidProfileException(sprintf('There is no antispam profile "%s" defined, did you use the correct profile name from your antispam.yaml configuration file?', $name));
        }

        return $this->profiles->get($id);
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
}
