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

class BannedScriptsValidator extends AntiSpamConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BannedScripts) {
            throw new UnexpectedTypeException($constraint, BannedScripts::class);
        } elseif (null === $value) {
            return;
        } elseif (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }
        $value = (string) $value;
        $class = $constraint->getCharacterClass();

        // Try the cheaper test first for early fail
        if (preg_match("/{$class}/u", $value)) {
            if ($constraint->maxPercentage > 0 || null !== $constraint->maxCharacters) {
                // Only do an expensive full count once we know we need the numbers
                $count = preg_match_all("/{$class}/u", $value);
                $percentage = 100 * $count / mb_strlen($value);
                if ($constraint->maxPercentage <= 0 && $count > $constraint->maxCharacters) {
                    $this->failValidation($constraint, 'validator.banned_scripts.characters_exceeded', [
                        'count' => (string) $count,
                        'max' => $constraint->maxCharacters,
                        'scripts' => $constraint->getReadableScripts(),
                    ], $value);
                } elseif (null === $constraint->maxCharacters && $percentage > $constraint->maxPercentage) {
                    $this->failValidation($constraint, 'validator.banned_scripts.percentage_exceeded', [
                        'percentage' => (string) ceil($percentage),
                        'max' => $constraint->maxPercentage,
                        'scripts' => $constraint->getReadableScripts(),
                    ], $value);
                }
            } else {
                $this->failValidation($constraint, 'validator.banned_scripts.not_allowed', [
                    'scripts' => $constraint->getReadableScripts(),
                ], $value);
            }
        }
    }
}
