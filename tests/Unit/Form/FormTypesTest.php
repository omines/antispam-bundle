<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Form;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixture\Form\Type\KitchenSinkForm;

class FormTypesTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    /**
     * @param array<string, mixed> $options
     */
    private static function createForm(string $class, array $options = []): FormBuilderInterface
    {
        $factory = static::getContainer()->get(FormFactoryInterface::class);
        assert($factory instanceof FormFactoryInterface);

        return $factory->createBuilder($class, options: $options);
    }

    public function testNonInteractiveFormTypesAreUnmapped(): void
    {
        $form = $this->createForm(KitchenSinkForm::class)->getForm();
        $this->assertTrue($form->has('honeypot'));
        $this->assertTrue($form->has('timer'));

        $view = $form->createView();
        self::mockTime('+10 seconds');

        $request = Request::create('/', method: 'POST', parameters: [
            'kitchen_sink_form' => [
                'name' => 'John Doe',
                'email' => 'foo@example.org',
                'message' => 'Message for testing',
                'timer' => $view['timer']->vars['value'],
            ],
        ]);
        $form->handleRequest($request);
        $this->assertTrue($form->isValid() && $form->isSubmitted());

        $this->assertIsArray($data = $form->getData());
        $this->assertArrayNotHasKey('honeypot', $data);
        $this->assertArrayNotHasKey('timer', $data);
    }

    public function testExternalFormErrorsAreIgnored(): void
    {
        $form = $this->createForm(KitchenSinkForm::class, [
            'antispam_profile' => 'test2',
        ]);
        $form->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            // Insert a fake error that should not get replaced by stealth
            $event->getForm()->addError(new FormError('Foo bar'));
        });
        $form = $form->getForm();
        $view = $form->createView();

        $request = Request::create('/', method: 'POST', parameters: [
            'kitchen_sink_form' => [
                'name' => 'John Doe',
                'email' => 'foo@example.org',
                'message' => 'Message for testing',
                'timer' => $view['timer']->vars['value'],
            ],
        ]);
        $form->handleRequest($request);
        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($errors = $form->getErrors());
        $this->assertSame('Foo bar', $form->getErrors()[0]->getMessage());
    }
}
