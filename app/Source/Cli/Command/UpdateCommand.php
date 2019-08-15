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
 * Class UpdateCommand
 * @package ArrayIterator\Api\Crypt\Source\Cli\Command
 */
class UpdateCommand extends AbstractDatabaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure() : void
    {
        $this
            ->setName($this->commandPrefix .':update')
            ->setAliases(['update'])
            ->setDescription('Execute database scheme checking')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes database update
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
            $output->writeln('');
            if ($this->createUserMaybe($output) !== null) {
                return;
            }

            $output->writeln('<info>All Database Structures OK</info>');
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

        $database = $this->getDatabase();
        try {
            $database->beginTransaction();
            foreach ($sql as $s) {
                if (preg_match('/^\s*CREATE\s+SCHEMA/i', $s)) {
                    $s = preg_replace('/^(CREATE\s+SCHEMA)\s+/', '$1 IF NOT EXISTS ', $s);
                }

                $database->executeQuery($s);
            }

            if ($database->isTransactionActive()) {
                $database->commit();
            }
        } catch (\Exception $e) {
            $database->rollBack();
            throw $e;
        }

        // create user
        $this->createUserMaybe($output);
        $output->writeln('');
        $output->writeln('<info>Database Structures Updated</info>');
        $output->writeln('');
    }
}
