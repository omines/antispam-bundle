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

use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Tests\Fixture\Form\Type\KitchenSinkForm;

class FormProcessingTest extends KernelTestCase
{
    public function testOnlySubmittedFormsHaveResults(): void
    {
        $factory = static::getContainer()->get(FormFactoryInterface::class);
        assert($factory instanceof FormFactoryInterface);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('submitted form');
        new AntiSpamFormResult($factory->create(KitchenSinkForm::class));
    }

    public function testFormsMustImplementClearableErrorsInterface(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $result = new AntiSpamFormResult($form);
        $this->assertSame($form, $result->getForm());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ClearableErrorsInterface');
        $result->clearAntiSpamErrors();
    }
}
