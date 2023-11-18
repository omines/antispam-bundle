<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Quarantine;

use Omines\AntiSpamBundle\Form\AntiSpamFormResult;

/**
 * @phpstan-type SerializedItem array{timestamp: string}
 */
class QuarantineItem
{
    public function __construct(private readonly \DateTimeInterface $timestamp)
    {
    }

    public function getTimestamp(): \DateTimeInterface
    {
        return $this->timestamp;
    }

    public static function fromResult(AntiSpamFormResult $result): self
    {
        $item = new self($result->getTimestamp());

        return $item;
    }

    /**
     * @return SerializedItem
     */
    public function __serialize(): array
    {
        return [
            'timestamp' => $this->timestamp->format('c'),
        ];
    }

    /**
     * @param SerializedItem $data
     */
    public function __unserialize(array $data): void
    {
        $this->timestamp = new \DateTimeImmutable($data['timestamp']);
    }
}
