<?php


namespace App\Server;


use App\Constants\CacheKey;
use App\Service\JWTService;
use App\Service\RedisService;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnReceiveInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Coroutine;


class TcpServer implements OnReceiveInterface, OnCloseInterface
{
    /**
     * @Inject()
     * @var RedisService
     */
    private $redis;

    /**
     * @Inject()
     * @var JWTService
     */
    private $jwtService;

    //接收推流过来的数据
    public function onReceive($server, int $fd, int $reactorId, string $data): void
    {
//        var_dump('是否协程环境', Coroutine::inCoroutine());
        self::authValidate($server, $fd, $data);
        $roomId = $this->redis->getConn()->hGet(CacheKey::LIVE_INFO . $fd, 'room_id');

        //需要获取到redis当中,房间里面的所有客户端fd
        $liveFd = $this->redis->getConn()->sMembers(CacheKey::LIVE_ROOM_CLIENT . $roomId);

        foreach ($liveFd as $value) {
            if (!$server->exist($value)) { // 判断链接是否正常
                //$this->eventDispatcher->dispatch(Event::ON_CLOSE);
                continue;
            }

            //判断该客户端是否已经接收过视频头信息
            if ($this->redis->getConn()->sIsMember(CacheKey::LIVE_CLIENT_INFO . $value, 'header')) {

                //已经接收过视频头信息 则直接推送数据
                $server->push($value, $data, WEBSOCKET_OPCODE_BINARY); //二进制
            } else {
                //获取视频头信息
                $header = $this->redis->getConn()->hGet(CacheKey::LIVE_INFO . $fd, 'header');

                //当前客户端已经发送
                $result = $this->redis->getConn()->sAdd(CacheKey::LIVE_CLIENT_INFO . $value, 'header');
                if ($result) { //防止并发导致数据重复发送
                    //没有推流数据加上视频头信息
                    $server->push($value, $header . $data, WEBSOCKET_OPCODE_BINARY); //二进制
                }
            }
        }

    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        //直播关闭
        if ($this->redis->getConn()->EXISTS(CacheKey::LIVE_INFO . $fd)) {
            $roomId = $this->redis->getConn()->hGet(CacheKey::LIVE_INFO . $fd, 'room_id');
            $this->redis->getConn()->del(CacheKey::LIVE_INFO . $fd);
//            if ($this->redis->exists(CacheKey::LIVE_ROOM . $roomId)) {
//                $this->redis->del(CacheKey::LIVE_ROOM . $roomId);
//            }
        }
        var_dump('连接关闭了');

    }

    private function authValidate($server, $fd, $data)
    {
        try {
            //判断视频流信息存不存在 不存在则校验
            if (!$this->redis->getConn()->exists(CacheKey::LIVE_INFO . $fd)) {
                if (preg_match('/\{token=(.*?)\}/', $data, $match)) {
                    //验证
                    $token = $match[1];

                    if (!$token || !$auth = $this->jwtService->decode($token)) {
                        throw new \Exception('token认证失败');
                    }
                    $roomId = $auth['room_id'];
                    //判断房间是否直播中
                    if (!$roomId || !$this->redis->getConn()->sIsMember(CacheKey::LIVE_ROOM . $roomId, 'open')) {
                        throw new \Exception('房间未在直播中');
                    }

                    if (strstr($data, 'FLV')) {
                        //设置视频头信息 和房间号
                        $this->redis->getConn()->hMSet(CacheKey::LIVE_INFO . $fd, [
                            'header' => $data,
                            'room_id' => $roomId
                        ]);
                    }
                } else {
                    throw new \Exception('token认证失败');
                }
            }
        } catch (\Exception $e) {
            var_dump('tcp认证抛出：', json_encode([
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getCode()
            ]));
            $server->close($fd);
        }
    }
}