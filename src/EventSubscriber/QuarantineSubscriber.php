<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\EventSubscriber;

use Omines\AntiSpamBundle\AntiSpam;
use Omines\AntiSpamBundle\AntiSpamEvents;
use Omines\AntiSpamBundle\Event\FormResultEvent;
use Omines\AntiSpamBundle\Form\AntiSpamFormResult;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-import-type FileQuarantineOptions from AntiSpam
 */
class QuarantineSubscriber implements EventSubscriberInterface
{
    use ClockAwareTrait;

    public function __construct(
        private readonly AntiSpam $antiSpam,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AntiSpamEvents::FORM_PROCESSED => ['onFormProcessed', -512],
        ];
    }

    public function onFormProcessed(FormResultEvent $event): void
    {
        $result = $event->getResult();
        $config = $this->antiSpam->getQuarantineConfig();

        if (!$result->hasAntiSpamErrors() && $config['only_spam']) {
            return;
        }
        if ($fileConfig = ($config['file'] ?? null)) {
            $this->processFileQuarantine($fileConfig, $result);
        }
    }

    /**
     * @param FileQuarantineOptions $config
     */
    private function processFileQuarantine(array $config, AntiSpamFormResult $result): void
    {
        $now = $this->now();

        $filename = sprintf('%s.yaml', $now->format('Y-m-d'));
        $path = Path::join($config['dir'], $filename);
        $data = $result->asArray();
        $data['time'] = $this->now()->format('c');

        $fs = new Filesystem();
        $fs->appendToFile($path, sprintf("#\n# ----- %s -----\n%s", $now->format('c'),
            Yaml::dump([$data], 5, flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)));
    }
}
