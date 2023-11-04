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

use Omines\AntiSpamBundle\Validator\Constraints\BannedPhrases;
use Omines\AntiSpamBundle\Validator\Constraints\BannedPhrasesValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @extends ConstraintValidatorTestCase<BannedPhrasesValidator>
 */
#[CoversClass(BannedPhrases::class)]
#[CoversClass(BannedPhrasesValidator::class)]
class BannedPhrasesTest extends ConstraintValidatorTestCase
{
    protected function getValidatorClass(): string
    {
        return BannedPhrasesValidator::class;
    }

    public function testValidatorMismatchThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->constraintValidator->validate(684, new Length(min: 3));
    }

    public function testOnlyStringablesAndNullAreAccepted(): void
    {
        $constraint = new BannedPhrases(['foo', 'bar']);

        $this->validate('aap', $constraint);
        $this->assertNoViolation();

        $this->validate(null, $constraint);
        $this->assertNoViolation();

        $this->validate(684, $constraint);
        $this->assertNoViolation();

        $this->validate(new class() implements \Stringable {
            public function __toString(): string
            {
                return 'example-baz';
            }
        }, $constraint);
        $this->assertNoViolation();

        $this->expectException(UnexpectedValueException::class);
        $this->constraintValidator->validate($this, $constraint);
    }

    #[DataProvider('provideViolatingPhrases')]
    public function testBannedPhraseDetection(string $text, BannedPhrases $constraint): void
    {
        $this->expectViolations($text, $constraint);
    }

    /**
     * @return array<int, array{0: string, 1: BannedPhrases}>
     */
    public static function provideViolatingPhrases(): array
    {
        return [
            ['The foo and bar are strong.', new BannedPhrases(['foo', 'bar'])],
            ['The foo and bar are strong.', new BannedPhrases(['foo', 'baz'])],
            ['The foo and bar are strong.', new BannedPhrases(['fool', 'bar'])],
            ['The foo and bar are strong.', new BannedPhrases('foo')],
            ['The foo and bar are strong.', new BannedPhrases('foo and bar')],
            ['The #foo#and#bar#are#strong', new BannedPhrases('#and#')],
            ['#does it (look)?.*like a regexp?#i', new BannedPhrases('(look)?.*')],
        ];
    }
}
