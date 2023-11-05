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
                    $this->context
                        ->buildViolation('validator.banned_script.characters_exceeded')
                        ->setParameter('count', (string) $count)
                        ->setParameter('max', (string) $constraint->maxCharacters)
                        ->setParameter('scripts', $constraint->getReadableScripts())
                        ->setInvalidValue($value)
                        ->setCode(BannedScripts::TOO_MANY_CHARACTERS_ERROR)
                        ->setTranslationDomain('antispam')
                        ->addViolation();
                } elseif (null === $constraint->maxCharacters && $percentage > $constraint->maxPercentage) {
                    $this->context
                        ->buildViolation('validator.banned_script.percentage_exceeded')
                        ->setParameter('percentage', (string) ceil($percentage))
                        ->setParameter('max', (string) $constraint->maxPercentage)
                        ->setParameter('scripts', $constraint->getReadableScripts())
                        ->setInvalidValue($value)
                        ->setCode(BannedScripts::TOO_HIGH_PERCENTAGE_ERROR)
                        ->setTranslationDomain('antispam')
                        ->addViolation();
                }
            } else {
                $this->context
                    ->buildViolation('validator.banned_script.not_allowed')
                    ->setParameter('scripts', $constraint->getReadableScripts())
                    ->setInvalidValue($value)
                    ->setCode(BannedScripts::NOT_ALLOWED_ERROR)
                    ->setTranslationDomain('antispam')
                    ->addViolation();
            }
        }
    }
}
