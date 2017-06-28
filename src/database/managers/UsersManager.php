<?php

namespace App\Database\Managers;

use App\Database\Models\Users;
use Slim\Container;

/**
 * This class is responsible to manage the interaction between the application
 * and the Users model. All the operations (e.g. insert, select) must be done here.
 *
 * Class UsersManager
 * @package App\Database\Managers
 */
class UsersManager
{
    /** @var Container */
    private $container;

    /**
     * UsersManager constructor.
     *
     * @param   Container   $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}