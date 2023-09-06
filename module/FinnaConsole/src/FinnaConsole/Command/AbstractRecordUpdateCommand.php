<?php

/**
 * Abstract base class for a command that updates records.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Db\Row\RowGateway;

use function get_class;

/**
 * Abstract base class for a command that updates records.
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
abstract class AbstractRecordUpdateCommand extends Command
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = null;

    /**
     * Table display name
     *
     * @var string
     */
    protected $tableName = null;

    /**
     * Command description
     *
     * @var string
     */
    protected $description = null;

    /**
     * Table
     *
     * @var \VuFind\Db\Table\Gateway
     */
    protected $table;

    /**
     * Constructor
     *
     * @param \VuFind\Db\Table\Gateway $table UserList table
     */
    public function __construct(\VuFind\Db\Table\Gateway $table)
    {
        if (null === $this->tableName) {
            throw new \Exception('tableName empty');
        }
        if (null === $this->description) {
            throw new \Exception('description empty');
        }
        $name = null;
        if (empty($this->defaultName)) {
            $className = get_class($this);
            $parts = explode('\\', $className);
            $name = strtolower(
                preg_replace(
                    '/(?<=[a-z])([A-Z])/',
                    '-$1',
                    array_pop($parts)
                )
            );
            $name = strtolower(array_pop($parts)) . "/$name";
        }
        parent::__construct($name);
        $this->table = $table;
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription($this->description)
            ->addArgument(
                'ids',
                InputArgument::REQUIRED,
                "A comma-separated list of {$this->tableName} id's to process"
            );
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $count = 0;
        foreach (explode(',', $input->getArgument('ids')) as $id) {
            if ($record = $this->table->select(['id' => $id])->current()) {
                if ($this->changeRecord($record)) {
                    ++$count;
                    $output->writeln(
                        "Record {$record->id} updated"
                    );
                } else {
                    $output->writeln(
                        "Record {$record->id} already up to date"
                    );
                }
            } else {
                $output->writeln("Record {$record->id} not found");
            }
        }
        $output->writeln("Total $count {$this->tableName}(s) updated");
        return 0;
    }

    /**
     * Update a record
     *
     * @param RowGateway $record Record
     *
     * @return bool Whether changes were made
     */
    abstract protected function changeRecord(RowGateway $record): bool;
}
