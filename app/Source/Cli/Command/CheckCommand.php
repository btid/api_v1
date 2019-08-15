<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2018, Pentagonal Development
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Cli\Command;

use Doctrine\DBAL\Schema\Comparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckCommand
 * @package ArrayIterator\Api\Crypt\Source\Cli\Command
 */
class CheckCommand extends AbstractDatabaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure() : void
    {
        $this
            ->setName($this->commandPrefix .':check')
            ->setAliases(['check'])
            ->setDescription('Execute database scheme checking')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes database checking
EOT
        );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln("<fg=white;bg=blue>\n\n  Checking database schema\n</>\n");
        $output->write(sprintf(
            '- <comment>Database Driver is</comment> <info>%s</info>',
            $this->getDatabase()->getDriver()->getName()
        ), $output::VERBOSITY_VERBOSE);
        $output->writeln('- <comment>Build scheme</comment>', $output::VERBOSITY_VERBOSE);

        $schemeArray = $this->getSchemaMerge()->getSeparateScheme($this->getDatabase());
        $comparator = new Comparator();
        $diff = $comparator->compare($schemeArray[0], $schemeArray[1]);
        $output->writeln('- <comment>Compare scheme</comment>', $output::VERBOSITY_VERBOSE);
        $sql  = $diff->toSql($this->database->getDatabasePlatform());

        $newSQL = $sql;
        // fix different Postgre Version
        foreach ($sql as $key => $d) {
            if (preg_match(
                '/^\s*ALTER\s+TABLE\s+[^\s]+\s+ALTER\s+[^\s]+_at\s+SET\s+DEFAULT\s+
                    (\'now\(\)\'|CURRENT_TIMESTAMP)
                /ix',
                $d
            )) {
                unset($newSQL[$key]);
            }
        }

        if (empty($sql) || empty($newSQL)) {
            $db = $this->getDatabase();
            $output->writeln('');
            $output->writeln('No difference ! <info>All Database Scheme OK</info>');
            $output->writeln('');
            return;
        }

        if (count($schemeArray[0]->getTables()) === 0) {
            $output->writeln('');
            $output->writeln(sprintf(
                '<info>Database table is empty! To install execute:</info>%1$s%1$s %2$s %3$s:install%1$s',
                PHP_EOL,
                $_SERVER['PHP_SELF'],
                $this->commandPrefix
            ));

            return;
        }

        $output->write('- <comment>Checking Table</comment>', $output::VERBOSITY_VERBOSE);
        $nonExistencesTables = [];
        $tables = $schemeArray[1]->getTables();
        $tablesIncomplete = [];
        foreach ($tables as $key => $value) {
            if (!$schemeArray[0]->hasTable($value->getName())) {
                $nonExistencesTables[] = $value->getName();
                continue;
            }
            try {
                if (($tableDiff = $comparator->diffTable(
                    $schemeArray[0]->getTable($value->getName()),
                    $value
                )) && !empty($tableDiff->addedColumns)
                ) {
                    $tablesIncomplete[$value->getName()] = array_keys($tableDiff->addedColumns);
                    continue;
                }
            } catch (\Exception $e) {
                //
            }
        }

        if (!empty($nonExistencesTables)) {
            $output->writeln('');
            $output->writeln(sprintf(
                '<fg=red>Database tables incomplete:</>%s',
                PHP_EOL . PHP_EOL . '- '.implode(PHP_EOL .'- ', $nonExistencesTables)
            ));
            if (!empty($tablesIncomplete)) {
                $output->writeln('');
                $output->writeln('<fg=red>Database table column incomplete:</>');
                foreach ($tablesIncomplete as $t => $c) {
                    $output->writeln("- {$t}");
                    $output->writeln('  -> '.implode(PHP_EOL .'  ', $c));
                }
            }
            $output->writeln('');
            foreach ($schemeArray[1]->toSql($this->getDatabase()->getDatabasePlatform()) as $sql) {
                if (stripos(trim($sql), 'CREATE') !== 0) {
                    $output->writeln('<comment>Also need to update structures</comment>');
                    break;
                }
            }
        } elseif (!empty($tablesIncomplete)) {
            $output->writeln('');
            $output->writeln('<fg=red>Database table column incomplete:</>');
            foreach ($tablesIncomplete as $t => $c) {
                $output->writeln("- {$t}");
                $output->writeln('  -> '.implode(PHP_EOL .'  ', $c));
            }
        } else {
            $output->writeln('<info>There are difference between scheme</info>');
            if ($output->isVeryVerbose()) {
                $output->writeln('');
                $output->writeln('<comment>Below is SQL that need to execute</comment>:');
                foreach ($sql as $s) {
                    $output->writeln($s);
                }
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>To update execute:</info>%1$s%1$s %2$s %3$s:update%1$s',
            PHP_EOL,
            $_SERVER['PHP_SELF'],
            $this->commandPrefix
        ));

        $output->writeln('');
    }
}
