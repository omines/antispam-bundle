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
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @phpstan-type AntiSpamProfile array{passive: ?bool, banned_markup: array<string, mixed>, url_count: array<string, mixed>, banned_phrases: array<string, mixed>, banned_scripts: array<string, mixed>}
 * @phpstan-type AntiSpamConfiguration array{enabled: bool, passive: bool, profiles: array<string, AntiSpamProfile>}
 */
class AntiSpamBundle extends AbstractBundle
{
    public const ANTISPAM_ALIAS = 'antispam';
    public const MONOLOG_CHANNEL_NAME = 'antispam';
    public const TRANSLATION_DOMAIN = 'antispam';

    protected string $extensionAlias = self::ANTISPAM_ALIAS;

    /**
     * Note that constraints are added in order of increasing cost/effectiveness balance so the resulting array
     * can be used Sequentially efficiently. Iow: add new costly ones at the end, cheap ones up front.
     */
    public const CONFIG_KEY_TO_VALIDATOR_MAPPING = [
        'banned_markup' => BannedMarkup::class,
        'url_count' => UrlCount::class,
        'banned_phrases' => BannedPhrases::class,
        'banned_scripts' => BannedScripts::class,
    ];

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AddEventAliasesPass(AntiSpamEvents::ALIASES));
    }

    /**
     * @param AntiSpamConfiguration $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();
        $services->defaults()->autowire()->autoconfigure();
        $services->instanceof(LoggerAwareInterface::class)->tag('monolog.logger', ['channel' => self::MONOLOG_CHANNEL_NAME]);
        $services->load(__NAMESPACE__ . '\\', __DIR__);

        $builder->setParameter('antispam.enabled', $config['enabled']);
        foreach ($config['profiles'] as $name => $profile) {
            if (null === $profile['passive']) {
                $profile['passive'] = $config['passive'];
            }

            foreach (array_keys(self::CONFIG_KEY_TO_VALIDATOR_MAPPING) as $key) {
                if (array_key_exists($key, $profile)) {
                    $newConfig = [];
                    foreach ($profile[$key] as $param => $value) {
                        $newConfig[lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $param))))] = $value;
                    }
                    $profile[$key] = $newConfig;
                }
            }

            $id = 'antispam.profile.' . $name;
            $builder
                ->register($id, Profile::class)
                ->addTag('antispam.profile')
                ->addArgument($name)
                ->addArgument($profile)
                ->addArgument(new Reference(FormProfileSubscriber::class))
            ;
        }

        unset($config['profiles']);
        $builder
            ->register(AntiSpam::class, AntiSpam::class)
            ->setArgument(1, $config)
            ->setAutowired(true)
        ;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'form_themes' => ['@AntiSpam/form/widgets.html.twig'],
            ]);
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        Configuration::load($definition->rootNode());
    }
}
