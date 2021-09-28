<?php


namespace App\Exception\Handler;


use Hyperf\ExceptionHandler\ExceptionHandler;
use Phper666\JWTAuth\Exception\JWTException;
use Phper666\JWTAuth\Exception\TokenValidException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class JWTExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof JWTException || $throwable instanceof TokenValidException) {
            // 格式化输出
            $data = json_encode([
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ], JSON_UNESCAPED_UNICODE);

            // 阻止异常冒泡
            $this->stopPropagation();
            return $response->withStatus(500)->withBody(new SwooleStream($data));
        }
        // 交给下一个异常处理器
        return $response;
    }


    /**
     * 判断该异常处理器是否要对该异常进行处理
     */
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

}