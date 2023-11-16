<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Functional;

use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Profiler\Profile;

class IntegrationTest extends WebTestCase
{
    use ClockAwareTrait;
    use ClockSensitiveTrait;

    /**
     * Ensure all time sensitive tests start at the exact same rounded time for predictable results.
     */
    protected function setUp(): void
    {
        self::mockTime('2020-05-04 03:02:01+01:00');
    }

    /**
     * @param mixed[] $options
     * @param mixed[] $server
     */
    protected static function createClient(array $options = [], array $server = []): KernelBrowser
    {
        // Fix annoying default
        // @link https://github.com/symfony/symfony/issues/40837
        $client = parent::createClient($options, $server);
        $client->catchExceptions(false);

        return $client;
    }

    public function testHoneypotAndTimerAreHidden(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        $this->assertResponseIsSuccessful('The request failed');

        $honeypot = $crawler->filter('input[name="kitchen_sink_form[honeypot]"]');
        $this->assertCount(1, $honeypot);
        $this->assertSame('input', $honeypot->nodeName());
        $this->assertSame('text', $honeypot->attr('type'));
        $this->assertSame('display:none', $honeypot->attr('style'));
        $this->assertNull($honeypot->attr('required'));

        $timer = $crawler->filter('input[name="kitchen_sink_form[timer]"]');
        $this->assertCount(1, $timer);
        $this->assertSame('input', $timer->nodeName());
        $this->assertSame('hidden', $timer->attr('type'));
    }

    public function testFastAndSlowResponsesAreCaught(): void
    {
        static::mockTime('2023-10-31 09:00:00');

        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        $this->assertResponseIsSuccessful();

        $formData = [
            'kitchen_sink_form[name]' => 'John Doe',
            'kitchen_sink_form[email]' => 'foo@example.org',
            'kitchen_sink_form[message]' => 'Just a normal text that should pass validation',
        ];

        static::mockTime('+4 seconds');
        $crawler = $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertSelectorExists('.alert-danger', 'The form was submitted too quickly to pass');
        $this->assertSelectorTextContains('.alert-danger', 'unreasonably fast');

        static::mockTime('+5 seconds');
        $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertSelectorNotExists('.alert-danger', 'The form was submitted after a reasonable delay');

        static::mockTime('+3594 seconds');
        $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertSelectorNotExists('.alert-danger', 'The form was submitted after a reasonable delay');

        static::mockTime('+1 second');
        $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertSelectorNotExists('.alert-danger', 'The form was submitted after a reasonable delay');

        static::mockTime('+1 second');
        $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertSelectorExists('.alert-danger', 'The form was submitted too slow to pass');
        $this->assertSelectorTextContains('.alert-danger', 'unreasonably slow');

        static::mockTime('+3 days');
        $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertSelectorExists('.alert-danger', 'The form was submitted too slow to pass');
        $this->assertSelectorTextContains('.alert-danger', 'unreasonably slow');
    }

    public function testFillingInTheHoneypotFailsTheForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        $this->assertResponseIsSuccessful();

        $formData = [
            'kitchen_sink_form[name]' => 'John Doe',
            'kitchen_sink_form[email]' => 'foo@example.org',
            'kitchen_sink_form[message]' => 'Just a normal text that should pass validation',
            'kitchen_sink_form[honeypot]' => 'WE OFFER ADVERTISING SERVICES FOR FREE',
        ];

        $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger', 'The form should not have passed with the honeypot filled');
        $this->assertSelectorTextContains('.alert-danger', 'honeypot field was supposed');
    }

    public function testCorruptingTheHashFailsValidation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        $this->assertResponseIsSuccessful();

        $formData = [
            'kitchen_sink_form[name]' => 'John Doe',
            'kitchen_sink_form[email]' => 'foo@example.org',
            'kitchen_sink_form[message]' => 'Just a normal text that should pass validation',
        ];

        $crawler = $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData, [
            'REMOTE_ADDR' => '1.2.3.4',
        ]);
        $this->assertResponseIsSuccessful();
        $this->expectFormErrors($crawler, ['Your IP address changed', 'unreasonably fast']);

        $formData['kitchen_sink_form[timer]'] = '';
        $crawler = $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertResponseIsSuccessful();
        $this->expectFormErrors($crawler, 'Technical reasons');

        $formData['kitchen_sink_form[timer]'] = '%%%%%NOT_BASE_64%%%%%%';
        $crawler = $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertResponseIsSuccessful();
        $this->expectFormErrors($crawler, 'Technical reasons');

        $formData['kitchen_sink_form[timer]'] = \base64_encode('incorrect structure');
        $crawler = $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertResponseIsSuccessful();
        $this->expectFormErrors($crawler, 'Technical reasons');

        $formData['kitchen_sink_form[timer]'] = \base64_encode('123456|127.0.0.1|invalid_hash');
        $crawler = $client->submit($crawler->filter('form[name=kitchen_sink_form]')->form(), $formData);
        $this->assertResponseIsSuccessful();
        $this->expectFormErrors($crawler, 'Technical reasons');
    }

    public function testProfileTest1(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/profile/test1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#basic_form___custom_timer_field', 'Configured custom name for timer field');
        $this->assertSelectorExists('input#basic_form_email[type=email]', 'EmailType must not change type');
        $this->assertSelectorExists('textarea#basic_form_message', 'TextAreaType must not change type');

        $honeypot = $crawler->filter('#basic_form_email_address')->first();
        $this->assertSame('display:none', $honeypot->attr('style'), 'Honeypot should by default be hidden with inline style');

        $formData = [
            'basic_form[name]' => 'Priya Kaila',
            'basic_form[email]' => 'foo@example.org',
            'basic_form[message]' => <<<EOT
                I hope this email finds you well. I am Priya Kaila from Spammer agency the official partner
                of WordPress VIP and WooCommerce. We are a web developing company with having 60+
                experienced designers and developers. Our company can provide service to you with hourly
                rate of USD 15. We are having expert developers in Drupal , Joomla , WordPress and Laravel.
            EOT,
            'basic_form[email_address]' => 'I love spam',
        ];

        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler,
            ['unreasonably fast', 'honeypot field was supposed to be empty'],
            ['disallowed phrase']);

        static::mockTime('+15 seconds');
        $formData['basic_form[name]'] = 'Арнолд Шварзэнэджр';
        $formData['basic_form[phone]'] = '';
        $formData['basic_form[message]'] = 'Please visit my <a href="https://www.example.org">website</a> at https://example.org';
        $formData['basic_form[email_address]'] = '';
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler, fieldErrors: ['disallowed scripts', 'contains HTML markup']);

        static::mockTime('+10 minutes');
        $formData['basic_form[name]'] = 'Foo Bar';
        $formData['basic_form[message]'] = 'At https://spam.org/viagra we sell https://spam.org/viagra with https://spam.org/viagra';
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler, fieldErrors: ['contains 3 URLs', 'https://spam.org/viagra 3 times']);

        $handler = static::getContainer()->get('monolog.handler.test');
        assert($handler instanceof TestHandler);
        $this->assertCount(1, $records = $handler->getRecords());

        $record = $records[0];
        $this->assertSame('antispam', $record->channel);
        $this->assertStringContainsString('en/profile/test1 violated anti-spam', $record->message);
    }

    public function testProfileTest1Timings(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/profile/test1');
        $formData = [
            'basic_form[name]' => 'John Doe',
            'basic_form[email]' => 'foo@example.org',
            'basic_form[message]' => 'A non-spammy message that is fine',
        ];

        static::mockTime('+14 seconds');
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler, ['unreasonably fast']);

        static::mockTime('+15 seconds');
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectNoErrors($crawler);

        static::mockTime('+899 seconds');
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectNoErrors($crawler);

        static::mockTime('+900 seconds');
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectNoErrors($crawler);

        static::mockTime('+901 seconds');
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler, ['unreasonably slow']);
    }

    /**
     * The test2 profile is stealthed, so all errors should be hidden and merged to a single one at the form level.
     */
    public function testProfileTest2(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/profile/test2');
        $this->assertResponseIsSuccessful();

        // Honeypot is hidden with Bootstrap "d-none" class instead of default
        $honeypot = $crawler->filter('#basic_form_message1')->first();
        $this->assertEmpty($honeypot->attr('style'), 'Default hiding inline style should be overriden');
        $this->assertContains('d-none', array_map('trim', explode(' ', $honeypot->attr('class') ?? '')),
            'Honeypot should be hidden with "d-none" class');

        $formData = [
            'basic_form[name]' => 'Priya Kaila',
            'basic_form[email]' => 'foo@example.org',
            'basic_form[message]' => 'Buy some VIAGRA',
        ];

        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler, formErrors: ['could not be processed']);

        // Magic keyword "CANCEL" should cancel the violation in the test case
        $formData['basic_form[message]'] = 'CANCEL VIAGRA';
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectNoErrors($crawler);

        $formData['basic_form[message]'] = 'Too short';
        $formData['basic_form[message1]'] = 'Winnie the Pooh';
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectFormErrors($crawler, formErrors: ['could not be processed'], fieldErrors: ['10 characters']);
    }

    public function testProfileTest3(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/profile/test3');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#basic_form_email1', 'Duplicate field name should add counter');
    }

    public function testEmbeddedFormsWithConflictingProfiles(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/embedded');
        $this->assertResponseIsSuccessful();

        $formData = [
            'embedding_form[embedded][name]' => 'Priya Kaila',
            'embedding_form[embedded][email]' => 'foo@example.org',
            'embedding_form[embedded][message]' => 'Buy some VIAGRA',
        ];

        // The 900 is defined by test profile 1 in the root form, hence it should win
        self::mockTime('+900 seconds');
        $crawler = $client->submit($crawler->filter('form[name=embedding_form]')->form(), $formData);
        $this->expectNoErrors($crawler);
    }

    public function testDisabledBundle(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/disabled');

        $formData = [
            'basic_form[name]' => 'Priya Kaila',
            'basic_form[email]' => 'foo@example.org',
            'basic_form[message]' => 'Buy some VIAGRA',
        ];

        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->expectNoErrors($crawler);
    }

    public function testPassiveProfile(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/profile/passive_empty');
        $this->assertResponseIsSuccessful();

        $formData = [
            'basic_form[name]' => 'Priya Kaila',
            'basic_form[email]' => 'foo@example.org',
            'basic_form[message]' => 'Buy some spam and SPAM',
        ];

        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);

        // Passive mode should just let it fly
        $this->expectFormErrors($crawler);

        $crawler = $client->request('GET', '/en/fake_success');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submit($crawler->filter('form[name=basic_form]')->form(), $formData);
        $this->assertSelectorTextContains('h1', 'FAKED');
    }

    public function testEmptyProfile(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/profile/empty');
        $this->assertResponseIsSuccessful();
    }

    private function expectNoErrors(Crawler $crawler): void
    {
        $this->expectFormErrors($crawler);
    }

    /**
     * @param string[] $formErrors
     * @param string[] $fieldErrors
     */
    private function expectFormErrors(Crawler $crawler, string|array $formErrors = [], string|array $fieldErrors = []): void
    {
        if (empty($formErrors) && empty($fieldErrors)) {
            $this->assertSelectorExists('.alert-primary', 'The form was not accepted successfully.');
        } else {
            $this->expectErrors($crawler, '.alert-danger', 'form level', (array) $formErrors);
            $this->expectErrors($crawler, '.invalid-feedback', 'field level', (array) $fieldErrors);
        }
    }

    /**
     * @param string[] $errors
     */
    private function expectErrors(Crawler $crawler, string $selector, string $type, array $errors): void
    {
        $actual = [];
        foreach ($crawler->filter($selector) as $element) {
            $actual[] = $element->textContent;
            foreach ($errors as $idx => $error) {
                if (str_contains($element->textContent, $error)) {
                    unset($errors[$idx]);
                    continue 2;
                }
            }
            $this->fail(sprintf('Unexpected %s error: "%s"', $type, $element->textContent));
        }
        if (!empty($errors)) {
            $this->fail(sprintf('Expected %s errors not found: "%s", actual errors: "%s"',
                $type, implode('", "', $errors), implode('", "', $actual)));
        }
    }
}
