<?php

declare(strict_types=1);

namespace App\Constants;


/**
 * redis数据字典信息
 * Class CacheKey
 * @package App\Constants
 */
class CacheKey
{
    //房间信息 集合 开播状态key  （value1,value2）(开播状态，对应视频流fd)
    const LIVE_ROOM = 'live_room_';

    //视频推流信息 hash   header(视频头信息) room_id(视频推流房间号)
    const LIVE_INFO = 'live_info_';

    //房间连接的客户端 即观看直播的用户websocket fd 集合
    const LIVE_ROOM_CLIENT = 'live_room_client_';

    //房间websocket客户端信息 集合 (value1,value2)(房间id,是否获取过视频头标志 header表示已获得)
    const LIVE_CLIENT_INFO = 'live_client_info_';

    //srs流媒体信息  hash room_id (房间id) ff_id (ffmpeg推流进程id)
    const LIVE_SRS_INFO = 'live_srs_info_';
}
