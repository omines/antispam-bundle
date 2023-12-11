<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Quarantine;

use Omines\AntiSpamBundle\Quarantine\Driver\QuarantineDriverInterface;

class Quarantine
{
    public function __construct(private readonly QuarantineDriverInterface $driver)
    {
    }

    public function add(QuarantineItem $item): void
    {
        $this->driver->persist($item);
    }

    public function getDriver(): QuarantineDriverInterface
    {
        return $this->driver;
    }
}
