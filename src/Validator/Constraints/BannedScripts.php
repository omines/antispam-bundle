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
    /**
     * @var string
     * @todo Make a typed constant when PHP <8.3 support is dropped
     */
    public const MINIMUM_PCRE_VERSION = '10.40';

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
            // Some distros use exotic version strings like "10.40 2022-04-14" which version_compare can't handle, hence the extra check
            if (version_compare(static::MINIMUM_PCRE_VERSION, PCRE_VERSION) > 0 && strtok(PCRE_VERSION, ' ') !== static::MINIMUM_PCRE_VERSION) {
                throw new \LogicException(sprintf('PHP is using PCRE version %s but requires at least version %s to detect banned scripts. Update your PHP installation and/or operating system.', PCRE_VERSION, static::MINIMUM_PCRE_VERSION));
            }
            $this->characterClass = sprintf('[%s]', implode('', array_map(fn (Script $script) => sprintf('\\p{%s}', $script->value), $this->scripts)));
        }

        return $this->characterClass;
    }

    public function getReadableScripts(): string
    {
        return implode(', ', array_map(fn (Script $script) => $script->name, $this->scripts));
    }
}
