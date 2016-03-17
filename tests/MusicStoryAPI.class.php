<?php

// Check php extensions
$php_functions = array('curl_init', 'json_decode', 'simplexml_load_string');
foreach ($php_functions as $php_function)
	if (!function_exists($php_function))
		throw new Exception('php function "' . $php_function . '" is missing');
$php_interfaces = array('Iterator');
foreach ($php_interfaces as $php_interface)
	if (!interface_exists($php_interface))
		throw new Exception('php interface "' . $php_interface . '" is missing');

/**
 * Music Story API Class
 */
class MusicStoryApi {

	/**
	 * Consumer key
	 * @var string
	 */
	protected $ConsumerKey;

	/**
	 * Consumer secret key
	 * @var string
	 */
	protected $ConsumerSecret;

	/**
	 * Token key
	 * @var string
	 */
	protected $AccessToken;

	/**
	 * Token secret key
	 * @var string
	 */
	protected $TokenSecret;

	/**
	 * Supported formats
	 * @var array
	 */
	private $formats = array('json', 'xml');

	/**
	 * API url
	 * @var string
	 */
	protected $url_api = 'http://api.music-story.com/';

	/**
	 * Sign methods
	 * @var array
	 */
	protected $sign_methods = array('sha1');

	// Class errors
	const E_FORMAT = 'Unknown format';
	const E_NO_CONSUMER_KEY = 'Empty consumer key';
	const E_NO_CONSUMER_SECRET = 'Empty consumer secret key';
	const E_NO_URL = 'Empty url';
	const E_SIGN_METHOD = 'Unsupported signature method';
	const E_UNKNOWN_METHOD = 'Unkown method';
	const E_MISSING_PARAMETER = 'Missing parameter';
	const E_UNKNOWN_CONNECTOR = 'Unknown connector';
	const E_UNKNOWN_OBJECT = 'Unknown object';

	/**
	 * Constructor
	 * @param string $consumer_key Consumer key
	 * @param string $consumer_secret Consumer secret key
	 * @param string $access_token Access token (optional)
	 * @param string $token_secret Token secret (optional)
	 * @param string $version Version de l'API (optional)
	 */
	public function __construct($consumer_key = null, $consumer_secret = null, $access_token = null, $token_secret = null, $version = null) {
		if (!$consumer_key)
			$this->getError(__function__, self::E_NO_CONSUMER_KEY);
		if (!$consumer_secret)
			$this->getError(__function__, self::E_NO_CONSUMER_SECRET);
		$this->setConsumerKey($consumer_key);
		$this->setConsumerSecret($consumer_secret);
		if (!$access_token || !$token_secret) {
			$this->getToken();
		} else {
			$this->setAccessToken($access_token);
			$this->setTokenSecret($token_secret);
		}
		$this->url_api = ($version) ? $this->url_api . $version . '/' : $this->url_api;
	}

	/**
	 * Router to setKey, getObject and searchObject methods
	 * @param string $method Method name
	 * @param array $args Arguments
	 * @return mixed (MusicStoryObject/MusicStoryObjects)
	 */
	public function __call($method, $args) {
		if (strpos($method, 'search') !== false)
			return $this->searchObject(str_replace('search', '', $method), count($args) ? $args[0] : array(), isset($args[1]) ? $args[1] : null, isset($args[2]) ? $args[2] : null);
		if (strpos($method, 'get') !== false)
			return $this->getObject(str_replace('get', '', $method), count($args) ? $args[0] : null, (isset($args[1]) && is_array($args[1])) ? $args[1] : array(), (isset($args[1]) && !is_array($args[1])) ? $args[1] : null);
		if (strpos($method, 'set') !== false)
			$this->setKey(str_replace('set', '', $method), $args[0]);
		else
			$this->getError(__function__, self::E_UNKNOWN_METHOD);
	}

	/**
	 * Set consumer keys and token keys
	 * @param string $key Key name
	 * @param string $val Key value
	 */
	protected function setKey($key, $val) {
		$this->{$key} = $val;
	}

	/**
	 * Get Music Story object by id
	 * @param string $object Object name
	 * @param string/int $id Object id
	 * @return MusicStoryObject
	 */
	protected function getObject($object, $id, $fields = array(), $partner = null) {
		if (!isset($id) || is_array($id))
			$this->getError(__function__, self::E_MISSING_PARAMETER);
		$url = $this->setFormat($this->url_api . strtolower($partner ? $partner : $object) . '/' . $id . ($partner ? ('/' . strtolower($object)) : ''), 'json');
		$params = array('oauth_consumer_key' => $this->ConsumerKey, 'oauth_token' => $this->AccessToken);
		if (count($fields)) {
			$params['fields'] = implode(',', $fields);
		}
		$signature = $this->sign($url, $params);
		$signed_url = $url . '?' . (count($fields) ? $this->normalize_params(implode(',', $fields) . '&', false) : '') . $this->normalize_params($params, false) . 'oauth_consumer_key=' . $this->ConsumerKey . '&oauth_token=' . $this->AccessToken . '&oauth_signature=' . $this->rawurlencode_rfc3986($signature);

		$result = $this->request($signed_url, true);
		return $this->constructResult($result, $object, $partner ? true : false);
	}

	/**
	 * Search Music Story objects
	 * @param string $object Object name
	 * @param array $filters Search filters
	 * @param int $page Page number (optional)
	 * @param int $count Number of items per page (optional)
	 * @return MusicStoryObjects
	 */
	protected function searchObject($object, $filters, $page = false, $count = false) {
		$filters = $this->getFields($filters);
		if ($page)
			$filters['page'] = (string) $page;
		if ($count)
			$filters['pageCount'] = (string) $count;
		$url = $this->url_api . strtolower($object) . '/search';
		$url = $this->setFormat($url, 'json');
		$params = array_merge($filters, array('oauth_consumer_key' => $this->ConsumerKey, 'oauth_token' => $this->AccessToken));
		$signature = $this->sign($url, $params);
		$signed_url = $url . '?' . $this->normalize_params($filters, false) . '&oauth_consumer_key=' . $this->ConsumerKey . '&oauth_token=' . $this->AccessToken . '&oauth_signature=' . $this->rawurlencode_rfc3986($signature);
		$result = $this->request($signed_url, true);

		if ($object == 'biographies')
			$object = 'biography';
		else if (substr($object, strlen($object) - 1, 1) == 's')
			$object = substr($object, 0, strlen($object) - 1);
		return $this->constructResult($result, $object, true);
	}

	/**
	 * Get new tokens
	 * @param string $consumer_key Consumer key (optional)
	 * @param string $consumer_secret Consumer secret (optional)
	 * @return array
	 */
	public function getToken($consumer_key = null, $consumer_secret = null) {
		$consumer_key = $consumer_key ? $consumer_key : $this->ConsumerKey;
		$consumer_secret = $consumer_secret ? $consumer_secret : $this->ConsumerSecret;
		if (!$consumer_key)
			$this->getError(__function__, self::E_NO_CONSUMER_KEY);
		if (!$consumer_secret)
			$this->getError(__function__, self::E_NO_CONSUMER_SECRET);
		$url = $this->setFormat($this->url_api . 'oauth/request_token', 'json');
		$oauth_signature = $this->sign($url, array('oauth_consumer_key' => $consumer_key), 'GET', true);
		$response = $this->request($url . '?oauth_consumer_key=' . $consumer_key . '&oauth_signature=' . $oauth_signature, true);
		$this->setAccessToken($response['data']['token']);
		$this->setTokenSecret($response['data']['token_secret']);
		return array('access_token' => $response['data']['token'], 'token_secret' => $response['data']['token_secret']);
	}

	/**
	 * Set new tokens
	 * @param string $access_token Access token
	 * @param string $token_secret Secret token
	 */
	public function setToken($access_token, $token_secret) {
		if (!isset($access_token) || !isset($token_secret) || !$access_token || !$token_secret)
			$this->getError(__function__, self::E_MISSING_PARAMETER);
		$this->setAccessToken($access_token);
		$this->setTokenSecret($token_secret);
	}

	/**
	 * Return oauth signature
	 * @param string $url Request url
	 * @param array $params Request parameters
	 * @param string $method Http method
	 * @param boolean $token_request The request is the token request
	 * @return string
	 */
	public function sign($url, $params, $method = 'GET', $token_request = false) {
echo $url."\n";
		if (!$url)
			$this->getError(__function__, self::E_NO_URL);
		if (!$token_request && (!$this->AccessToken || !$this->TokenSecret))
			$this->getToken();
		$sign_method = (isset($params['oauth_signature_method'])) ? $params['oauth_signature_method'] : 'sha1';
		if (!in_array($sign_method, $this->sign_methods))
			$this->getError(__function__, self::E_SIGN_METHOD);
		$params = $params ? $params : array();
		$method = strtoupper($method);
		$normalized_params = $this->normalize_params($params);
		$base_signature = $this->get_base_signature($method, $url, $normalized_params);
		$encrypt_key = $this->getEncryptKey();
echo 'Base signature : '.$base_signature."\n";
echo 'Encrypte key : '.$encrypt_key."\n";
echo 'hash hmac : '.hash_hmac($sign_method, $base_signature, $encrypt_key, true)."\n";
echo 'Signature : '. base64_encode(hash_hmac($sign_method, $base_signature, $encrypt_key, true))."\n";

		$signature = base64_encode(hash_hmac($sign_method, $base_signature, $encrypt_key, true));
		return $signature;
	}

	/**
	 * Return parameters into string format
	 * @param array $params Parameters
	 * @param boolean $rfc3986 RFC3986 url encode
	 * @return string
	 */
	protected function normalize_params($params, $rfc3986 = true) {
		ksort($params);
		$normalized = null;
		foreach ($params as $attr => $val) {
			$val = str_replace(' ', '+', $val);
			if ($normalized)
				$normalized .= '&';
			$normalized.=($rfc3986 ? $this->rawurlencode_rfc3986($attr) : rawurlencode($attr)) . '=' . ($rfc3986 ? $this->rawurlencode_rfc3986($val) : rawurlencode($val));
		}
		return $normalized;
	}

	/**
	 * Set the "fields" parameter to be able to filter through facultative fields
	 * @param array $filters filters
	 * @return array $filters completed filters
	 */
	protected function getFields($filters) {
		if (!$filters)
			$filters = array();
		$fields = '';
		/* foreach($filters as $attr=>$val){
		  $fields.=(strlen($fields)?',':'').$attr;
		  } */
		return strlen($fields) ? array_merge($filters, array('fields' => $fields)) : $filters;
	}

	/**
	 * Encode url
	 * @param string $input Url
	 * @return string
	 */
	protected function rawurlencode_rfc3986($input) {
		return str_replace('%7E', '~', str_replace('+', ' ', urlencode($input)));
	}

	/**
	 * Set request format into url
	 * @param string $url Url
	 * @param string $format Format
	 * @return string
	 */
	protected function setFormat($url, $format = null) {
		if (!in_array($format, $this->formats))
			$this->getError(__function__, self::E_FORMAT);
		if ($format) {
			foreach ($this->formats as $f) {
				$url = str_replace('.' . $f, '', $url);
			}
			$url = (strpos($url, '?')) ? str_replace('?', '.' . $format . '?', $url) : $url . '.' . $format;
		}
		return $url;
	}

	/**
	 * Make an API request
	 * @param string $url Request url
	 * @param boolean $parse Return result or parsed result
	 * @return mixed (string/array)
	 */
	public function request($url, $parse = false) {
echo $url."\n";
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
		return $method . '&' . $this->rawurlencode_rfc3986($url) . '&' . $this->rawurlencode_rfc3986($normalized_params);
	}

	/**
	 * Get encrypt key
	 * @return string
	 */
	private function getEncryptKey() {
		return $this->rawurlencode_rfc3986($this->ConsumerSecret) . '&' . $this->rawurlencode_rfc3986($this->TokenSecret);
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

	/**
	 * Get exception
	 * @param string $f Function name
	 * @param string $err Error Name
	 * @throws Exception
	 */
	protected function getError($f, $err) {
		throw new Exception('Class error in method "' . $f . '": ' . $err);
	}

	/**
	 * Transform parsed result into MusicStoryObject or MusicStoryObjects iterator
	 * @param array $result Parsed result
	 * @param string $name Object(s) name
	 * @param boolean $list Is result an object or a list of objects?
	 * @return mixed (MusicStoryObject/MusicStoryObjects)
	 */
	protected function constructResult($result, $name, $list = false) {
		if (isset($result['error'])) {
			throw new Exception('API returned the following error: "' . $result['error']['message'] . '"');
		}
		if (!$list) {
			$res = (count($result) > 0) ? new MusicStoryObject($result, $name, array('ConsumerKey' => $this->ConsumerKey, 'ConsumerSecret' => $this->ConsumerSecret, 'AccessToken' => $this->AccessToken, 'TokenSecret' => $this->TokenSecret)) : null;
		}else {
			$items = array();
			if (isset($result['data'])) {
				foreach ($result['data'] as $data) {
					$items[] = new MusicStoryObject($data, $name, array('ConsumerKey' => $this->ConsumerKey, 'ConsumerSecret' => $this->ConsumerSecret, 'AccessToken' => $this->AccessToken, 'TokenSecret' => $this->TokenSecret));
				}
			}
			$res = new MusicStoryObjects($items, isset($result['count']) ? $result['count'] : 1, isset($result['pageCount']) ? $result['pageCount'] : 1, isset($result['currentPage']) ? $result['currentPage'] : 1);
		}
		return $res;
	}

}

/**
 * Music Story Object Class
 */
class MusicStoryObject extends MusicStoryApi {

	/**
	 * Object name
	 * @var string
	 */
	private $_object_name;

	/**
	 * Constructor
	 * @param array $api_result Parsed result
	 * @param string $name Object name
	 * @param array $keys Consumer keys and token keys values
	 */
	public function __construct($api_result, $name, $keys) {
		foreach ($keys as $key => $val)
			$this->setKey($key, $val);
		$this->_object_name = $name;
		foreach ($api_result as $key => $val)
			if ($key != 'version' && $key != 'code')
				$this->{$key} = $val;
	}

	/**
	 * Router to getConnector method
	 * @param string $method Method name
	 * @param array $args Arguments
	 * @return MusicStoryObjects
	 */
	public function __call($method, $args) {
		if (strpos($method, 'get') !== false) {
			return $this->getConnector(str_replace('get', '', $method), count($args) ? $args[0] : array(), isset($args[1]) ? $args[1] : null, isset($args[2]) ? $args[2] : null);
		} else
			$this->getError(__function__, self::E_UNKNOWN_METHOD);
	}

	/**
	 * Get connector result
	 * @param string $connector Connector name
	 * @param array $filters Search filters
	 * @param int $page Page number (optional)
	 * @param int $count Items per page (optional)
	 * @return MusicStoryObjects
	 */
	public function getConnector($connector, $filters, $page = false, $count = false) {
		if ($page)
			$filters['page'] = (string) $page;
		if ($count)
			$filters['pageCount'] = (string) $count;
		$url = $this->url_api . strtolower($this->_object_name) . '/' . $this->id . '/' . strtolower($connector);
		$url = $this->setFormat($url, 'json');
		$params = array_merge($filters, array('oauth_consumer_key' => $this->ConsumerKey, 'oauth_token' => $this->AccessToken));
		$signature = $this->sign($url, $params);
		$signed_url = $url . '?' . $this->normalize_params($filters, false) . '&oauth_consumer_key=' . $this->ConsumerKey . '&oauth_token=' . $this->AccessToken . '&oauth_signature=' . $this->rawurlencode_rfc3986($signature);
		$result = $this->request($signed_url, true);

		if ($connector == 'biographies')
			$connector = 'biography';
		else if (substr($connector, strlen($connector) - 1, 1) == 's')
			$connector = substr($connector, 0, strlen($connector) - 1);
		return $this->constructResult($result, $connector, true);
	}

}

/**
 * Music Story Objects Iterator Class
 */
class MusicStoryObjects implements Iterator {

	/**
	 * Current item key
	 * @var int
	 */
	private $position;

	/**
	 * Number of pages
	 * @var int
	 */
	private $page_count;

	/**
	 * Number of items per page
	 * @var int
	 */
	private $count;

	/**
	 * Current page
	 * @var int
	 */
	private $current_page;

	/**
	 * MusicStoryObjects list
	 * @var array
	 */
	private $data;

	/**
	 * Constructor
	 * @param array $items Music Story objects
	 * @param int $count Number of items per page
	 * @param int $page_count Number of pages
	 * @param type $current_page Current page
	 */
	public function __construct($items, $count, $page_count, $current_page) {
		$this->data = $items;
		$this->count = $count;
		$this->page_count = $page_count;
		$this->current_page = $current_page;
		$this->position = 0;
	}

	/**
	 * Get the result count
	 * @return integer
	 */
	public function size() {
		return $this->count;
	}

	/**
	 * Check existence of next page
	 * @return boolean
	 */
	public function hasNextPage() {
		return ($this->current_page < $this->page_count);
	}

	/**
	 * Check existence of previous page
	 * @return boolean
	 */
	public function hasPrevPage() {
		return ($this->current_page > 1);
	}

	/**
	 * Rewind iterator
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Get current MusicStoryObject
	 * @return MusicStoryObject
	 */
	public function current() {
		return isset($this->data[$this->position]) ? $this->data[$this->position] : null;
	}

	/**
	 * Get current MusicStoryObject key
	 * @return int
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Increment iterator position
	 */
	public function next() {
		++$this->position;
	}

	/**
	 * Decrement iterator position
	 */
	public function prev() {
		--$this->position;
	}

	/**
	 * Check existence of current Object
	 * @return boolean
	 */
	public function valid() {
		return isset($this->data[$this->position]);
	}

	/**
	 * Check existence of next Object
	 * @return boolean
	 */
	public function hasNext() {
		return isset($this->data[$this->position + 1]);
	}

	/**
	 * Check existence of previous Object
	 * @return boolean
	 */
	public function hasPrev() {
		return isset($this->data[$this->position - 1]);
	}

}
