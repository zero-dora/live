<?php
declare(strict_types=1);

namespace App\Service;


use Hyperf\Di\Annotation\Inject;
use Phper666\JWTAuth\JWT;

class JWTService
{
    /**
     * @Inject()
     * @var JWT
     */
    protected $jwt;

    public function encode(array $data): string
    {
        $token = $this->jwt->getToken($data);
        return $token;
    }

    public function decode(string $token): array
    {
        $arr = $this->jwt->getParserData($token);
        return $arr;
    }

}