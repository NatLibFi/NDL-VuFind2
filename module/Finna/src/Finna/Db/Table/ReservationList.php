<?php

namespace Finna\Db\Table;

use Laminas\Db\Adapter\Adapter;
use Laminas\Session\Container;
use VuFind\Db\Row\RowGateway;
use VuFind\Exception\LoginRequired as LoginRequiredException;
use VuFind\Db\Table\PluginManager;
use VuFind\Db\Table\UserList;
use VuFind\Db\Table\Gateway;

class ReservationList extends UserList
{
    /**
     * Session container for last list information.
     *
     * @var Container
     */
    protected $session;

    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param Container     $session Session container (must use same
     * namespace as container provided to \VuFind\View\Helper\Root\UserList).
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        ?RowGateway $rowObj = null,
        Container $session = null,
        $table = 'finna_reservation_list'
    ) {
        $this->session = $session;
        Gateway::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Create a new list object.
     *
     * @param mixed $user User object representing owner of
     * new list (or false if not logged in)
     *
     * @return \Finna\Db\Row\ReservationList
     * @throws LoginRequiredException
     */
    public function getNew($user)
    {
        if (!$user) {
            throw new LoginRequiredException('Log in to create lists.');
        }

        $row = $this->createRow();
        $row->created = date('Y-m-d H:i:s');    // force creation date
        $row->user_id = $user->id;
        $row->datasource = '';
        return $row;
    }
}
