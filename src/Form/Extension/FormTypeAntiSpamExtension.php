<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Form\Extension;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\Profile;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeAntiSpamExtension extends AbstractTypeExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly AntiSpam $antiSpam)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('antispam_profile')
            ->default(null)
            ->allowedTypes(Profile::class, 'string', 'null')
            ->normalize(function (Options $options, Profile|string|null $profile) {
                return is_string($profile) ? $this->antiSpam->getProfile($profile) : $profile;
            })
        ;
    }

    /**
     * @param array{antispam_profile: ?Profile, compound: bool} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->antiSpam->isEnabled()) {
            return;
        }

        // Only act on compound form types with a profile set
        if ($options['compound'] && null !== ($profile = $options['antispam_profile'])) {
            $builder->addEventSubscriber($profile->getFormEventSubscriber());
        }
    }
}
