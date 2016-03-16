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
            // Get API tokens
        }
    }

    /**
     * @return Builder
     */
    public function buildRequest()
    {
        $builder = new Builder(self::API_URL);
        $builder->addParameters(array('oauth_token' => $this->accessToken));

        return $builder;
    }

    public function execute(Builder $builder){
        $builder->sign($this->consumerKey, $this->consumerSecret);
        $request = $builder->build();
    }

    public function debug()
    {
        $builder->sign($this->consumerKey, $this->consumerSecret);
        $request = $builder->build();
    }
}