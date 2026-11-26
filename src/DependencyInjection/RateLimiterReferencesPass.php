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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RateLimiterReferencesPass implements CompilerPassInterface
{
    private const LIMITERS_ARGUMENT = 3;

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds('antispam.profile')) as $id) {
            /** @var list<Reference> $references */
            $references = $container->getDefinition($id)->getArguments()[self::LIMITERS_ARGUMENT] ?? [];
            foreach ($references as $reference) {
                if (!$container->has((string) $reference)) {
                    throw new InvalidConfigurationException(sprintf('Profile "%s" in the antispam configuration uses rate limiter "%s", which is not defined under framework.rate_limiter', mb_substr($id, mb_strlen('antispam.profile.')), mb_substr((string) $reference, mb_strlen('limiter.'))));
                }
            }
        }
    }
}
