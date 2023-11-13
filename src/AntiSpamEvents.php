<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle;

use Omines\AntiSpamBundle\Event\FormViolationEvent;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;

final class AntiSpamEvents
{
    /**
     * @Event(FormViolationEvent::class)
     */
    public const FORM_VIOLATION = 'antispam.form_violation';

    /**
     * @Event(ValidatorViolationEvent::class)
     */
    public const VALIDATOR_VIOLATION = 'antispam.validator_violation';

    /**
     * @codeCoverageIgnore This method is intended to never be called.
     */
    private function __construct()
    {
    }
}
