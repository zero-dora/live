<?php


namespace App\Controller;


use App\Constants\CacheKey;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

class WebSocketController implements OnMessageInterface, OnCloseInterface, OnOpenInterface
{
    /**
     * @Inject()
     * @var Redis
     */
    private $redis;

    public function onClose($server, int $fd, int $reactorId): void
    {
        //获取客户端链接信息
        $clientInfo = $this->redis->sMembers(CacheKey::LIVE_CLIENT_INFO . $fd);
        if (isset($clientInfo[1])) {
            $roomId = $clientInfo[1];
            //删除房间包含的客户端fd
            $this->redis->sRem(CacheKey::LIVE_ROOM_CLIENT . $roomId, $fd);
        }

        //删除客户端信息
        $this->redis->del(CacheKey::LIVE_CLIENT_INFO . $fd);

    }

    public function onMessage($server, Frame $frame): void
    {

        //获取用户上传的房间信息数据
        $data = json_decode($frame->data, true);
        $fd = $frame->fd; //客户端id
        $roomId = $data['room_id'];//房间id
        $serverfd = $data['server_fd'];
        /**
         * 1、绑定当前客户端id到房间集合中
         * 2、绑定客户端所打开的房间
         */
        $this->redis->sAdd(CacheKey::LIVE_CLIENT_INFO . $fd, $roomId);
        $this->redis->sAdd(CacheKey::LIVE_ROOM_CLIENT . $roomId, $fd);

    }

    public function onOpen($server, Request $request): void
    {
        var_dump('新的客户端连接');
    }

}