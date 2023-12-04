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
use Symfony\Component\Form\FormError;

/**
 * @phpstan-type SerializedItem array{timestamp: string}
 */
final class QuarantineItem
{
    /** @var SerializedItem $data */
    private array $data;

    private function __construct()
    {
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->data['timestamp']);
    }

    public static function fromResult(AntiSpamFormResult $result): self
    {
        $item = new self();
        $item->data = [
            'timestamp' => $result->getTimestamp()->format('c'),
            'is_spam' => $result->hasAntiSpamErrors(),
            'values' => $result->getForm()->getData(),
            'antispam' => array_map(fn (FormError $error) => [
                'message' => $error->getMessage(),
                'cause' => $error->getCause(),
                'field' => $error->getOrigin()?->getName(),
            ], $result->getAntiSpamErrors()),
            'other' => array_map(fn (FormError $error) => [
                'message' => $error->getMessage(),
                'field' => $error->getOrigin()?->getName(),
            ], $result->getFormErrors()),
        ];

        if (null !== ($request = $result->getRequest())) {
            $item->data['request'] = [
                'uri' => $request->getRequestUri(),
                'client_ip' => $request->getClientIp(),
                'referrer' => $request->headers->get('referer'),
                'user_agent' => $request->headers->get('user-agent'),
            ];
        } else {
            $item->data['request'] = null;
        }

        return $item;
    }

    /**
     * @return SerializedItem
     */
    public function __serialize(): array
    {
        return $this->data;
    }

    /**
     * @param SerializedItem $data
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data;
    }
}
