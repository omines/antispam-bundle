<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Event;

use Omines\AntiSpamBundle\Validator\Constraints\AntiSpamConstraint;

class ValidatorViolationEvent extends AntiSpamEvent
{
    public function __construct(
        private readonly AntiSpamConstraint $constraint,
        private readonly string $value,
    ) {
    }

    public function getConstraint(): AntiSpamConstraint
    {
        return $this->constraint;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
