<?php
/**
 *  Papi_Oauth
 *
 *  require
 *      * Papi_Base
 *
 *  @version 0.1.1
 *  @see     http://code.google.com/p/papi/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  See also
 *  @see http://tools.ietf.org/html/rfc5849
 */
class Papi_Oauth extends Papi_Base {
	private $_consumerKey;
	private $_consumerSecret;
	private $_accessToken = null;
	private $_accessSecret = null;
	
	/**
	 *	コンストラクタ
	 *	@param	string	$key	Consumer Key
	 *	@param	string	$secret	Consumer Secret
	 *	@param	string	$accessToken	(Optional) Access Token取得済みの場合のみ渡す
	 *	@param	string	$accessSecre	(Optional) Access Token取得済みの場合のみ渡すt
	 */
	public function __construct($key, $secret, $accessToken = '', $accessSecret = '') {
		$this->_consumerKey = $key;
		$this->_consumerSecret = $secret;
		
		if ($accessToken) {
			$this->_accessToken = $accessToken;
			$this->_accessSecret = $accessSecret;
		}
	}
	/**
	 *	Access Token取得の準備をする
	 *	@param	string	$reqTokenUrl	Request Token取得URL
	 *	@return	array	Request Token, Request Toekn Secret, 認証用パラメータ等
	 */
	public function prepare($reqTokenUrl) {
		$res = parent::request(
			$reqTokenUrl . '?' . http_build_query($params),
			$this->_params($reqTokenUrl)
		);
		parse_str($res, $arr);
		return $arr;
	}
	/**
	 *	Request TokenとそのSecretをAccess TokenとそのSecretと交換する
	 *	@param	string	$accessTokenUrl	Access Token取得URL
	 *	@param	string	$reqToken	Request Token
	 *	@param	string	$reqSecret	Request Token Secret
	 *	@param	string	$verifier	OAuth Verifier
	 *	@return	array	Access TokenとAccess Token Secret
	 */
	public function exchange($accessTokenUrl, $reqToken, $reqSecret, $verifier) {
		$params = $this->_params(
			$accessTokenUrl,
			array(),
			'GET',
			array($reqToken, $reqSecret, $verifier)
		);
		parse_str(parent::request($accessTokenUrl, $params, 'POST'), $arr);
		return array($arr['oauth_token'], $arr['oauth_token_secret']);
	}
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$url
	 *	@param	array	$params	(optional)
	 *	@param	string	$method	(optional)
	 *	@return	string
	 */
	public function request($url, array $params = array(), $method = 'GET') {
		return parent::request($url, $this->_params($url, $params, $method), $method);
	}
	/**
	 *	OAuthのパラメータを生成する
	 *	@param	string	$url
	 *	@param	array	$params	パラメータ
	 *	@param	string	$method	HTTP Method (GET or POST)
	 *	@param	array	$_req	Request Token情報
	 *	@return	array	OAuthのパラメータ
	 */
	private function _params($url, array $params = array(), $method = 'GET', $req = null) {
		$oauthParams = array(
			'oauth_consumer_key' => $this->_consumerKey,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => md5('more unique' . microTime()),
			'oauth_version' => '1.0',	//省略可
			//'oauth_callback' => '',
		);
		
		if ($this->_accessToken) {	//Access Tokenまで取得済みの場合
			$oauthParams['oauth_token'] = $this->_accessToken;
			$tokenSecret = $this->_accessSecret;
		} else if ($req) {	//Request Token取得済みの場合
			$oauthParams['oauth_token'] = $req[0];
			$oauthParams['oauth_verifier'] = $req[2];
			$tokenSecret = $req[1];
		} else {	//Request Token未取得の場合
			$tokenSecret = '';
		}
		
		$params = array_merge($params, $oauthParams);
		ksort($params);
		$qs = strtr(http_build_query($params), array('+' => '%20'));
		$params['oauth_signature'] = base64_encode(hash_hmac(
			'sha1',
			$method . '&' . rawUrlEncode($url) . '&' . rawUrlEncode($qs),
			$this->_consumerSecret . '&' . $tokenSecret,
			true
		));
		return $params;
	}
}
