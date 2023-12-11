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

abstract class AbstractQuarantineDriver implements QuarantineDriverInterface
{
    public function setOptions(array $options): void
    {
    }

    public function persist(QuarantineItem $item): void
    {
    }
}
