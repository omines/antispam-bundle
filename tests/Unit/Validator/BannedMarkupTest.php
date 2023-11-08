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
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkup;
use Omines\AntiSpamBundle\Validator\Constraints\BannedMarkupValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @extends ConstraintValidatorTestCase<BannedMarkupValidator>
 */
#[CoversClass(BannedMarkup::class)]
#[CoversClass(BannedMarkupValidator::class)]
#[CoversClass(AntiSpamConstraintValidator::class)]
class BannedMarkupTest extends ConstraintValidatorTestCase
{
    protected function getValidatorClass(): string
    {
        return BannedMarkupValidator::class;
    }

    public function testValidatorMismatchThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->constraintValidator->validate(684, new Length(min: 3));
    }

    public function testOnlyStringablesAndNullAreAccepted(): void
    {
        $constraint = new BannedMarkup();

        $this->validate('aap', $constraint);
        $this->assertNoViolation();

        $this->validate(null, $constraint);
        $this->assertNoViolation();

        $this->validate(684, $constraint);
        $this->assertNoViolation();

        $this->validate(new class() implements \Stringable {
            public function __toString(): string
            {
                return 'example-text';
            }
        }, $constraint);
        $this->assertNoViolation();

        $this->expectException(UnexpectedValueException::class);
        $this->constraintValidator->validate($this, $constraint);
    }

    #[DataProvider('provideBannedMarkupMessages')]
    public function testBannedMarkupValidation(string $message, string $expectedError = null): void
    {
        static $constraint = new BannedMarkup();

        $errors = $this->validate($message, $constraint);
        if (null === $expectedError) {
            $this->assertNoViolation();
        } else {
            $this->assertNotEmpty($errors, sprintf('Message "%s" was expected to contain %s', $message, $expectedError));
            $this->assertStringContainsString($expectedError, (string) $errors->get(0)->getMessage());
        }
    }

    /**
     * @return array{0: string, 1?: string}[]
     */
    public static function provideBannedMarkupMessages(): array
    {
        return [
            ['Please click our <a href="http://example.org">link</a> to buy products.', 'HTML'],
            ['Please click our <a  href=http://example.org>link</a> to buy products.', 'HTML'],
            ['Please click our <a malformed=true href=\'http://example.org\'>link</a> to buy products.', 'HTML'],
            ['Please click <b>our</b> link to <i class="ms-word">buy</i> products.', 'HTML'],
            ['Please click our <a href="http://example.org">link</a> to buy products.', 'HTML'],
            ['<B>ANCIENT HTML USED ALL CAPS</B>', 'HTML'],
            ['<i>ANCIENT HTML USED ALL CAPS (but not consistently)</I>', 'HTML'],
            ['<i>completely buggy html should not fire</b>'],

            ['If you try to [b]BB yourself into bold shouting[/b] it should fail', 'BBCode'],
            ['Even if you mismatch [b]cases[/B] between opening and closing', 'BBCode'],
            ['The [url=https://example.org]BBCode links[/url] should definitely fail', 'BBCode'],
            ['Nested [i]tags in [b]TAGS[/b] should [/i] fail', 'BBCode'],

            ['A <a href="http://spam.me">broken link element that will not work anyway should not trigger on its own'],
            ['Or a text without any markup whatsoever'],
        ];
    }
}
