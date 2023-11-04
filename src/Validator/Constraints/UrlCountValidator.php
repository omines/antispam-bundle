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

class UrlCountValidator extends AntiSpamConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UrlCount) {
            throw new UnexpectedTypeException($constraint, UrlCount::class);
        } elseif (null === $value) {
            return;
        } elseif (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }
        $value = (string) $value;
        $urlCount = preg_match_all('#[a-z][a-z0-9]+://\w+\.[\w\.]+#i', $value);
        if ($urlCount > $constraint->max) {
            $this->context->buildViolation('validator.url_count.exceeded')
                ->setParameter('count', (string) $urlCount)
                ->setParameter('limit', (string) $constraint->max)
                ->setInvalidValue($value)
                ->setCode(UrlCount::TOO_MANY_URLS_ERROR)
                ->setTranslationDomain('antispam')
                ->addViolation();
        }
    }
}
