<?php
/**
 * Created by PhpStorm.
 * User: vandries
 * Date: 14/03/2016
 * Time: 20:28
 */

namespace MusicStory\SDK;


class Signer
{

    /**
     * Sign methods
     * @var array
     */
    protected $sign_methods = array('sha1');

    private $formats = array('json', 'xml');

    private $consumerKey;
    private $consumerSecret;

    private $accessToken;
    private $tokenSecret;

    private $sign_method;

    public function __construct ($consumerKey, $consumerSecret, $sign_method = 'sha1'){
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->sign_method = $sign_method;
    }

    /**
     * @param $url
     * @param $params
     * @param string $method
     * @param bool $token_request
     * @return string
     * @throws \Exception
     */
    public function sign($url, $oauth, $params, $method = 'GET', $tokenSecret) {
echo $url."\n";

        $this->tokenSecret = $tokenSecret;
        $method = strtoupper($method);
        $normalized_params = $this->normalize_params(array_merge($oauth, $params));

        $base_signature = $this->get_base_signature($method, $url, $normalized_params);
        $encrypt_key = $this->getEncryptKey();
echo 'Base signature : '.$base_signature."\n";
echo 'Encrypte key : '.$encrypt_key."\n";
echo 'hash hmac : '.hash_hmac($this->sign_method, $base_signature, $encrypt_key, true)."\n";
echo 'Signature : '. base64_encode(hash_hmac($this->sign_method, $base_signature, $encrypt_key, true))."\n";

        $signature = base64_encode(hash_hmac($this->sign_method, $base_signature, $encrypt_key, true));

        return $signature;
    }


    /**
     * Get signature base
     * @param string $method Http method
     * @param string $url Url
     * @param string $normalized_params Parameters string
     * @return string
     */
    private function get_base_signature($method, $url, $normalized_params) {
        $matches = array();
        preg_match('@^(http://|https://)?([^/]+)(.*)$@i', $url, $matches);
        $url = strtolower($matches[1] . $matches[2]) . $matches[3];
        return $method . '&' . $this->rawurlencode_rfc3986($url). '&' . $this->rawurlencode_rfc3986($normalized_params);
    }

    /**
     * Get encrypt key
     * @return string
     */
    private function getEncryptKey() {
        return $this->rawurlencode_rfc3986($this->consumerSecret) . '&' . $this->rawurlencode_rfc3986($this->tokenSecret);
    }


    /**
     * @param $input
     * @return mixed
     */
    public static function rawurlencode_rfc3986($input) {
        return str_replace('%7E', '~', str_replace('+', ' ', urlencode($input)));
    }

    /**
     * Return parameters into string format
     * @param array $params Parameters
     * @param boolean $rfc3986 RFC3986 url encode
     * @return string
     */
    public static function normalize_params($params, $rfc3986 = true) {
        ksort($params);
        $normalized = null;
        foreach ($params as $attr => $val) {
            $val = str_replace(' ', '+', $val);
            if ($normalized)
                $normalized .= '&';
            $normalized.=($rfc3986 ? self::rawurlencode_rfc3986($attr) : rawurlencode($attr)) . '=' . ($rfc3986 ? self::rawurlencode_rfc3986($val) : rawurlencode($val));
        }
        return $normalized;
    }

}