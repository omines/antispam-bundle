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
    public const NOT_ALLOWED_ERROR = '2d0a5852-fcb0-4362-8d23-c77a46bd1835';
    public const TOO_MANY_CHARACTERS_ERROR = 'aacf0d8b-f220-447b-9340-973f4ec54cc1';
    public const TOO_HIGH_PERCENTAGE_ERROR = '66e86947-3275-4157-a3b1-44b5e492d818';

    /** @var Script[] */
    public array $scripts;

    private string $characterClass;

    /**
     * @param Script|Script[] $scripts
     */
    #[HasNamedArguments]
    public function __construct(
        Script|array $scripts,
        public int $maxPercentage = 0,
        public ?int $maxCharacters = null,
        bool $passive = null,
        bool $stealth = null,
        array $groups = null,
        mixed $payload = null)
    {
        $this->scripts = is_array($scripts) ? $scripts : [$scripts];

        parent::__construct($passive, $stealth, $groups, $payload);
    }

    public function getCharacterClass(): string
    {
        if (!isset($this->characterClass)) {
            $this->scripts = array_map(fn (Script|string $v) => is_string($v) ? Script::from($v) : $v, $this->scripts);
            $this->characterClass = sprintf('[%s]', implode('', array_map(fn (Script $script) => sprintf('\p{%s}', $script->value), $this->scripts)));
        }

        return $this->characterClass;
    }

    public function getReadableScripts(): string
    {
        return implode(', ', array_map(fn (Script $script) => $script->name, $this->scripts));
    }
}
