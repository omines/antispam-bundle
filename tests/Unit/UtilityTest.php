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

use Omines\AntiSpamBundle\Utility\StringCounter;
use PHPUnit\Framework\TestCase;

class UtilityTest extends TestCase
{
    public function testStringCounter(): void
    {
        $test = new StringCounter();
        $test->add('foo');
        $test->add('bar');
        $test->add('bar');
        $test->add('baz');
        $test->add('baz');
        $test->add('baz');

        $unsorted = $test->getScores(false);
        self::assertSame(['foo', 1], $unsorted[0]);

        $sorted = $test->getScores();
        self::assertSame(['bar', 2], $sorted[0]);

        $ranking = $test->getRanking(2);
        self::assertCount(2, $ranking);
        self::assertSame(['baz', 3], $ranking[0]);
    }
}
