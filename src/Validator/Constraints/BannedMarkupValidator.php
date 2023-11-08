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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class BannedMarkupValidator extends AntiSpamConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BannedMarkup) {
            throw new UnexpectedTypeException($constraint, BannedMarkup::class);
        } elseif (null === $value) {
            return;
        } elseif (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }
        $value = (string) $value;

        /* @todo Build correct translatable validations */
        if ($constraint->html && preg_match('#<([a-z0-9]+).*</\1>#i', $value)) {
            $this->failValidation($constraint, 'validator.banned_markup.html', [], $value);
        }
        if ($constraint->bbcode && preg_match('#\[([a-z]+).*\[/\1\]#i', $value)) {
            $this->failValidation($constraint, 'validator.banned_markup.bbcode', [], $value);
        }
    }
}
