<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Form\Type;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\Form\AntiSpamFormError;
use Omines\AntiSpamBundle\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractAntiSpamType extends AbstractType
{
    protected AntiSpam $antiSpam;
    protected TranslatorInterface $translator;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);
    }

    #[Required]
    public function setAntiSpam(AntiSpam $antiSpam): void
    {
        $this->antiSpam = $antiSpam;
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createFormError(FormInterface $form, string $template, array $parameters = [], string $cause = null): void
    {
        $stealth = (null !== ($profile = self::getProfile($form))) ? $profile->getStealth() : $this->antiSpam->getStealth();
        if ($stealth) {
            $message = $this->translator->trans('form.stealthed', domain: 'antispam');
        } else {
            $message = $this->translator->trans($template, $parameters, domain: 'antispam');
        }
        $form->addError(new AntiSpamFormError($message, $template, $parameters, cause: $cause));
    }

    private static function getProfile(FormInterface $form): ?Profile
    {
        do {
            if (null !== ($profile = $form->getConfig()->getOption('antispam_profile'))) {
                assert($profile instanceof Profile);

                return $profile;
            }
        } while ($form = $form->getParent());

        return null;
    }
}
