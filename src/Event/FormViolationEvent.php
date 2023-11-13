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

use Omines\AntiSpamBundle\Form\AntiSpamFormResult;

class FormViolationEvent extends AntiSpamEvent
{
    public function __construct(private readonly AntiSpamFormResult $result)
    {
    }

    public function getResult(): AntiSpamFormResult
    {
        return $this->result;
    }
}
