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

use Omines\AntiSpamBundle\EventSubscriber\FormProfileSubscriber;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use Omines\AntiSpamBundle\Validator\Constraints\BannedPhrases;
use Omines\AntiSpamBundle\Validator\Constraints\BannedScripts;
use Omines\AntiSpamBundle\Validator\Constraints\UrlCount;
use Symfony\Component\Validator\Constraint;

/**
 * @phpstan-type BannedMarkupConfig array{html: bool, bbcode: bool}
 * @phpstan-type BannedPhrasesConfig array{phrases: string[]}
 * @phpstan-type BannedScriptsConfig array{scripts: Type\Script[], maxPercentage: int, maxCharacters: int}
 * @phpstan-type UrlCountConfig array{max: int, maxIdentical: ?int}
 * @phpstan-type HoneypotConfig array{field: string, attributes: array<string, string>}
 * @phpstan-type TimerConfig array{min: int, max: int, field: string}
 * @phpstan-type ProfileConfig array{banned_markup?: BannedMarkupConfig, banned_phrases?: BannedPhrasesConfig,
 *          banned_scripts?: BannedScriptsConfig, honeypot?: HoneypotConfig, max_urls?: UrlCountConfig,
 *          passive: bool, stealth: bool, timer?: TimerConfig}
 */
class Profile
{
    /** @var Constraint[] */
    private array $constraints;

    /**
     * Note that constraints are added in order of increasing cost/effectiveness balance so the resulting array
     * can be used Sequentially efficiently. Iow: add new costly ones at the end, cheap ones up front.
     *
     * @var array<string, class-string>
     */
    public const CONFIG_KEY_TO_VALIDATOR_MAPPING = [
        'banned_markup' => BannedMarkup::class,
        'url_count' => UrlCount::class,
        'banned_phrases' => BannedPhrases::class,
        'banned_scripts' => BannedScripts::class,
    ];

    /**
     * @param ProfileConfig $config
     */
    public function __construct(
        private readonly string $name,
        private readonly array $config,
        private readonly FormProfileSubscriber $formEventSubscriber,
    ) {
        $formEventSubscriber->setProfile($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getFormEventSubscriber(): FormProfileSubscriber
    {
        return $this->formEventSubscriber;
    }

    /**
     * @return ?HoneypotConfig
     */
    public function getHoneypotConfig(): ?array
    {
        return $this->config['honeypot'] ?? null;
    }

    public function getPassive(): bool
    {
        return $this->config['passive'];
    }

    public function getStealth(): bool
    {
        return $this->config['stealth'];
    }

    /**
     * @return ?TimerConfig
     */
    public function getTimerConfig(): ?array
    {
        return $this->config['timer'] ?? null;
    }

    /**
     * @return Constraint[]
     */
    public function getTextTypeConstraints(): array
    {
        return $this->constraints ?? $this->buildTextTypeConstraints();
    }

    /**
     * @return Constraint[]
     */
    protected function buildTextTypeConstraints(): array
    {
        $this->constraints = [];
        foreach (self::CONFIG_KEY_TO_VALIDATOR_MAPPING as $key => $class) {
            if ($config = $this->config[$key] ?? null) {
                /* @phpstan-ignore-next-line poor PHPStan goes bonkers over this */
                $this->constraints[] = new $class(...$config);
            }
        }

        return $this->constraints;
    }
}
