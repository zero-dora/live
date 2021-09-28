<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Constants\CacheKey;
use App\Service\JWTService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Swoole\Process;

class IndexController extends AbstractController
{
    /**
     * @Inject()
     * @var JWTService
     */
    protected $jwtService;

    /**
     * @Inject()
     * @var Redis
     */
    protected $redis;

    public function index()
    {
//        $user = $this->request->input('user', 'Hyperf');
//        $method = $this->request->getMethod();

//        $user = 'zero';
//        $password = '123456';
        $roomId = 12345;
        $token = $this->jwtService->encode([
            'room_id' => $roomId
        ]);
        //设置房间状态为开播
        $this->redis->sAdd(CacheKey::LIVE_ROOM . $roomId, 'open');
        //返回推流工具的串流密钥
        return $roomId . "?token=" . $token;
    }

    public function test()
    {

        $string = 'videodatarateonwframerate@:@ 
audiodatarateaudiosamplerate@刀audiosamplesize@0stereo 
audiochannels22.1false3.1false4.0false4.1false5.1false7.1falsetitletoken={eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJkZWZhdWx0XzYxNDMwMzM1OGJkYjE5LjgwMTExMzEwIiwiaWF0IjoxNjMxNzgxNjg1LCJuYmYiOjE2MzE3ODE2ODUsImV4cCI6MTYzMTc4ODg4NSwicm9vbV9pZCI6MTIzNCwiand0X3NjZW5lIjoiZGVmYXVsdCJ9.OBFGkWlwGpvE-X-Tc_L413x8KVxTAk0Je76QWmBvwvI
}serverSRS/2.0.276(ZhouGuowen)';

        preg_match_all('/token=(.*?)serverSRS}/i', $string, $match);

        return $match;
    }

    public function kill()
    {
        $pid = $this->request->input('pid');
        return Process::kill($pid, SIGKILL);
    }
}
