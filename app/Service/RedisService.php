<?php


namespace App\Service;



class RedisService
{
    private $reids;

    public function __construct()
    {
        $this->reids = new \Redis();
        $this->reids->connect('127.0.0.1', '6379');
    }

    public function getConn()
    {
        return $this->reids;
    }
}