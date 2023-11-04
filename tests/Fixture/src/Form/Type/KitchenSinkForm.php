<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixture\Form\Type;

use Omines\AntiSpamBundle\Form\Type\HoneypotType;
use Omines\AntiSpamBundle\Form\Type\SubmitTimerType;
use Omines\AntiSpamBundle\Type\Script;
use Omines\AntiSpamBundle\Validator\Constraints\BannedScripts;
use Omines\AntiSpamBundle\Validator\Constraints\UrlCount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class KitchenSinkForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Length(min: 3, max: 100),
                ],
            ])
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class, [
                'attr' => [
                    'cols' => 80,
                    'rows' => 6,
                ],
                'constraints' => [
                    new Length(min: 10, max: 1000),
                    new BannedScripts([Script::Cyrillic, Script::Hebrew], maxPercentage: 25),
                    new UrlCount(1),
                ],
            ])
            ->add('honeypot', HoneypotType::class)
            ->add('timer', SubmitTimerType::class, [
                'min' => 5,
            ])
        ;
    }
}
