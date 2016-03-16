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

    private $consumerKey;
    private $consumerSecret;

    private $accessToken;
    private $tokenSecret;

    public function __construct (){

    }

    /**
     * @param $url
     * @param $params
     * @param string $method
     * @return string
     * @throws \Exception
     */
    public function sign($url, $params, $method = 'GET', $token_request = false) {
        if (!$url) {
            throw new \Exception('NO URL');
        }

        if (!$token_request && (!$this->accessToken || !$this->tokenSecret))
            $this->getToken();

        $sign_method = (isset($params['oauth_signature_method'])) ? $params['oauth_signature_method'] : 'sha1';

        if (!in_array($sign_method, $this->sign_methods)) {
            throw new \Exception('Bad methode');
        }

        $params = $params ? $params : array();
        $method = strtoupper($method);

        $base_signature = $this->get_base_signature($method, $url);
        $encrypt_key = $this->getEncryptKey($params);

        $signature = base64_encode(hash_hmac($sign_method, $base_signature, $encrypt_key, true));

        return $signature;
    }


    /**
     * Get signature base
     * @param string $method Http method
     * @param string $url Url
     * @param string $normalized_params Parameters string
     * @return string
     */
    private function get_base_signature($method, $url) {
        $matches = array();
        preg_match('@^(http://|https://)?([^/]+)(.*)$@i', $url, $matches);
        $url = strtolower($matches[1] . $matches[2]) . $matches[3];
        return http_build_query($method . '&' . $url, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
    }

    /**
     * Get encrypt key
     * @return string
     */
    private function getEncryptKey($params) {
        return http_build_query($params['consumerSecret'] . '&' . $this->TokenSecret, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
    }

    /**
     * @param null $consumer_key
     * @param null $consumer_secret
     * @return array
     * @throws \Exception
     */
    public function getToken($params) {

        if (!$params['consumer_key']) {
            throw new \Exception('No consumer key');
        }

        if (!$params['consumer_secret']) {
            throw new \Exception('No consumer secret');
        }

        $oauth_signature = $this->sign(API_URL, array('oauth_consumer_key' => $params['consumer_key']), 'GET', true);

        $response = $this->request(API_URL . '?oauth_consumer_key=' . $params['consumer_key'] . '&oauth_signature=' . $oauth_signature, true);

        $this->accessToken = $response['data']['token'];
        $this->tokenSecret = $response['data']['token_secret'];

        return true;
    }

    /**
     * Make an API request
     * @param string $url Request url
     * @param boolean $parse Return result or parsed result
     * @return mixed (string/array)
     */
    public function request($url, $parse = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $answer = curl_exec($ch);
        $format = 'xml';
        foreach ($this->formats as $f) {
            $format = (strpos($url, '.' . $f)) ? $f : $format;
        }
        return $parse ? $this->parse($answer, $format) : $answer;
    }
}