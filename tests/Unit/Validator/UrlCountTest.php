<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Omines\AntiSpamBundle\Validator\Constraints\AntiSpamConstraintValidator;
use Omines\AntiSpamBundle\Validator\Constraints\UrlCount;
use Omines\AntiSpamBundle\Validator\Constraints\UrlCountValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @extends ConstraintValidatorTestCase<UrlCountValidator>
 */
#[CoversClass(UrlCount::class)]
#[CoversClass(UrlCountValidator::class)]
#[CoversClass(AntiSpamConstraintValidator::class)]
class UrlCountTest extends ConstraintValidatorTestCase
{
    protected function getValidatorClass(): string
    {
        return UrlCountValidator::class;
    }

    public function testValidatorMismatchThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->constraintValidator->validate(684, new Length(min: 3));
    }

    public function testOnlyStringablesAndNullAreAccepted(): void
    {
        $constraint = new UrlCount();

        $this->validate('aap', $constraint);
        $this->assertNoViolation();

        $this->validate(null, $constraint);
        $this->assertNoViolation();

        $this->validate(684, $constraint);
        $this->assertNoViolation();

        $this->validate(new class implements \Stringable {
            public function __toString(): string
            {
                return 'foo';
            }
        }, $constraint);
        $this->assertNoViolation();

        $this->expectException(UnexpectedValueException::class);
        $this->constraintValidator->validate($this, $constraint);
    }

    public function testStealthedValidationError(): void
    {
        $errors = $this->expectViolations('Please visit https://www.example.org', new UrlCount(stealth: true));
        $this->assertStringContainsString('The submitted value could not be processed', (string) $errors->get(0)->getMessage());
    }

    public function testFormattedMessage(): void
    {
        $text = 'Please visit https://www.example.org and https://www.example.org';

        $errors = $this->expectViolations($text, new UrlCount(max: 1));
        $this->assertSame('The value contains 2 URLs. It should have at most 1.', (string) $errors->get(0)->getMessage());

        $errors = $this->expectViolations($text, new UrlCount(max: 2, maxIdentical: 1));
        $this->assertSame('The value contains URL https://www.example.org 2 times, which is more than the 1 allowed.', (string) $errors->get(0)->getMessage());
    }

    #[DataProvider('provideUrlCounts')]
    public function testUrlCountValidation(UrlCount $constraint, string $value, int $urlCount, int $expectedViolations = 0): void
    {
        $errors = $this->expectViolations($value, $constraint, $expectedViolations ? 1 : 0);
        if ($expectedViolations) {
            $this->assertEquals($expectedViolations, $errors->get(0)->getParameters()['count']);
        }
    }

    /**
     * @return array<string, array{0: UrlCount, 1: string, 2: int, 3?: int}>
     */
    public static function provideUrlCounts(): array
    {
        return [
            'no URLs, default allowed' => [
                new UrlCount(), 'Test without URL', 0,
            ],
            'no URLs, 5 allowed' => [
                new UrlCount(5), 'Test without URL', 0,
            ],
            '1 URL, default allowed' => [
                new UrlCount(), 'Test with URL http://example.org in text', 1, 1,
            ],
            '1 URL, 5 allowed' => [
                new UrlCount(5), 'Test with URL http://example.org in text', 1, 0,
            ],
            '2 URL, 1 allowed' => [
                new UrlCount(1), 'Test with http://foo.org/bar and https://bar.org/foo in text', 1, 2,
            ],
            '1 identical allowed' => [
                new UrlCount(10, 1), 'Test with https://foo.org/bar and https://foo.org/bar in text', 1, 2,
            ],
            '1 identical allowed, trailing characters' => [
                new UrlCount(10, 1), 'Test with https://foo.org/bar, and https://foo.org/bar', 1, 2,
            ],
        ];
    }
}
