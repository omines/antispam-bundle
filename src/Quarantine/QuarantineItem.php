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
 * @phpstan-type SerializedError array{message: string, cause: ?string, field: ?string}
 * @phpstan-type SerializedRequest array{uri: string, client_ip: ?string, referrer: ?string, user_agent: ?string}
 * @phpstan-type QuarantineArray array{timestamp: string, is_spam: bool, data: mixed, antispam: SerializedError[], other: SerializedError[], request: ?SerializedRequest}
 */
final class QuarantineItem
{
    /**
     * @param QuarantineArray $data
     */
    private function __construct(private readonly array $data)
    {
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->data['timestamp']);
    }

    /**
     * @param QuarantineArray $array
     */
    public static function fromArray(array $array): self
    {
        return new self($array);
    }

    public static function fromResult(AntiSpamFormResult $result): self
    {
        return new self([
            'timestamp' => $result->getTimestamp()->format('c'),
            'is_spam' => $result->hasAntiSpamErrors(),
            'data' => $result->getForm()->getData(),
            'antispam' => self::serializeFormErrors($result->getAntiSpamErrors()),
            'other' => self::serializeFormErrors($result->getFormErrors()),
            'request' => (null === ($request = $result->getRequest())) ? null : [
                'uri' => $request->getRequestUri(),
                'client_ip' => $request->getClientIp(),
                'referrer' => $request->headers->get('referer'),
                'user_agent' => $request->headers->get('user-agent'),
            ],
        ]);
    }

    /**
     * @param FormError[] $errors
     * @return SerializedError[]
     */
    private static function serializeFormErrors(array $errors): array
    {
        return array_map(fn (FormError $error) => [
            'message' => $error->getMessage(),
            'cause' => is_string($error->getCause()) ? $error->getCause() : null,
            'field' => $error->getOrigin()?->getName(),
        ], $errors);
    }

    /**
     * @return QuarantineArray
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
