<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/13
 * Time: 15:07
 */

namespace core\auth;

/**
 * AccessToken生成类
 * Class AccessToken
 * @package core\auth
 */
class AccessToken
{
    private static function safe_base64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    /**
     * 生成access token
     * @param $accessKey        string      公钥
     * @param $secretKey        string      私钥
     * @return string                       生成好的access token
     */
    public static function get_access_token($accessKey, $secretKey)
    {
        $json = json_encode(array(
            'accessKey'  => $accessKey,
            'rid' => md5(uniqid(mt_rand(), true)),
            'deadline' => time() + 86400 * 2,
        ));
        $encode_json = self::safe_base64encode($json);
        $sign = \hash_hmac('sha1', $encode_json, $secretKey, true);
        $encode_sign = self::safe_base64encode($sign);
        $access_token = "K{$accessKey}_{$encode_sign}";
        return $access_token;
    }
}