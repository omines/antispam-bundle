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

use Omines\AntiSpamBundle\Form\Type\SubmitTimerType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[CoversClass(SubmitTimerType::class)]
class SubmitTimerTypeTest extends KernelTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideSubmitTimerConfigurations')]
    public function testSubmitTimerConfiguration(array $options, ?string $expectedError = null): void
    {
        $factory = static::getContainer()->get(FormFactoryInterface::class);
        $this->assertInstanceOf(FormFactoryInterface::class, $factory);

        if (null !== $expectedError) {
            $this->expectException(InvalidOptionsException::class);
            $this->expectExceptionMessage($expectedError);
        }
        $this->assertInstanceOf(FormInterface::class, $factory->create(SubmitTimerType::class, options: $options));
    }

    /**
     * @return array{0: array<string, mixed>, 1?: string}[]
     */
    public static function provideSubmitTimerConfigurations(): array
    {
        return [
            'min not a float' => [['min' => 'foo'], 'expected to be of type "float"'],
            'max not a float' => [['max' => 'bar'], 'expected to be of type "float"'],
            'secret not a string' => [['secret' => 684], 'expected to be of type "string"'],
        ];
    }
}
