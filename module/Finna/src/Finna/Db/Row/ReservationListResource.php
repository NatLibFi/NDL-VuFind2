<?php

namespace Finna\Db\Row;

use VuFind\Db\Row\RowGateway;

class ReservationListResource extends RowGateway
{
    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'finna_reservation_list_resource', $adapter);
    }
}
