<?php

/**
 * Console service for verifying metadata of saved records.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2024.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command\Util;

use Closure;
use Finna\Db\Service\FinnaRecordServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console service for verifying metadata of saved records.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
#[AsCommand(
    name: 'util/verify_resource_metadata'
)]
class VerifyResourceMetadata extends AbstractUtilCommand
{
    /**
     * Constructor
     *
     * @param FinnaRecordServiceInterface $recordService Record database service
     * @param \VuFind\Date\Converter      $dateConverter Date converter
     * @param \VuFind\Record\Loader       $recordLoader  Record loader
     */
    public function __construct(
        protected FinnaRecordServiceInterface $recordService,
        protected \VuFind\Date\Converter $dateConverter,
        protected \VuFind\Record\Loader $recordLoader
    ) {
        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Verify and update metadata for saved records')
            ->addOption(
                'index',
                null,
                InputOption::VALUE_OPTIONAL,
                'Search index (backend) to check (by default all indexes are checked)'
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->recordService->verifyResourceMetadata(
            $this->recordLoader,
            $this->dateConverter,
            $input->getOption('index'),
            Closure::fromCallable([$this, 'msg'])
        );
        return 0;
    }
}
