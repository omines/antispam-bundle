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

use Omines\AntiSpamBundle\DependencyInjection\AntiSpamExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AntiSpamBundle extends AbstractBundle
{
    public const ALIAS = 'antispam';
    public const TRANSLATION_DOMAIN = 'antispam';

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AntiSpamExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new AddEventAliasesPass(AntiSpamEvents::ALIASES))
        ;
    }
}
