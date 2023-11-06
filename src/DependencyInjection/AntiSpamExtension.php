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
use Omines\AntiSpamBundle\Profile;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @infection-ignore-all As infection does not clear caches mutating the extension has no effect
 */
class AntiSpamExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $mergedConfig = $this->processConfiguration(new Configuration(), $configs);
        foreach ($mergedConfig['profiles'] as $name => $profile) {
            $id = 'antispam.profile.' . $name;
            $container
                ->register($id, Profile::class)
                ->addTag('antispam.profile')
                ->addArgument($name)
                ->addArgument($profile)
            ;
        }

        unset($mergedConfig['profiles']);
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
        return 'antispam'; // underscore is ugly in configs
    }
}
