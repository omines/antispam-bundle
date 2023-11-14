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

use Omines\AntiSpamBundle\EventSubscriber\FormProfileEventSubscriber;
use Omines\AntiSpamBundle\Validator\Constraints\AntiSpamConstraint;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use Omines\AntiSpamBundle\Validator\Constraints\BannedPhrases;
use Omines\AntiSpamBundle\Validator\Constraints\BannedScripts;
use Omines\AntiSpamBundle\Validator\Constraints\UrlCount;
use Symfony\Component\Validator\Constraint;

/**
 * @phpstan-type BannedMarkupConfig array{html: bool, bbcode: bool}
 * @phpstan-type BannedPhrasesConfig string[]
 * @phpstan-type BannedScriptsConfig array{scripts: Type\Script[], max_percentage: int, max_characters: int}
 * @phpstan-type UrlCountConfig array{max: int, max_identical: ?int}
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
     * @param ProfileConfig $config
     */
    public function __construct(
        private readonly string $name,
        private readonly array $config,
        private readonly FormProfileEventSubscriber $formEventSubscriber,
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

    public function getFormEventSubscriber(): FormProfileEventSubscriber
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
     * Note that constraints are added in order of increasing cost/effectiveness balance so the resulting array
     * can be used Sequentially efficiently. Iow: add new costly ones at the end, cheap ones up front.
     *
     * @return Constraint[]
     */
    protected function buildTextTypeConstraints(): array
    {
        static $types;

        if (!isset($types)) {
            $types = [
                'banned_markup' => fn ($config) => $this->createBannedMarkupConstraint($config),
                'banned_phrases' => fn ($config) => $this->createBannedPhrasesConstraint($config),
                'banned_scripts' => fn ($config) => $this->createBannedScriptsConstraint($config),
                'url_count' => fn ($config) => $this->createUrlCountConstraints($config),
            ];
        }

        $this->constraints = [];
        foreach ($types as $key => $closure) {
            if ($config = $this->config[$key] ?? null) {
                $this->constraints[] = $closure($config);
            }
        }

        return $this->constraints;
    }

    /**
     * @param BannedMarkupConfig $config
     */
    protected function createBannedMarkupConstraint(array $config): AntiSpamConstraint
    {
        return new BannedMarkup(...$config);
    }

    /**
     * @param BannedPhrasesConfig $config
     */
    protected function createBannedPhrasesConstraint(array $config): AntiSpamConstraint
    {
        return new BannedPhrases($config);
    }

    /**
     * @param BannedScriptsConfig $config
     */
    protected function createBannedScriptsConstraint(array $config): AntiSpamConstraint
    {
        return new BannedScripts($config['scripts'], $config['max_percentage'], $config['max_characters']);
    }

    /**
     * @param UrlCountConfig $config
     */
    protected function createUrlCountConstraints(array $config): AntiSpamConstraint
    {
        return new UrlCount(max: $config['max'], maxIdentical: $config['max_identical']);
    }
}
