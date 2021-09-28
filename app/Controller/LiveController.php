<?php


namespace App\Controller;

use App\Constants\CacheKey;
use App\Service\JWTService;
use App\Service\ProcessService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Redis\Redis;
use Swoole\Coroutine\System;
use Swoole\Process;

/**
 * @AutoController()
 * Class LiveController
 * @package App\Controller
 */
class LiveController extends AbstractController
{
    /**
     * @Inject()
     * @var Redis
     */
    private $redis;

    /**
     * @Inject()
     * @var JWTService
     */
    private $jwtService;

    public function connect()
    {
        return 0;
    }

    public function publish()
    {
        try {
            //推流工具推流到src流媒体 回调
            $data = json_decode($this->request->getBody()->getContents(), true);
            $clientId = $data['client_id']; //流媒体id
            $roomId = $data['stream']; //房间号
            $params = $data['param']; //附加数据 token等 格式?token=123123&a=12321&b=2
            //判断房间是否开播 没开播禁止推流
            if (!$roomId || !$this->redis->sIsMember(CacheKey::LIVE_ROOM . $roomId, 'open')) {
                return 1;
            }
            parse_str(substr($params, 1, strlen($params)), $pramsArr);
            //验证token
            if (!isset($pramsArr['token'])) {
                return 1;
            }
            $auth = $this->jwtService->decode($pramsArr['token']);
            if (!$auth || $roomId != $auth['room_id']) {
                return 1;
            }
            //边缘服务器
            $edgeUrl = 'rtmp://127.0.0.1:1935/live/' . $roomId;
            $tcpUrl = 'tcp://127.0.0.1:9503';

            $cmd = 'nohup ffmpeg -i ' . $edgeUrl . ' -r 26 -filter:a "atempo=1.0,adelay=196|196"' .
                ' -ar 44100 -acodec mp3   -profile baseline   -level:v 3.1  -tune zerolatency ' .
                ' -metadata title="{token=' . $pramsArr['token'] . '}"' .
                '  -preset ultrafast   -vcodec  libx264  -f flv  ' . $tcpUrl . ' 1>/dev/null 2>&1 & echo $!';

            $process = System::exec($cmd);
            if (!$process) {
                return 1;
            }
            $pid = $process['output'];
            $this->redis->hMSet(CacheKey::LIVE_SRS_INFO . $clientId, [
                'room_id' => $roomId,
                'ff_id' => $pid
            ]);
            return 0;
        } catch (\Exception $e) {
            var_dump('srs publish抛出：', json_encode([
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getCode()
            ]));
            return 1;
        }
    }

    public function close()
    {
        //src流媒体接收到推流停止 回调
        $data = json_decode($this->request->getBody()->getContents(), true);
        $clientId = $data['client_id']; //流媒体id
        $ffId=$this->redis->hGet(CacheKey::LIVE_SRS_INFO.$clientId,'ff_id');
        if($ffId){
            var_dump('关闭进程',Process::kill($ffId,SIGKILL));
            $this->redis->del(CacheKey::LIVE_SRS_INFO.$clientId);
        }
        return 0;
    }
}