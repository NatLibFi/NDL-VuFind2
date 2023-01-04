<?php
/**
 * Console service for listing protected users.
 *
 * PHP version 7
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
namespace FinnaConsole\Command\Users;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console service for listing protected users
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class ListProtected extends Command
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'users/list_protected';

    /**
     * User table
     *
     * @var \VuFind\Db\Table\User
     */
    protected $table;

    /**
     * Constructor
     *
     * @param \Finna\Db\Table\User $table User table
     */
    public function __construct(\VuFind\Db\Table\User $table)
    {
        $this->table = $table;
        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('List protected users in the database');
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->table->select(['finna_protected' => 1]) as $user) {
            $output->writeln($user->id . ' (' . $user->username . ')');
        }
        return 0;
    }
}
