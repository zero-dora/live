<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine\System;

/**
 * @Command
 */
#[Command]
class TestCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('test:command');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Test Command');
    }

    public function handle()
    {
        $cmd =  'nohup ffmpeg -i rtmp://0.0.0.0:1935/live/12345 -r 26 -filter:a "atempo=1.0,adelay=196|196"   -ar 44100 -acodec mp3   -profile baseline   -level:v 3.1  -tune zerolatency  -metadata title="{token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJkZWZhdWx0XzYxNDU0M2M3MWZjNzc1LjkyNzYxNTc5IiwiaWF0IjoxNjMxOTI5Mjg3LCJuYmYiOjE2MzE5MjkyODcsImV4cCI6MTYzMTkzNjQ4Nywicm9vbV9pZCI6MTIzNDUsImp3dF9zY2VuZSI6ImRlZmF1bHQifQ.0raVXtum4ZZbS8tnD-EUwWUuvDBLQk0oOoNT6apBS3w}"  -preset ultrafast   -vcodec  libx264  -f flv  tcp://127.0.0.1:9503';

        $result = System::exec($cmd);
        var_dump($result);
        $this->line('Hello Hyperf!', 'info');
    }
}
