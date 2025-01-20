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

use Omines\AntiSpamBundle\AntiSpamEvents;
use Omines\AntiSpamBundle\Event\FormProcessedEvent;
use Omines\AntiSpamBundle\Form\Type\HoneypotType;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tests\Fixture\Form\Type\KitchenSinkForm;

class FormTypesTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    private static FormFactoryInterface $formFactory;

    public static function setUpBeforeClass(): void
    {
        self::$formFactory = static::getContainer()->get(FormFactoryInterface::class);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function createForm(string $class, array $options = []): FormBuilderInterface
    {
        return self::$formFactory->createBuilder($class, options: $options);
    }

    private static function getEventDispatcher(): EventDispatcherInterface
    {
        return static::getContainer()->get(EventDispatcherInterface::class);
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
        $this->assertSame('Foo bar', $errors[0]->getMessage());
    }

    public function testNestedProfileResolution(): void
    {
        $form = self::$formFactory->createBuilder();
        $child1 = $form->add('child1', FormType::class, ['antispam_profile' => 'stealthy_empty'])->get('child1');
        $child2 = $child1->add('child2', FormType::class)->get('child2');
        $child3 = $child2->add('child3', FormType::class)->get('child3');
        $child3->add('email', EmailType::class);
        $child3->add('pooh', HoneypotType::class);

        $form = $form->getForm();
        $view = $form->createView();

        $request = Request::create('/', method: 'POST', parameters: [
            'form' => ['child1' => ['child2' => ['child3' => [
                'email' => 'spam@spam.org',
                'pooh' => 'gotcha!',
            ]]]],
        ]);
        $form->handleRequest($request);

        $this->assertFalse($form->isSubmitted() && $form->isValid());
        $this->assertNotEmpty($errors = $form->getErrors());
        $this->assertStringContainsString('could not be processed', $errors[0]->getMessage());
    }

    /**
     * Run in separate process as we modify global event dispatcher state.
     */
    #[RunInSeparateProcess]
    public function testEventsAreDispatched(): void
    {
        $form = $this->createForm(KitchenSinkForm::class, [
            'antispam_profile' => 'test1',
        ])->getForm();

        $view = $form->createView();
        self::mockTime('+10 seconds');

        static::getEventDispatcher()->addListener(AntiSpamEvents::FORM_PROCESSED, function (FormProcessedEvent $event) {
            self::assertTrue($event->getResult()->isSpam());
        });

        $request = Request::create('/', method: 'POST', parameters: [
            'kitchen_sink_form' => [
                'name' => 'John Doe',
                'email' => 'foo@example.org',
                'message' => 'Message for testing',
                'timer' => $view['timer']->vars['value'],
            ],
        ]);
        $form->handleRequest($request);
        $form->isValid() && $form->isSubmitted();
    }
}
