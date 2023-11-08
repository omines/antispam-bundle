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

class BannedPhrasesValidator extends AntiSpamConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof BannedPhrases) {
            throw new UnexpectedTypeException($constraint, BannedPhrases::class);
        } elseif (null === $value) {
            return;
        } elseif (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }
        $value = (string) $value;
        $regexp = $constraint->getRegularExpression();

        if (preg_match($regexp, $value, $matches)) {
            $this->failValidation($constraint, 'validator.banned_phrases.phrase_found', [
                'phrase' => $matches[1],
            ], $value);
        }
    }
}
