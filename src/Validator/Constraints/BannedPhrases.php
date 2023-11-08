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

use Symfony\Component\Validator\Attribute\HasNamedArguments;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BannedPhrases extends AntiSpamConstraint
{
    /** @var string[] */
    public readonly array $phrases;

    private string $regexp;

    /**
     * @param string|string[] $phrases
     */
    #[HasNamedArguments]
    public function __construct(
        string|array $phrases,
        bool $passive = null,
        bool $stealth = null,
        array $groups = null,
        mixed $payload = null)
    {
        $this->phrases = is_array($phrases) ? $phrases : [$phrases];

        parent::__construct($passive, $stealth, $groups, $payload);
    }

    public function getRegularExpression(): string
    {
        if (!isset($this->regexp)) {
            $this->regexp = sprintf('#(%s)#i', implode('|', array_map(fn (string $v) => preg_quote($v, '#'), $this->phrases)));
        }

        return $this->regexp;
    }
}
