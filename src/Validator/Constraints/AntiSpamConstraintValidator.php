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

use Omines\AntiSpamBundle\AntiSpam;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AntiSpamConstraintValidator extends ConstraintValidator
{
    public function __construct(
        protected readonly AntiSpam $antiSpam,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    protected function getGlobalPassive(): bool
    {
        return $this->antiSpam->isPassive();
    }

    protected function getGlobalStealth(): bool
    {
        return $this->antiSpam->isStealth();
    }
}
