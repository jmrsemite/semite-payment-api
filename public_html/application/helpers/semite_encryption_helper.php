<?php
/**
 * Created by PhpStorm.
 * User: mbicanin
 * Date: 7/21/16
 * Time: 8:44 PM
 */
final class Encryption {
    private $key;

    public function __construct($key) {
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt($value) {
        return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hash('sha256', $this->key, true), $value, MCRYPT_MODE_ECB)), '+/=', '-_=');
    }

    public function decrypt($value) {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hash('sha256', $this->key, true), base64_decode(strtr($value, '-_=', '+/=')), MCRYPT_MODE_ECB));
    }

    public function decrypt_interface($value,$key) {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hash('sha256', $key, true), base64_decode(strtr($value, '-_=', '+/=')), MCRYPT_MODE_ECB));
    }
}