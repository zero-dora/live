视频直播学习
hyperf框架+ffmpeg转码工具+srs流媒体服务

本实例只适合学习，无法生产环境使用，只是作为学习借鉴。不会写前端，所以很多都是写死数据，能看到效果就行。
后期准备再添加下rpc分布式处理（技术渣渣，边学习边做）


#### 

#### 1.安装ffmpeg

​	选择静态库安装（编译安装太麻烦了），https://johnvansickle.com/ffmpeg，选择对应release:版本

```linux
下载安装包：
wget https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz
解压缩：
tar -xvjf ffmpeg-release-amd64-static.tar.xz 
或
xz -d -xvjf ffmpeg-release-amd64-static.tar.xz
tar -zxvf ffmpeg-release-amd64-static.tar

cd ffmpeg-4.4-amd64-static

ln -s /opt/ffmpeg-4.4-amd64-static/ffmpeg /usr/bin/ffmpeg
ffmpeg -version 查看是否安装成功
```



#### 2.安装srs媒体服务器

文档地址：https://github.com/ossrs/srs/wiki/v4_CN_Home#getting-started

选择了2.0稳定发布版

```
git clone -b 2.0release https://gitee.com/ossrs/srs.git
```

```linux
cd srs/trunk
./configure
make

启动流媒体服务器
./objs/srs -c conf/rtmp.conf #rtmp的配置内容在代码里可以找到
```



#### 3.启动hyperf服务

```php
composer update
php bin/hyperf start
端口号设置为 9502 http,9503 tcp ,9509 websocket

```



浏览器请求 127.0.0.1:9502 ,模拟主播开启直播，房间号目前写死为12345，代码在IndexController::index()里面$roomId。返回串流密钥

```
12345?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJkZWZhdWx0XzYxNTNjZmUyODk0OTcxLjU0NDAwMTI0IiwiaWF0IjoxNjMyODgyNjU4LCJuYmYiOjE2MzI4ODI2NTgsImV4cCI6MTYzMjg4OTg1OCwicm9vbV9pZCI6MTIzNDUsImp3dF9zY2VuZSI6ImRlZmF1bHQifQ.ELeh-ioXkltIOTwIXpCnauUGSdBhe7lsU2g-BbfqlbY
```

流程：1.obs工具推流到srs流媒体服务器 -》 2.ffmpeg对srs视频流转码推送到hyperf tcp服务器-》2.hyperf tcp服务器将视频流推送到hyperfwebsocket了客户端



#### 4.启动obs直播推流工具

进入 设置-》推流

服务器填写：127.0.0.1:1935 ,就是srs服务器的ip和端口，端口可在rtmp.conf文件中修改。

串流密钥就填写上面浏览器返回的串流密钥，用来做身份验证的。

#### 5.打开前端文件

文件在  html/test.html

修改ws路径和房间号

```
 t='ws://192.168.71.100:9509/ws'; //后台服务器websocket地址 /ws是hyperf中配置的websocket路由
 params = {};  
 params['room_id'] = 12345; //房间号 
```

