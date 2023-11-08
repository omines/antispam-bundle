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

use Symfony\Component\Validator\Attribute\HasNamedArguments;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class UrlCount extends AntiSpamConstraint
{
    public const TOO_MANY_URLS_ERROR = 'a13968de-dd09-4bff-9ca8-60791f75cbf0';

    #[HasNamedArguments]
    public function __construct(
        public int $max = 0,
        public ?int $maxIdentical = null,
        bool $passive = null,
        bool $stealth = null,
        array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($passive, $stealth, $groups, $payload);
    }
}
