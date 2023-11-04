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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixture\Form\Type\KitchenSinkForm;

class FormTypesTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    public function testNonInteractiveFormTypesAreUnmapped(): void
    {
        $factory = static::getContainer()->get(FormFactoryInterface::class);
        $this->assertInstanceOf(FormFactoryInterface::class, $factory);

        $form = $factory->create(KitchenSinkForm::class);
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
}
