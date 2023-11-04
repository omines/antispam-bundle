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

use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubmitTimerType extends NonInteractiveAntiSpamType
{
    use ClockAwareTrait;

    public const DEFAULT_MIN = 3;
    public const DEFAULT_MAX = 3600;

    private const NO_IP = 'no-ip';

    public function __construct(
        #[Autowire(param: 'kernel.secret')]
        private readonly string $secret,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'min' => self::DEFAULT_MIN,
            'max' => self::DEFAULT_MAX,
            'secret' => $this->secret,
        ]);

        $resolver->setAllowedTypes('min', 'int');
        $resolver->setAllowedTypes('max', 'int');
        $resolver->setAllowedTypes('secret', 'string');
    }

    /**
     * @param array<string, scalar|\Stringable> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();
            assert(is_string($data));

            if (empty($data) || false === ($decoded = \base64_decode($data, true))) {
                $this->createFormError($form, 'form.timer.corrupted', cause: 'Data could not be decoded');
            } elseif (3 !== count($parts = explode('|', $decoded))) {
                $this->createFormError($form, 'form.timer.corrupted', cause: 'Data must contain 3 elements');
            } else {
                [$ip, $ts, $hash] = $parts;

                $secret = $options['secret'];
                if ($hash !== hash('sha256', "$ts|$ip|$secret")) {
                    $this->createFormError($form, 'form.timer.corrupted', cause: 'Hash verification failed');

                    return;
                }

                $currentIp = $this->requestStack->getMainRequest()?->getClientIp() ?? self::NO_IP;
                if (null !== $currentIp && $ip !== $currentIp) {
                    $this->createFormError($form, 'form.timer.mismatch_ip', ['original' => $ip, 'current' => $currentIp],
                        cause: "The client IP address changed from $ip to $currentIp");
                }

                $age = $this->now()->getTimestamp() - intval($ts);
                if ($age < $options['min']) {
                    $this->createFormError($form, 'form.timer.too_fast', cause: "Form was submitted after $age seconds");
                } elseif ($age > $options['max']) {
                    $this->createFormError($form, 'form.timer.too_slow', cause: "Form was submitted after $age seconds");
                }
            }
        });
    }

    /**
     * @param array<string, scalar|\Stringable> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $ip = $this->requestStack->getMainRequest()?->getClientIp() ?? self::NO_IP;
        $ts = $this->now()->getTimestamp();
        $secret = $options['secret'];
        $view->vars['value'] = \base64_encode(implode('|', [$ip, $ts, hash('sha256', "$ts|$ip|$secret")]));
    }

    public function getBlockPrefix(): string
    {
        return 'antispam_submit_timer';
    }
}
