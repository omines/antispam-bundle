<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Quarantine\Driver;

use Omines\AntiSpamBundle\Quarantine\QuarantineItem;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-type FileQuarantineOptions array{dir: string, max_days: int}
 */
#[AsAlias('antispam.quarantine.file')]
class FileQuarantineDriver implements QuarantineDriverInterface
{
    public const DEFAULT_MAX_DAYS = 21;

    private int $maxDays = self::DEFAULT_MAX_DAYS;

    private string $dir;

    /**
     * @param FileQuarantineOptions $options
     */
    public function setOptions(array $options): void
    {
        $this->dir = $options['dir'];
        $this->maxDays = $options['max_days'] ?: self::DEFAULT_MAX_DAYS;
    }

    public function persist(QuarantineItem $item): void
    {
        $ts = $item->getTimestamp();

        $filename = sprintf('%s.yaml', $ts->format('H-i-s'));
        $path = Path::join($this->dir, $ts->format('Y-m-d'), $filename);

        $fs = new Filesystem();
        $fs->appendToFile($path, sprintf("#\n# ----- %s -----\n%s", $ts->format('c'),
            Yaml::dump($item->toArray(), 5, flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)));
    }

    public function getMaxDays(): int
    {
        return $this->maxDays;
    }

    public function getDir(): string
    {
        return $this->dir;
    }
}
