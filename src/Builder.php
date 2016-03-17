<?php

namespace MusicStory\SDK;


class Builder
{
    const TYPE_GET = "get";
    const TYPE_SEARCH = "search";
    const TYPE_OAUTH = "oauth";

    const FORMAT_XML = "xml";
    const FORMAT_JSON = "json";

    const REF_CLASSIC = "classic";
    const REF_PARTNER = "partner";

    private $url;
    private $consumerKey;
    private $accessToken;
    private $format = self::FORMAT_XML;
    private $type = self::TYPE_SEARCH;
    private $ref = self::REF_CLASSIC;
    private $params = array();
    private $id;
    private $idPartner;
    private $namePartner;
    private $object;
    private $filters;
    private $connector;
    private $link;
    private $fields;
    private $page = 1;
    private $limit = 10;
    private $signature;

    public function __construct($url, $consumerKey, $accessToken = null)
    {
        $this->url = $url;
        $this->consumerKey = $consumerKey;
        $this->accessToken = $accessToken;
/*
        $this->addParameters(array('oauth_consumer_key' => $consumerKey));

        if (!is_null($accessToken)) {
            $this->addParameters(array('oauth_token' => $accessToken));
        }
*/
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->build();
    }

    /**
     * @return bool
     */
    public function sign($consumerKey, $consumerSecret, $tokenSecret)
    {
        $sign = new Signer($consumerKey, $consumerSecret);

        $oauth_token = array('oauth_consumer_key'=>$this->consumerKey);
        if (!is_null($this->accessToken)) {
            $oauth_token = $oauth_token + array('oauth_token' => $this->accessToken);
        }

        $this->signature = $sign->sign($this->build(false), $oauth_token, $this->params, self::TYPE_GET, $tokenSecret);

        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function build($with_params = true)
    {
//        if (is_null($this->sign)) {
//            throw new \Exception('sign before, soon of a bitch');
//        }

        if ($this->type == self::TYPE_GET) {
            return $this->buildGet($with_params);
        } elseif ($this->type == self::TYPE_SEARCH) {
            return $this->buildSearch($with_params);
        } elseif ($this->type == self::TYPE_OAUTH) {
            return $this->buildOauth($with_params);
        }

        throw new \Exception('Bad TYPE');
    }

    /**
     * @return string
     */
    private function buildGet($with_params = true)
    {
        if ($this->ref === self::REF_CLASSIC) {
            $content = $this->url . '/' . $this->object . '/' . $this->id;
        } elseif ($this->ref === self::REF_PARTNER) {
            $content = $this->url . '/' . $this->namePartner . '/' . $this->idPartner . '/' . $this->object;
        } else {
            throw new \Exception('Bad REF');
        }

        if (!is_null($this->connector)) {
            $content .= '/' . $this->connector;
        }

        $content .= '.' . $this->format;

        $content = ($with_params ? $this->buildWithParams($content) : $content);

        return $content;
    }

    /**
     * @return string
     */
    private function buildSearch($with_params = true)
    {
        $content = $this->url . '/'. $this->object . '.' . $this->format;

        if (!is_null($this->connector)) {
            throw new \Exception('Connector in search');
        }

        $content = ($with_params ? $this->buildWithParams($content) : $content);

        return $content;
    }

    /**
     * @param bool $with_params
     * @return string
     */
    private function buildOauth($with_params = true)
    {
        $content = $this->url . '/'. $this->object . '.' . $this->format;

        if ($with_params && count($this->params) > 0) {
            $content .= '?' . Signer::normalize_params($this->params, false);
        } else{
            $content .= '?oauth_consumer_key=' . $this->consumerKey;

            if (!is_null($this->signature)){
                $content .= '&oauth_signature=' . Signer::rawurlencode_rfc3986($this->signature);
            }
        }

        return $content;
    }

    /**
     * @param $url
     * @return string
     */
    private function buildWithParams($url)
    {
        $params = array_merge(
            $this->params,
            array(
                'page' => $this->page,
                'pageCount' => $this->limit,
            )
        );

        if (!is_null($this->connector) && !is_null($this->link)){
            $params = array_merge($params, array('link' => $this->link));
        }

        if (count($this->filters) > 0){
            $params = array_merge($params, $this->filters);
        }

        if (count($this->fields) > 0){
            $params = array_merge($params, array('fields' => implode(',', $this->fields)));
        }

        if (count($params) > 0) {
            //$url .= '?' . http_build_query($params, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
            $url .= '&' . Signer::normalize_params($params, false) . '&oauth_consumer_key=' . $this->consumerKey;
        }


        if (!is_null($this->accessToken)){
            $url .= '&oauth_token=' . $this->accessToken;
        }

        if (!is_null($this->signature)){
            $url .= '&oauth_signature=' . Signer::rawurlencode_rfc3986($this->signature);
        }

        return $url;
    }


    /**
     * @return $this
     */
    public function xml()
    {
        $this->format = self::FORMAT_XML;
        return $this;
    }

    /**
     * @return $this
     */
    public function json()
    {
        $this->format = self::FORMAT_JSON;
        return $this;
    }

    /**
     * @return $this
     */
    public function get()
    {
        $this->type = self::TYPE_GET;
        return $this;
    }

    /**
     * @return $this
     */
    public function search()
    {
        $this->type = self::TYPE_SEARCH;
        return $this;
    }

    /**
     * @return $this
     */
    public function oauth()
    {
        $this->type = self::TYPE_OAUTH;
        return $this;
    }

    /**
     * @return $this
     */
    public function id($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return $this
     */
    public function idPartner($name, $id)
    {
        $this->ref = self::REF_PARTNER;
        $this->namePartner = $name;
        $this->idPartner = $id;
        return $this;
    }

    /**
     * @return $this
     */
    public function object($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return $this
     */
    public function filters($filters = array())
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @return $this
     */
    public function connector($connector, $link = null)
    {
        $this->connector = $connector;
        $this->link = $link;
        return $this;
    }

    /**
     * @return $this
     */
    public function fields($fields = array())
    {
        $this->fields = $fields;
        return $this;
    }

    public function page($nb)
    {
        $this->page = $nb;
        return $this;
    }

    public function limit($nb)
    {
        $this->limit = $nb;
        return $this;
    }

    public function addParameters($params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
}