<?php

namespace MusicStory\SDK;


class Context
{
    const API_URL = 'http://api.music-story.com';

    private $consumerKey;
    private $consumerSecret;
    private $accessToken;
    private $tokenSecret;

    public function __construct($consumerKey, $consumerSecret, $accessToken = null, $tokenSecret = null)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        if (!is_null($accessToken) && !is_null($tokenSecret)) {
            $this->accessToken = $accessToken;
            $this->tokenSecret = $tokenSecret;
        } else {
            $this->getToken();
        }
    }

    /**
     * @return Builder
     */
    public function buildRequest()
    {
        $builder = new Builder(self::API_URL, $this->consumerKey, $this->accessToken);

        return $builder;
    }

    public function execute(Builder $builder){
        $request = $builder->sign($this->consumerKey, $this->consumerSecret, $this->tokenSecret)
                           ->build();
    }

    /**
     * @param Builder $builder
     * @return string
     * @throws \Exception
     */
    public function debug(Builder $builder)
    {
        return $builder->sign($this->consumerKey, $this->consumerSecret, $this->tokenSecret)
                       ->build();
    }



    /**
     * @param $params
     * @return bool
     * @throws \Exception
     */
    public function getToken() {

        $ms_query = $this->buildRequest();
        $ms_query
            ->oauth()
            ->json()
            ->object('oauth/request_token')
            ->sign($this->consumerKey, $this->consumerSecret, $this->tokenSecret)
        ;

        $response = $this->request($ms_query->build(), true, Builder::FORMAT_JSON);
var_dump($response);
        /*
        if ($response['code'] != '1'){
            throw new \Exception('Bad response : '.$response['error']['message']);
        }
        */
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
    public function request($url, $parse = false, $format = Builder::FORMAT_XML) {
echo $url."\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $answer = curl_exec($ch);

        return $parse ? $this->parse($answer, $format) : $answer;
    }


    /**
     * Parse an API result
     * @param string $data_string API result
     * @param string $format API result format
     * @return array
     */
    public function parse($data_string, $format = 'xml') {
        switch ($format) {
            case 'xml':
                $data_tmp = simplexml_load_string($data_string);
                $data = get_object_vars($data_tmp);
                $results_data = $data['data'];
                $objects = array();
                foreach ($results_data as $res) {
                    $data_vars = get_object_vars($res);
                    $object = array();
                    foreach ($data_vars as $item => $value) {
                        if (is_object($value))
                            $value = null;
                        $object[$item] = $value;
                    }
                    $objects[] = $object;
                }
                $data['data'] = $objects;
                break;
            case 'json':
                $data = json_decode($data_string, true);
                break;
            default:
                $this->getError(__function__, self::E_FORMAT);
        }
        return $data;
    }

}