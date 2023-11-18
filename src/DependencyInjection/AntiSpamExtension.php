<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\DependencyInjection;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\AntiSpamBundle;
use Omines\AntiSpamBundle\EventSubscriber\FormProfileSubscriber;
use Omines\AntiSpamBundle\Profile;
use Omines\AntiSpamBundle\Quarantine\Driver\QuarantineDriverInterface;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use Omines\AntiSpamBundle\Validator\Constraints\BannedPhrases;
use Omines\AntiSpamBundle\Validator\Constraints\BannedScripts;
use Omines\AntiSpamBundle\Validator\Constraints\UrlCount;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @infection-ignore-all As infection cannot clear caches reliably mutating the extension has no effect
 */
final class AntiSpamExtension extends Extension implements PrependExtensionInterface
{
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

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $mergedConfig = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('antispam.enabled', $mergedConfig['enabled']);
        foreach ($mergedConfig['profiles'] as $name => $profile) {
            if (null === $profile['passive']) {
                $profile['passive'] = $mergedConfig['passive'];
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
            $container
                ->register($id, Profile::class)
                ->addTag('antispam.profile')
                ->addArgument($name)
                ->addArgument($profile)
                ->addArgument(new Reference(FormProfileSubscriber::class))
            ;
        }

        $quarantineConfig = $mergedConfig['quarantine'];
        $driver = $quarantineConfig['driver'];
        $driverOptions = $quarantineConfig[$driver] ?? $quarantineConfig['options'] ?? [];
        $alias = sprintf('antispam.quarantine.%s', $driver);
        if ($container->hasAlias($alias)) {
            $alias = $container->getAlias($alias);
            $definition = $container->getDefinition((string) $alias);
            $container->setAlias(QuarantineDriverInterface::class, $alias);
        } else {
            $definition = $container->getDefinition($driver);
            $container->setAlias(QuarantineDriverInterface::class, $driver);
        }
        $definition->addMethodCall('setOptions', [$driverOptions]);

        unset($mergedConfig['profiles'], $mergedConfig['quarantine']);
        $container
            ->register(AntiSpam::class, AntiSpam::class)
            ->setArgument(1, $mergedConfig)
            ->setAutowired(true)
        ;
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'form_themes' => ['@AntiSpam/form/widgets.html.twig'],
            ]);
        }
    }

    public function getAlias(): string
    {
        return AntiSpamBundle::ALIAS;
    }
}
