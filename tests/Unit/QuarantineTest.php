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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Tests\Fixture\Form\Type\KitchenSinkForm;

class QuarantineTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    public function testQuarantineIsGenerated(): void
    {
        self::mockTime('2021-03-20 12:00:00');

        $fs = new Filesystem();
        $fs->remove($quarantinePath = dirname(__DIR__) . '/Fixture/var/quarantine');
        $quarantinePath .= '/2021-03-20.yaml';

        $formFactory = static::getContainer()->get(FormFactoryInterface::class);
        assert($formFactory instanceof FormFactoryInterface);

        $form = $formFactory->createBuilder(KitchenSinkForm::class, options: [
            'antispam_profile' => 'test1',
        ])->getForm();
        $view = $form->createView();
        self::mockTime('+10 seconds');

        $request = Request::create('/', method: 'POST', parameters: [
            'kitchen_sink_form' => [
                'name' => 'John Doe',
                'email' => 'foo@example.org',
                'message' => 'Message for testing',
                'timer' => $view['timer']->vars['value'],
            ],
        ]);
        $form->handleRequest($request);

        // Test if log was created
        $this->assertFileExists($quarantinePath);
        $data = Yaml::parseFile($quarantinePath);

        /*
         * @todo expand more about the content of the tests
         */
        $this->assertIsArray($data);
        $this->assertCount(1, $data, 'There should be a single message in quarantine');
    }
}
