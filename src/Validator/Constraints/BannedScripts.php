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

use Omines\AntiSpamBundle\Type\Script;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BannedScripts extends AntiSpamConstraint
{
    /** @var Script[] */
    public array $scripts;

    private string $characterClass;

    /**
     * @param string|Script|array<string|Script> $scripts
     */
    #[HasNamedArguments]
    public function __construct(
        Script|string|array $scripts,
        public int $maxPercentage = 0,
        public ?int $maxCharacters = null,
        ?bool $passive = null,
        ?bool $stealth = null,
        ?array $groups = null,
        mixed $payload = null)
    {
        $this->scripts = array_map(fn ($v) => is_string($v) ? Script::from($v) : $v, is_array($scripts) ? $scripts : [$scripts]);

        parent::__construct($passive, $stealth, $groups, $payload);
    }

    public function getCharacterClass(): string
    {
        if (!isset($this->characterClass)) {
            $this->characterClass = sprintf('[%s]', implode('', array_map(fn (Script $script) => sprintf('\\p{%s}', $script->value), $this->scripts)));
        }

        return $this->characterClass;
    }

    public function getReadableScripts(): string
    {
        return implode(', ', array_map(fn (Script $script) => $script->name, $this->scripts));
    }
}
