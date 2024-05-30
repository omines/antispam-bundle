<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit;

use Omines\AntiSpamBundle\AntiSpamBundle;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizationTest extends KernelTestCase
{
    public const MASTER_LOCALE = 'en';
    public const SUPPORTED_LOCALES = [
        'en',
        'en-US',
        'en-GB',
        'nl',
        'nl-NL',
        'nl-BE',
        'fr',
        'fr-FR',
        'de',
        'de-DE',
        'it',
    ];

    private static MessageCatalogue $catalogue;
    private static DataCollectorTranslator $translator;

    public static function setUpBeforeClass(): void
    {
        $rootDir = dirname(dirname(__DIR__));
        $loader = new YamlFileLoader();
        self::$catalogue = $loader->load(sprintf('%s/translations/antispam+intl-icu.%s.yaml', $rootDir, self::MASTER_LOCALE),
            self::MASTER_LOCALE, 'antispam');

        /* @phpstan-ignore-next-line Type hinting is a pointless mess on this one */
        self::$translator = new DataCollectorTranslator(static::getContainer()->get(TranslatorInterface::class));
    }

    /**
     * @return \Traversable<string, string[]>
     */
    public static function provideSupportedLocales(): \Traversable
    {
        foreach (self::SUPPORTED_LOCALES as $locale) {
            yield $locale => [$locale];
        }
    }

    #[DataProvider('provideSupportedLocales')]
    public function testLocalizationCompletion(string $locale): void
    {
        foreach (self::$catalogue->all(AntiSpamBundle::TRANSLATION_DOMAIN) as $id => $message) {
            self::$translator->trans($id, domain: AntiSpamBundle::TRANSLATION_DOMAIN, locale: $locale);
        }
        $errors = [];
        foreach (self::$translator->getCollectedMessages() as $message) {
            if (null !== $message['fallbackLocale'] && !in_array($message['fallbackLocale'], self::SUPPORTED_LOCALES, true)) {
                $errors[] = sprintf("Message '%s' was not translated to locale '%s':\n    %s",
                    $message['id'], $message['locale'], $message['translation']);
            }
        }
        $this->assertEmpty($errors, implode("\n", $errors));
    }

    public static function provideTranslationFiles(): \Generator
    {
        $finder = (new Finder())
            ->files()
            ->in(dirname(dirname(__DIR__)) . '/translations')
            ->name('*.yaml');
        foreach ($finder as $result) {
            $path = $result->getPathname();

            yield $path => [$path];
        }
    }

    #[DataProvider('provideTranslationFiles')]
    public function testLocalizationFilesAreValid(string $path): void
    {
        $this->assertIsArray(Yaml::parseFile($path));
    }
}
