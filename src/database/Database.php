<?php

namespace App\Database;

use PDO;
use Monolog\Logger;

/**
 *
 *
 * Class Database
 * @package App\Database
 */
class Database
{
    /** @var string */
    private $host;

    /** @var string */
    private $port;

    /** @var string */
    private $dbname;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var \Monolog\Logger */
    private $logger;

    /**
     * Database constructor.
     *
     * @param   string  $host
     * @param   string  $port
     * @param   string  $dbname
     * @param   string  $username
     * @param   string  $password
     *
     * @throws  \Exception
     */
    public function __construct($host = "localhost", $port = "3306", $dbname, $username, $password, $logger)
    {
        if (is_null($dbname) || empty($dbname)) {
            throw new \Exception('The database name is required');
        }

        if (is_null($username) || empty($username)) {
            throw new \Exception('The username is required');
        }

        if (is_null($password) || empty($password)) {
            throw new \Exception('The password is required');
        }

        $this->setHost($host);
        $this->setPort($port);
        $this->setDbname($dbname);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setLogger($logger);
    }

    /**
     * Returns a new connection to the database.
     *
     * @return  null|PDO    Returns a Null connection if PDO cannot establish a
     *                      new connection to the DB, otherwise returns a new
     *                      PDO instance.
     */
    public function getInstance()
    {
        try {
            return new PDO(
                'mysql:' .
                'host='     . $this->getHost() . ':' .
                'port='     . $this->getPort() . ':' .
                'dbname='   . $this->getDbname() . ':',
                $this->getUsername(),
                $this->getPassword()
            );
        } catch (\Exception $e) {
            $this->getLogger()->addError($e->getMessage());
        } finally {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

    /**
     * @param string $dbname
     */
    public function setDbname($dbname)
    {
        $this->dbname = $dbname;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

}