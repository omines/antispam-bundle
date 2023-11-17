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

use Omines\AntiSpamBundle\Event\FormResultEvent;
use Omines\AntiSpamBundle\Event\ValidatorViolationEvent;

final class AntiSpamEvents
{
    /**
     * Dispatched after a protected form was processed. Can be cancelled to stop any further processing such as
     * quarantining and applying stealth/passive behaviors.
     *
     * @Event(FormResultEvent::class)
     */
    public const FORM_PROCESSED = 'antispam.form_processed';

    /**
     * Dispatched when a form has violations against anti-spam rules.
     *
     * @Event(FormResultEvent::class)
     */
    public const FORM_VIOLATION = 'antispam.form_violation';

    /**
     * Dispatched when one of the bundle's validators causes a violation.
     *
     * @Event(ValidatorViolationEvent::class)
     */
    public const VALIDATOR_VIOLATION = 'antispam.validator_violation';

    /**
     * @codeCoverageIgnore Instantiating the class is forbidden.
     */
    private function __construct()
    {
    }
}
