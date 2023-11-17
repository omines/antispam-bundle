<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Command;

use Omines\AntiSpamBundle\AntiSpam;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

#[AsCommand('antispam:stats', description: 'List statistics from the file quarantine')]
class StatisticsCommand extends Command
{
    public function __construct(private readonly AntiSpam $antiSpam)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists general statistics from the file based anti-spam quarantine.

    <info>%command.full_name%</info>

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === ($config = $this->antiSpam->getQuarantineConfig()['file'])) {
            $output->writeln('<error>The file quarantine is disabled in your configuration.</error>');

            return self::FAILURE;
        }

        $output->writeln(sprintf('<info>Gathering data from quarantine folder at %s</info>', $config['dir']));

        $finder = (new Finder())
            ->files()
            ->name('*.yaml')
            ->in($config['dir'])
        ;
        foreach ($finder as $file) {
            $items = Yaml::parse($file->getContents());
            if (!is_array($items)) {
                $output->writeln(sprintf('<error>Quarantine file %s is corrupted and could not be read</error>', $file->getFilename()));
                continue;
            }
            foreach ($items as $item) {
                $output->writeln('');
                $output->writeln(sprintf('<info>Time:</info> %s', $item['time']));
                $output->writeln(sprintf('<info>Message:</info> %s', $item['antispam'][0]['message']));
                $output->writeln(sprintf('<info>Cause:</info> %s', $item['antispam'][0]['cause']));
            }
        }

        return self::SUCCESS;
    }
}
