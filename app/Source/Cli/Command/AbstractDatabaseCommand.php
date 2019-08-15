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

use ArrayIterator\Api\Crypt\Source\Generator\SchemaMerger;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Tools\Console\Command\AbstractCommand;
use Pentagonal\DatabaseDBAL\Database;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractDatabaseCommand
 * @package ArrayIterator\Api\Crypt\Source\Cli\Command
 */
abstract class AbstractDatabaseCommand extends AbstractCommand
{
    protected $commandPrefix = 'database';

    /**
     * @var SchemaMerger
     */
    protected $schemaMerge;

    /**
     * @var Connection
     */
    protected $database;

    /**
     * AbstractDatabaseCommand constructor.
     * @param SchemaMerger $schemaMerger
     * @param Database $database
     */
    public function __construct(SchemaMerger $schemaMerger, Database $database)
    {
        $this->schemaMerge = $schemaMerger;
        $this->database = $database;
        parent::__construct();
    }

    /**
     * @return SchemaMerger
     */
    public function getSchemaMerge() : SchemaMerger
    {
        return $this->schemaMerge;
    }

    /**
     * @return Database
     */
    public function getDatabase() : Database
    {
        return $this->database;
    }

    /**
     * @param OutputInterface $output
     * @return bool|null
     */
    protected function createUserMaybe(OutputInterface $output)
    {
        $schemeArray = $this->getDatabase()->getSchemaManager()->createSchema();
        $db = $this->getDatabase();
        $userTable = $db->prefix('users');
        if ($schemeArray->hasTable($userTable)) {
            $output->writeln('- <comment>Checking super admin users</comment>', $output::VERBOSITY_VERBOSE);
            $qb = $db->createQueryBuilder();
            $v = $qb->select('*')
                ->from($userTable)
                ->where('user_role=1')
                ->execute()
                ->fetch();

            if (empty($v)) {
                $availUserName = 'admin';
                $baseName = $availUserName;
                $c = 1;
                do {
                    $exists = $qb->select('*')
                        ->from($userTable)
                        ->where('user_username=:user_n')
                        ->setParameter(':user_n', $availUserName)
                        ->execute()
                        ->fetch();
                    if ($exists) {
                        $availUserName = $baseName . $c++;
                    }
                } while (!empty($exists));
                $email = $availUserName .'@example.com';
                $baseName = $email;
                $c = 1;
                do {
                    $exists = $qb->select('*')
                        ->from($userTable)
                        ->where('user_username=:user_n')
                        ->setParameter(':user_n', $email)
                        ->execute()
                        ->fetch();
                    if ($exists) {
                        $email = $baseName . $c++;
                    }
                } while ($exists);

                $stmt = $qb->insert(
                    $userTable
                )->values([
                    'user_username' => ':p_username',
                    'user_email'    => ':p_email',
                    'user_first_name' => ':p_first_name',
                    'user_last_name' => ':p_last_name',
                    'user_password' => ':p_pass',
                    'user_role' => ':p_role',
                ])->setParameters([
                    ':p_username' => $availUserName,
                    ':p_first_name' => 'Super',
                    ':p_last_name' => 'Admin',
                    ':p_email' => $email,
                    ':p_pass' => password_hash(sha1('password'), PASSWORD_BCRYPT),
                    ':p_role' => 1,
                ]);
                if ($stmt->execute()) {
                    $output->writeln('');
                    $output->writeln('<info>Super admin user successfully created with detail:</info>');
                    $output->writeln('');
                    $output->writeln('-----------------------------------');
                    $output->writeln(sprintf('<info>Username</info> : <comment>%s</comment>', $availUserName));
                    $output->writeln(sprintf('<info>Password</info> : <comment>%s</comment>', 'password'));
                    $output->writeln(sprintf('<info>Email</info>    : <comment>%s</comment>', $email));
                    $output->writeln('-----------------------------------');
                    $output->writeln('');
                    return true;
                }

                $output->writeln(sprintf(
                    sprintf(
                        '<error>There was an error:</error>:%s%s',
                        PHP_EOL,
                        json_encode(
                            $this->getDatabase()->errorInfo()
                        )
                    )
                ));
                $output->writeln('');
                return false;
            }
        }

        return null;
    }
}
