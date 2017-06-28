<?php

namespace App\Database\Managers;

use App\Database\Models\Posts;
use Slim\Container;

/**
 * This class is responsible to manage the interaction between the application
 * and the Posts model. All the operations (e.g. insert, select) must be done here.
 *
 * Class PostsManager
 * @package App\Database\Managers
 */
class PostsManager
{

    /** @var Container */
    private $container;

    /**
     * PostsManager constructor.
     *
     * @param   Container   $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

}