<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixture\Entity;

use Omines\AntiSpamBundle\Type\Script;
use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fake Doctrine entity (therefore not annotated) for testing attributes.
 */
class Comment
{
    #[Antispam\BannedScripts([Script::Hebrew, Script::Cyrillic], maxPercentage: 50)]
    #[Assert\Length(min: 10, max: 500)]
    private string $message;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
