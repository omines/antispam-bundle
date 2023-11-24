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
use Omines\AntiSpamBundle\Utility\StringCounter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of results to show in rankings. Defaults to number of days in quarantine.')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists general statistics from the file based anti-spam quarantine.

    <info>%command.full_name%</info>

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null === ($config = $this->antiSpam->getQuarantineConfig()['file'])) {
            $output->writeln('<error>The file quarantine is disabled in your configuration.</error>');

            return self::FAILURE;
        }

        $output->writeln(sprintf('<info>Analyzing data from quarantine folder at %s</info>', $config['dir']));

        $limit = $input->getOption('limit');
        $limit = (is_string($limit) ? intval($limit) : 0) ?: $config['max_days'] ?: 25;

        $finder = (new Finder())
            ->files()
            ->name('*.yaml')
            ->in($config['dir'])
            ->sortByName()
        ;

        $ips = new StringCounter();
        $causes = new StringCounter();
        $dates = new StringCounter();
        foreach ($finder as $file) {
            $items = Yaml::parse($file->getContents());
            if (!is_array($items)) {
                $output->writeln(sprintf('<error>Quarantine file %s is corrupted and could not be read</error>', $file->getFilename()));
                continue;
            }
            foreach ($items as $item) {
                if (!$item['is_spam']) {
                    // Ignore ham for now
                    continue;
                }
                if (array_key_exists('request', $item)) {
                    $ips->add($item['request']['client_ip']);
                }
                $dates->add((new \DateTimeImmutable($item['time']))->format('Y-m-d'));
                foreach ($item['antispam'] as $antispam) {
                    $causes->add($antispam['cause'] ?? $antispam['message'] ?? 'unknown');
                }
            }
        }

        $table = new Table($output);
        $table->setHeaders([
            [new TableCell('By date', ['colspan' => 2]), new TableCell('By IP', ['colspan' => 2]), new TableCell('By cause', ['colspan' => 2])],
            ['Date', '#', 'IP', '#', 'Cause', '#'],
        ]);

        $dates = $dates->getScores();
        $ips = $ips->getRanking($limit);
        $causes = $causes->getRanking($limit);
        $max = max(count($dates), count($ips), count($causes));

        for ($i = 0; $i < $max; ++$i) {
            @$table->addRow([
                $dates[$i][0],
                $dates[$i][1],
                $ips[$i][0],
                $ips[$i][1],
                $causes[$i][0],
                $causes[$i][1],
            ]);
        }
        $table->render();

        return self::SUCCESS;
    }
}
