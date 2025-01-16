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
use Omines\AntiSpamBundle\AntiSpamEvents;
use Omines\AntiSpamBundle\DependencyInjection\AntiSpamExtension;
use Omines\AntiSpamBundle\DependencyInjection\Configuration;
use Omines\AntiSpamBundle\Form\Extension\FormTypeAntiSpamExtension;
use Omines\AntiSpamBundle\Type\Script;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(AntiSpamBundle::class)]
#[CoversClass(AntiSpamExtension::class)]
#[CoversClass(Configuration::class)]
class BundleTest extends TestCase
{
    public function testBundleIsLoadedCorrectlyWithExtension(): void
    {
        $bundle = new AntiSpamBundle();
        $extension = $bundle->getContainerExtension();

        $this->assertSame('AntiSpamBundle', $bundle->getName());
        $this->assertSame('antispam', $extension?->getAlias());
    }

    public function testBundleInjectsDependencies(): void
    {
        $builder = new ContainerBuilder();
        $builder->registerExtension(new TwigExtension());
        $builder->loadFromExtension('twig');

        $bundle = new AntiSpamBundle();
        $bundle->build($builder);
        $extension = $bundle->getContainerExtension();
        $this->assertInstanceOf(AntiSpamExtension::class, $extension);
        $extension->load(['antispam' => ['profiles' => ['default' => []]]], $builder);
        $extension->prepend($builder);

        /** @var array{form_themes: string[]}[] $twigConfig */
        $twigConfig = $builder->getExtensionConfig('twig');
        $this->assertContains('@AntiSpam/form/widgets.html.twig', $twigConfig[0]['form_themes']);

        $this->assertTrue($builder->has('antispam.profile.default'));
        $this->assertTrue($builder->has(FormTypeAntiSpamExtension::class));

        foreach ($builder->getCompilerPassConfig()->getPasses() as $pass) {
            if ($pass instanceof AddEventAliasesPass) {
                $pass->process($builder);
            }
        }

        $classes = $builder->getParameter('event_dispatcher.event_aliases');
        $this->assertIsArray($classes);
        foreach (AntiSpamEvents::ALIASES as $class => $alias) {
            $this->assertArrayHasKey($class, $classes);
        }
    }

    public function testConfigurationDefaultsAreEmpty(): void
    {
        $processor = new Processor();
        $result = $processor->processConfiguration(new Configuration(), []);

        $this->assertEmpty($result['profiles']);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    #[DataProvider('provideProfiles')]
    public function testProfileExpansionAndParsing(array $input, array $expected): void
    {
        $processor = new Processor();
        $result = $processor->processConfiguration(new Configuration(), $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return \Generator<string, array{mixed, mixed}>
     */
    public static function provideProfiles(): \Generator
    {
        $finder = (new Finder())
            ->in(__DIR__ . '/Fixtures')
            ->name('config-*-input.yaml');

        foreach ($finder as $file) {
            $input = $file->getRealPath();
            $output = str_replace('-input.', '-output.', $input);
            if (false === ($expected = @file_get_contents($output))) {
                throw new \LogicException("Missing required file $output");
            }
            yield $file->getFilename() => [Yaml::parseFile($input), Yaml::parse($expected)];
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    #[DataProvider('provideValidationTests')]
    public function testConfigurationValidation(array $input, ?string $expectedError = null): void
    {
        $wrapped = [
            'antispam' => [
                'profiles' => [
                    'default' => $input,
                ],
            ],
        ];
        if (null !== $expectedError) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage($expectedError);
        }
        $this->assertIsArray((new Processor())->processConfiguration(new Configuration(), $wrapped));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1?: string}>
     */
    public static function provideValidationTests(): array
    {
        return [
            'min timer value' => [
                ['timer' => ['min' => -1]],
                'is too small',
            ],
            'min timer value is 0' => [
                ['timer' => ['min' => 0]],
            ],
            'max timer value' => [
                ['timer' => ['max' => 59]],
                'is too small',
            ],
            'max timer value is 60' => [
                ['timer' => ['max' => 60]],
            ],
            'min timer not an int' => [
                ['timer' => ['min' => '0']],
                'Expected "float"',
            ],
            'max timer not an int' => [
                ['timer' => ['max' => '60']],
                'Expected "float"',
            ],
            'honeypot attributes should not be empty' => [
                ['honeypot' => ['field' => 'bar', 'attributes' => []]],
                'should have at least 1 element',
            ],
            'honeypot attributes must have string values' => [
                ['honeypot' => ['field' => 'bar', 'attributes' => ['foo' => 684]]],
                'string keys and values',
            ],
            'honeypot attributes must have string keys' => [
                ['honeypot' => ['field' => 'bar', 'attributes' => [684 => 'foo']]],
                'string keys and values',
            ],
            'honeypot attributes cannot nest' => [
                ['honeypot' => ['field' => 'bar', 'attributes' => [684 => ['foo', 'derp']]]],
                'Expected "scalar", but got "array"',
            ],
            'min percentage value too small' => [
                ['banned_scripts' => ['max_percentage' => -1]],
                'is too small',
            ],
            'min percentage value at 0%' => [
                ['banned_scripts' => ['max_percentage' => 0]],
            ],
            'max percentage value too big' => [
                ['banned_scripts' => ['max_percentage' => 101]],
                'is too big',
            ],
            'max percentage value at 100%' => [
                ['banned_scripts' => ['max_percentage' => 100]],
            ],
            'max character count too small' => [
                ['banned_scripts' => ['max_characters' => -1]],
                'is too small',
            ],
            'max character count at 0' => [
                ['banned_scripts' => ['max_characters' => 0]],
            ],
        ];
    }

    public function testBannedScriptConfigIsExpandedAndNormalized(): void
    {
        $processor = new Processor();
        $resultString = $processor->processConfiguration(new Configuration(), [
            'antispam' => ['profiles' => ['default' => [
                'banned_scripts' => Script::Armenian->value,
            ]]],
        ]);
        $resultArray = $processor->processConfiguration(new Configuration(), [
            'antispam' => ['profiles' => ['default' => [
                'banned_scripts' => [Script::Armenian->value],
            ]]],
        ]);
        $resultObject = $processor->processConfiguration(new Configuration(), [
            'antispam' => ['profiles' => ['default' => [
                'banned_scripts' => [
                    'scripts' => [Script::Armenian->value],
                ],
            ]]],
        ]);
        $this->assertSame($resultString, $resultArray);
        $this->assertSame($resultString, $resultObject);
        $this->assertContains(Script::Armenian->value, $resultObject['profiles']['default']['banned_scripts']['scripts']);
        $this->assertSame(0, $resultObject['profiles']['default']['banned_scripts']['max_percentage']);
        $this->assertNull($resultObject['profiles']['default']['banned_scripts']['max_characters']);
    }

    public function testBannedScriptNameMustBeValid(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('valid ISO-15924 script name');

        (new Processor())->processConfiguration(new Configuration(), [
            'antispam' => ['profiles' => ['default' => ['banned_scripts' => 'monkeys']]],
        ]);
    }
}
