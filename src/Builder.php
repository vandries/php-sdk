<?php

namespace MusicStory\SDK;


class Builder
{
    const TYPE_GET = "get";
    const TYPE_SEARCH = "search";

    const FORMAT_XML = "xml";
    const FORMAT_JSON = "json";

    const REF_CLASSIC = "classic";
    const REF_PARTNER = "partner";

    private $url;
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
    private $sign;

    public function __construct($url)
    {
        $this->url = $url;
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
    public function sign($consumerKey, $consumerSecret)
    {
        $sign = new Signer();
        $signature = $sign->sign($this->buildWithParams(), array('consumerKey' => $consumerKey, 'consumerSecret' => $consumerSecret), $this->type );

        $this->addParameters(array('oauth_signature' => $signature));
        return false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function build()
    {
//        if (is_null($this->sign)) {
//            throw new \Exception('sign before, soon of a bitch');
//        }

        if ($this->type == self::TYPE_GET) {
            return $this->buildGet();
        } elseif ($this->type == self::TYPE_SEARCH) {
            return $this->buildSearch();
        }

        throw new \Exception('Bad TYPE');
    }

    /**
     * @return string
     */
    private function buildGet()
    {
        if ($this->ref === self::REF_CLASSIC) {
            $content = $this->url . '/' . $this->object . '.' . $this->format . '/' . $this->id;
        } elseif ($this->ref === self::REF_PARTNER) {
            $content = $this->url . '/' . $this->namePartner . '/' . $this->idPartner . '/' . $this->object . '.' . $this->format;
        } else {
            throw new \Exception('Bad REF');
        }

        if (!is_null($this->connector)) {
            $content .= '/' . $this->connector;
        }

        return $this->buildWithParams($content);
    }

    /**
     * @return string
     */
    private function buildSearch()
    {
        $content = $this->url . '/'. $this->object . '.' . $this->format .'/search';

        if (!is_null($this->connector)) {
            throw new \Exception('Connector in search');
        }

        return $this->buildWithParams($content);
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
            $url .= '?' . http_build_query($params, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
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