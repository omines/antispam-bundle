<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Utility;

/**
 * @internal not part of the public API of this bundle
 */
class StringCounter
{
    /** @var array<string, int> */
    private array $scores = [];

    public function add(string $string): void
    {
        array_key_exists($string, $this->scores) ? $this->scores[$string]++ : ($this->scores[$string] = 1);
    }

    /**
     * @return array{string, int}[]
     */
    public function getScores(bool $sortByKey = true): array
    {
        if ($sortByKey) {
            ksort($this->scores);
        }

        return array_map(fn ($k, $v) => [$k, $v], array_keys($this->scores), $this->scores);
    }

    /**
     * @return array{string, int}[]
     */
    public function getRanking(?int $max = null): array
    {
        arsort($this->scores, SORT_NUMERIC);

        $slice = array_slice($this->scores, 0, $max);

        return array_map(fn ($k, $v) => [$k, $v], array_keys($slice), $slice);
    }
}
