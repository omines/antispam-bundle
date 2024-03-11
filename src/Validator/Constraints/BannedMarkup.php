<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Validator\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BannedMarkup extends AntiSpamConstraint
{
    public function __construct(
        public bool $html = true,
        public bool $bbcode = true,
        ?bool $passive = null,
        ?bool $stealth = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($passive, $stealth, $groups, $payload);
    }
}
