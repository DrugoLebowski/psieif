<?php

namespace App\Database\Models;

/**
 * Represents the database's Posts table.
 *
 * Class Posts
 * @package App\Models
 */
class Posts
{

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var integer
     */
    private $date;

    /**
     * References Users($id).
     *
     * @var integer
     */
    private $userId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Posts
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return Posts
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     * @return Posts
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return Posts
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

}
