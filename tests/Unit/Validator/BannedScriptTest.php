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

use Omines\AntiSpamBundle\Type\Script;
use Omines\AntiSpamBundle\Validator\Constraints\BannedScripts;
use Omines\AntiSpamBundle\Validator\Constraints\BannedScriptsValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @extends ConstraintValidatorTestCase<BannedScriptsValidator>
 */
#[CoversClass(BannedScripts::class)]
#[CoversClass(BannedScriptsValidator::class)]
class BannedScriptTest extends ConstraintValidatorTestCase
{
    private const SAMPLE_LATIN = 'An example sentence using only Latin characters';
    private const SAMPLE_ARABIC = 'مثال على النص باستخدام الأحرف العربية فقط';
    private const SAMPLE_CYRILLIC = 'Пример текста с использованием только русских символов';
    private const SAMPLE_GREEK = 'Ένα παράδειγμα κειμένου που χρησιμοποιεί μόνο ελληνικούς χαρακτήρες';
    private const SAMPLE_GURMUKHI = 'ਸਿਰਫ਼ ਪੰਜਾਬੀ ਅੱਖਰਾਂ ਦੀ ਵਰਤੋਂ ਕਰਦੇ ਹੋਏ ਇੱਕ ਉਦਾਹਰਨ ਟੈਕਸਟ';
    private const SAMPLE_HEBREW = 'טקסט לדוגמה המשתמש בתווים בערבית בלבד';

    private const LONG_LATIN = 'This is a long example using only Latin characters intended to be notably longer than all other samples for percentage testing';

    protected function getValidatorClass(): string
    {
        return BannedScriptsValidator::class;
    }

    public function testValidatorMismatchThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->constraintValidator->validate(684, new Length(min: 3));
    }

    public function testOnlyStringablesAndNullAreAccepted(): void
    {
        $constraint = new BannedScripts(Script::Bengali);

        $this->validate('aap', $constraint);
        $this->assertNoViolation();

        $this->validate(null, $constraint);
        $this->assertNoViolation();

        $this->validate(684, $constraint);
        $this->assertNoViolation();

        $this->validate(new class() implements \Stringable {
            public function __toString(): string
            {
                return 'foo';
            }
        }, $constraint);
        $this->assertNoViolation();

        $this->expectException(UnexpectedValueException::class);
        $this->constraintValidator->validate($this, $constraint);
    }

    public function testCalculatingPercentages(): void
    {
        $constraint = new BannedScripts(Script::Hebrew, maxPercentage: 19);
        $text = self::LONG_LATIN . self::SAMPLE_HEBREW;
        $parameters = $this->expectViolations($text, $constraint)->get(0)->getParameters();
        $this->assertSame('20', $parameters['percentage']);

        $constraint->maxPercentage = 20;
        $this->validate($text, $constraint);
        $this->assertNoViolation();

        $constraint->maxPercentage = 38;
        $text = str_repeat(self::SAMPLE_LATIN . self::SAMPLE_HEBREW, 1000);
        $parameters = $this->expectViolations($text, $constraint)->get(0)->getParameters();
        $this->assertSame('39', $parameters['percentage']);

        $constraint->maxPercentage = 39;
        $this->validate($text, $constraint);
        $this->assertNoViolation();
    }

    public function testCharacterThresholdsAreInclusive(): void
    {
        $constraint = new BannedScripts(Script::Hebrew, maxCharacters: 31);
        $text = self::LONG_LATIN . self::SAMPLE_HEBREW;
        $parameters = $this->expectViolations($text, $constraint)->get(0)->getParameters();
        $this->assertSame('32', $parameters['count']);

        $constraint->maxCharacters = 32;
        $this->validate($text, $constraint);
        $this->assertNoViolation();
    }

    #[DataProvider('provideBannedScripts')]
    public function testBannedScriptValidation(BannedScripts $constraint, string $message, string $expectedCode = null): void
    {
        $errors = $this->validate($message, $constraint);
        if (null === $expectedCode) {
            $this->assertNoViolation();
        } else {
            $this->assertCount(1, $errors, 'Expected one single violation');
            $this->assertEquals($errors->get(0)->getCode(), $expectedCode);
        }
    }

    /**
     * @return array<string, array{0: BannedScripts, 1: string, 2?: string}>
     */
    public static function provideBannedScripts(): array
    {
        return [
            'full Latin text' => [
                new BannedScripts(Script::Latin), self::SAMPLE_LATIN, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'full Arabic text' => [
                new BannedScripts(Script::Arabic), self::SAMPLE_ARABIC, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'full Cyrillic text' => [
                new BannedScripts(Script::Cyrillic), self::SAMPLE_CYRILLIC, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'full Greek text' => [
                new BannedScripts(Script::Greek), self::SAMPLE_GREEK, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'full Gurmukhi text' => [
                new BannedScripts(Script::Gurmukhi), self::SAMPLE_GURMUKHI, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'full Hebrew text' => [
                new BannedScripts(Script::Hebrew), self::SAMPLE_HEBREW, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'partial Cyrillic text' => [
                new BannedScripts(Script::Cyrillic), self::SAMPLE_LATIN . self::SAMPLE_CYRILLIC, BannedScripts::NOT_ALLOWED_ERROR,
            ],
            'sufficiently high percentage' => [
                new BannedScripts(Script::Hebrew, maxPercentage: 50),
                self::LONG_LATIN . self::SAMPLE_HEBREW,
            ],
            'sufficiently high character count' => [
                new BannedScripts(Script::Hebrew, maxCharacters: 100),
                self::LONG_LATIN . self::SAMPLE_HEBREW,
            ],
            'low percentage' => [
                new BannedScripts(Script::Hebrew, maxPercentage: 25),
                self::SAMPLE_HEBREW . self::SAMPLE_CYRILLIC,
                BannedScripts::TOO_HIGH_PERCENTAGE_ERROR,
            ],
            'low max character count' => [
                new BannedScripts(Script::Cyrillic, maxCharacters: 5),
                self::SAMPLE_HEBREW . self::SAMPLE_CYRILLIC,
                BannedScripts::TOO_MANY_CHARACTERS_ERROR,
            ],
        ];
    }
}
