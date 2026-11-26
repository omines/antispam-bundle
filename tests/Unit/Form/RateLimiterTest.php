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

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\EventSubscriber\FormProfileSubscriber;
use Omines\AntiSpamBundle\Form\AntiSpamFormError;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

#[CoversClass(FormProfileSubscriber::class)]
class RateLimiterTest extends KernelTestCase
{
    public function testSecondValidSubmissionIsRejected(): void
    {
        $first = $this->createForm('rate_limited');
        $second = $this->createForm('rate_limited');

        $first->submit(['name' => 'John']);
        $second->submit(['name' => 'John']);

        $error = $this->findAntiSpamError($second);
        self::assertCount(0, $first->getErrors(true));
        self::assertSame('form.rate_limiter.exceeded', $error?->getMessageTemplate());
    }

    public function testInvalidSubmissionsDoNotConsumeTokens(): void
    {
        $invalid = $this->createForm('rate_limited');
        $valid = $this->createForm('rate_limited');

        $invalid->submit(['name' => '']);
        $valid->submit(['name' => 'John']);

        self::assertFalse($invalid->isValid());
        self::assertCount(0, $valid->getErrors(true));
    }

    public function testEveryLimiterInCompoundMustAccept(): void
    {
        $first = $this->createForm('rate_limited_compound');
        $second = $this->createForm('rate_limited_compound');

        $first->submit(['name' => 'John']);
        $second->submit(['name' => 'John']);

        $error = $this->findAntiSpamError($second);
        self::assertCount(0, $first->getErrors(true));
        self::assertSame('form.rate_limiter.exceeded', $error?->getMessageTemplate());
    }

    public function testHourlyLimiterInCompoundIsEnforced(): void
    {
        $forms = [];
        for ($i = 0; $i < 3; ++$i) {
            $forms[] = $form = $this->createForm('rate_limited_hourly');
            $form->submit(['name' => 'John']);
        }

        $error = $this->findAntiSpamError($forms[2]);
        self::assertCount(0, $forms[0]->getErrors(true));
        self::assertCount(0, $forms[1]->getErrors(true));
        self::assertSame('form.rate_limiter.exceeded', $error?->getMessageTemplate());
    }

    public function testProfilesWithoutLimiterAreNeverThrottled(): void
    {
        $forms = [$this->createForm('empty'), $this->createForm('empty'), $this->createForm('empty')];

        foreach ($forms as $form) {
            $form->submit(['name' => 'John']);
        }

        foreach ($forms as $form) {
            self::assertCount(0, $form->getErrors(true));
        }
    }

    public function testStealthReplacesRateLimitError(): void
    {
        $first = $this->createForm('rate_limited_stealthy');
        $second = $this->createForm('rate_limited_stealthy');

        $first->submit(['name' => 'John']);
        $second->submit(['name' => 'John']);

        $error = $this->findAntiSpamError($second);
        self::assertSame('form.stealthed', $error?->getMessageTemplate());
    }

    public function testPassiveModeRecordsButDoesNotRejectThrottledSubmissions(): void
    {
        $first = $this->createForm('rate_limited_passive');
        $second = $this->createForm('rate_limited_passive');

        $first->submit(['name' => 'John']);
        $second->submit(['name' => 'John']);

        self::assertCount(0, $second->getErrors(true));
        self::assertTrue(AntiSpam::isSpam());
    }

    public function testEmbeddedFormsAreNotThrottled(): void
    {
        $factory = $this->getFormFactory();
        $forms = [];
        for ($i = 0; $i < 3; ++$i) {
            $forms[] = $form = $factory->createBuilder(FormType::class)
                ->add('embedded', FormType::class, ['antispam_profile' => 'rate_limited'])
                ->getForm();
            $form->submit(['embedded' => []]);
        }

        foreach ($forms as $form) {
            self::assertCount(0, $form->getErrors(true));
        }
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createForm(string $profile): FormInterface
    {
        return $this->getFormFactory()->createBuilder(FormType::class, options: ['antispam_profile' => $profile])
            ->add('name', TextType::class, ['constraints' => [new NotBlank()]])
            ->getForm();
    }

    private function getFormFactory(): FormFactoryInterface
    {
        $factory = static::getContainer()->get(FormFactoryInterface::class);
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        return $factory;
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function findAntiSpamError(FormInterface $form): ?AntiSpamFormError
    {
        foreach ($form->getErrors(true) as $error) {
            if ($error instanceof AntiSpamFormError) {
                return $error;
            }
        }

        return null;
    }
}
