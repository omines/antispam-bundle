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

use Omines\AntiSpamBundle\Form\Type\SubmitTimerType;
use Omines\AntiSpamBundle\Type\Script;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class Configuration
{
    public static function load(ArrayNodeDefinition $rootNode): void
    {
        $children = $rootNode
            ->children()
                ->booleanNode('passive')
                    ->info('Global default for whether included components should cause hard failures')
                    ->defaultFalse()
                ->end()
                ->booleanNode('stealth')
                    ->info('Global default for whether included components issue verbose or stealthy error messages')
                    ->defaultFalse()
                ->end()
                ->booleanNode('enabled')
                    ->info('Allows you to globally disable all bundle functions, specifically for functional testing')
                    ->defaultTrue()
                ->end()
        ;

        self::addProfilesSection($children);
    }

    private static function addProfilesSection(NodeBuilder $rootNode): void
    {
        $profile = $rootNode
            ->arrayNode('profiles')
                ->info('A named list of different profiles used throughout your application')
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                    ->info('Name the profile')
                    ->validate()->always(function (array $profile) {
                        if (empty($profile['banned_phrases'])) {
                            unset($profile['banned_phrases']);
                        }

                        return $profile;
                    })
                    ->end()
                    ->children()
                        ->booleanNode('stealth')
                            ->info('Defines whether measures in this profile issue stealthy error messages')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('passive')
                            ->info('Passive mode will not make any of the included checks actually fail validation, they will still be logged. Null inherits global setting')
                            ->defaultNull()
                        ->end()
        ;

        // Add profile subsections
        self::addBannedMarkupSection($profile);
        self::addBannedPhrasesSection($profile);
        self::addBannedScriptsSection($profile);
        self::addHoneypotSection($profile);
        self::addTimerSection($profile);
        self::addUrlCountSection($profile);
    }

    private static function addBannedMarkupSection(NodeBuilder $profile): void
    {
        $profile
            ->arrayNode('banned_markup')
                ->info('Defines whether to disallow content resembling markup languages like HTML and BBCode')
                ->children()
                    ->booleanNode('html')->defaultTrue()->end()
                    ->booleanNode('bbcode')->defaultTrue()->end()
                ->end()
            ->end()
        ;
    }

    private static function addBannedPhrasesSection(NodeBuilder $profile): void
    {
        $profile
            ->arrayNode('banned_phrases')
                ->info('Simple array of phrases which are rejected when encountered in a submitted text field')
                ->beforeNormalization()
                    ->always(fn (array|string $v) => isset($v[0]) ? ['phrases' => is_string($v) ? [$v] : $v] : $v)
                ->end()
                ->children()
                    ->arrayNode('phrases')
                        ->requiresAtLeastOneElement()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private static function addBannedScriptsSection(NodeBuilder $profile): void
    {
        $profile
            ->arrayNode('banned_scripts')
                ->info('Banned script types, like Cyrillic or Arabic (see docs for commonly used ISO 15924 names)')
                ->beforeNormalization()
                    ->always(fn (array|string $v) => isset($v[0]) ? ['scripts' => is_string($v) ? [$v] : $v] : $v)
                ->end()
                ->children()
                    ->arrayNode('scripts')
                        ->requiresAtLeastOneElement()
                        ->scalarPrototype()
                            ->validate()->always(function (string $v) {
                                if (null === Script::tryFrom($v = mb_strtolower($v))) {
                                    throw new InvalidConfigurationException(sprintf('"%s" is not a valid ISO-15924 script name, look in class %s for all valid options', $v, Script::class));
                                }

                                return $v;
                            })->end()
                        ->end()
                    ->end()
                    ->integerNode('max_characters')->min(0)->defaultNull()->end()
                    ->floatNode('max_percentage')->min(0)->max(100)->defaultValue(0)->end()
                ->end()
            ->end()
        ;
    }

    private static function addHoneypotSection(NodeBuilder $profile): void
    {
        $profile
            ->arrayNode('honeypot')
                ->info('Inject an invisible honeypot field in forms, baiting spambots to fill it in')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(fn (string $v) => ['field' => $v])
                    ->end()
                    ->children()
                        ->scalarNode('field')
                            ->info('Base name of the injected field')
                            ->isRequired()
                        ->end()
                        ->arrayNode('attributes')
                            ->useAttributeAsKey('name')
                            ->defaultValue(['style' => 'display:none'])
                            ->requiresAtLeastOneElement()
                            ->scalarPrototype()->end()
                            ->validate()->always(function (array $v) {
                                foreach ($v as $key => $value) {
                                    if (!is_string($key) || !is_string($value)) {
                                        throw new InvalidConfigurationException('Honeypot attributes must be an array with string keys and values');
                                    }
                                }

                                return $v;
                            })->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private static function addTimerSection(NodeBuilder $profile): void
    {
        $profile
            ->arrayNode('timer')
                ->info('Verify that time between retrieval and submission of a form is within human boundaries')
                ->children()
                    ->scalarNode('field')
                        ->info('Base name of the injected field')
                        ->defaultValue('_validation')
                    ->end()
                    ->floatNode('min')->defaultValue(SubmitTimerType::DEFAULT_MIN)->min(0)->end()
                    ->floatNode('max')->defaultValue(SubmitTimerType::DEFAULT_MAX)->min(60)->end()
                ->end()
            ->end()
        ;
    }

    private static function addUrlCountSection(NodeBuilder $profile): void
    {
        $profile
            ->arrayNode('url_count')
                ->info('Configure limits to number of URLs permitted in text fields')
                ->beforeNormalization()
                    ->always(fn (mixed $v) => is_int($v) ? ['max' => $v] : $v)
                ->end()
                ->children()
                    ->integerNode('max')
                        ->info('Maximum number of URLs accepted per text field')
                        ->defaultNull()->min(0)
                    ->end()
                    ->integerNode('max_identical')
                        ->info('Maximum number of identical URLs accepted per text field')
                        ->defaultNull()->min(0)
                    ->end()
                ->end()
            ->end()
        ;
    }
}
