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
use Omines\AntiSpamBundle\Form\Type\HoneypotType;
use Omines\AntiSpamBundle\Form\Type\SubmitTimerType;
use Omines\AntiSpamBundle\Profile;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Sequentially;

class FormTypeAntiSpamExtension extends AbstractTypeExtension
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
                return is_string($profile) ? $this->antiSpam->getProfile($profile) : null;
            })
        ;
    }

    /**
     * @param array{antispam_profile: ?Profile, compound: bool} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Only act on compound form types with a profile set
        if ($options['compound'] && null !== ($profile = $options['antispam_profile'])) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($profile) {
                /*
                 * Symfony Forms does not expose modifying options after creation, so the only way
                 * to reliably modify options based on outside factors is to recreate the child
                 * from its parent with updated options. Hence why the code below is so complex
                 * instead of just creating a TextType extension. This is thankfully completely
                 * safe as the Forms recognize which elements were added in the event handler.
                 */
                foreach ($event->getForm()->all() as $name => $child) {
                    $config = $child->getConfig();
                    $type = $config->getType();
                    while (null !== $type) {
                        if ($type->getInnerType() instanceof TextType) {
                            $options = $config->getOptions();
                            $this->applyTextTypeProfile($options, $profile);
                            $event->getForm()->add($name, $type->getInnerType()::class, $options);
                            /* @infection-ignore-all don't try to make this into an endless loop kthnxbye */
                            break;
                        }
                        $type = $type->getParent();
                    }
                }
            });

            $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($profile) {
                $this->applyFormTypeProfile($event->getForm(), $profile);
            });
        }
    }

    /**
     * @param array{constraints: array<Constraint>} $options
     */
    protected function applyTextTypeProfile(array &$options, Profile $profile): void
    {
        if (!empty($constraints = $profile->getTextTypeConstraints())) {
            $options['constraints'][] = new Sequentially($constraints);
        }
    }

    protected function applyFormTypeProfile(FormInterface $form, Profile $profile): void
    {
        // Add honeypot field as required
        if ($honeypot = $profile->getHoneypotConfig()) {
            $form->add(self::uniqueFieldName($form, $honeypot['field']), HoneypotType::class);
        }

        // Add hidden and signed timer field
        if ($timer = $profile->getTimerConfig()) {
            if ($form->isRoot()) {
                $form->add(self::uniqueFieldName($form, $timer['field']), SubmitTimerType::class, [
                    'min' => $timer['min'],
                    'max' => $timer['max'],
                ]);
            } else {
                $this->logger?->info(sprintf('Ignoring timer configuration from profile "%s" on embedded form', $profile->getName()));
            }
        }
    }

    /**
     * @infection-ignore-all Infection creates an endless loop here
     */
    private static function uniqueFieldName(FormInterface $form, string $basename): string
    {
        $field = $basename;
        $counter = 0;
        while ($form->has($field)) {
            $field = $basename . ++$counter;
        }

        return $field;
    }
}
