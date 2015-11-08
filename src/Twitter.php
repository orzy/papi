<?php
/**
 *  Papi_Twitter
 *
 *  require
 *      * Papi_Json
 *      * Papi_Oauth
 *
 *  @version 0.2.0
 *  @see     https://github.com/orzy/papi
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  See also
 *  @see https://dev.twitter.com/docs
 */
class Papi_Twitter extends Papi_Oauth {
	const API_URL = 'https://api.twitter.com/1.1/';
	const OAUTH_URL = 'https://api.twitter.com/oauth/';
	
	/**
	 *	ストリームで特定のキーワードを受信し続ける
	 *	・受信すると、$callback->answer()にarray()が渡される
	 *	・受信時に受信を止めたい場合は、$callback->answer()の戻り値をfalseにする
	 *	・TweetのidはPHPでは桁あふれするため、id_strを使うこと
	 *	@param	string	$screenName	TwitterアカウントのユーザーID
	 *	@param	string	$password	Twitterアカウントのパスワード
	 *	@param	mixed	$words	キーワードの文字列 or 配列
	 *	@param	object	$callback
	 *	@return	boolean	true:$callbackによる終了時、false:接続失敗
	 *	@throw	Exception
	 *
	 *	Streaming API 1.0を使っている
	 *	@see https://dev.twitter.com/docs/streaming-apis
	 */
	public static function trackStream($screenName, $password, $words, $callback) {
		$url = 'https://' . $screenName . ':' . $password;
		$url .= '@stream.twitter.com/1/statuses/filter.json?track=';
		$url .= rawUrlEncode(implode(',', (array)$words));
		
		$param = array('http' => array('method' => 'POST'));
		$stream = fopen($url, 'r', false, stream_context_create($param));
		
		if (!$stream) {
			return false;
		}
		
		while ($json = fgets($stream)) {
			if (!$json) {
				continue;
			}
			
			$decoded = Papi_Json::decode($json);
			
			try {
				if (is_array($decoded) && $callback->answer($decoded) === false) {
					fclose($stream);
					return true;
				}
			} catch (Exception $e) {
				fclose($stream);
				throw $e;
			}
		}
	}
	/**
	 *	Access Token取得の準備をする
	 *	@return	array	Request Token, Request Toekn Secret, 認証用パラメータ等
	 */
	public function oauthPrepare() {
		$arr = parent::prepare(self::OAUTH_URL . 'request_token');
		$arr['url'] = self::OAUTH_URL . 'authorize?oauth_token=' . $arr['oauth_token'];
		return $arr;
	}
	/**
	 *	Request TokenとそのSecretをAccess TokenとそのSecretと交換する
	 *	@param	string	$reqToken	Request Token
	 *	@param	string	$reqSecret	Request Token Secret
	 *	@param	string	$verifier	OAuth Verifier
	 *	@return	array	Access TokenとAccess Token Secret
	 */
	public function oauthExchange($requestToken, $requestSecret, $verifier) {
		return parent::exchange(
			self::OAUTH_URL . 'access_token',
			$requestToken,
			$requestSecret,
			$verifier
		);
	}
	/**
	 *	検索する
	 *	@param	mixed	$params	文字列の場合はキーワード、配列の場合はパラメータ
	 *	@param	boolean	$decode	(optional) JSONを連想配列にするかどうか
	 *	@return	array
	 *	@see https://dev.twitter.com/docs/api/1.1/get/search/tweets
	 */
	public function search($params, $decode = true) {
		if (!is_array($params)) {
			$params = array('q' => $params);
		}
		
		$res = $this->request('search/tweets', $params, 'GET', $decode);
		
		if ($decode) {
			return $res['statuses'];
		} else {
			return $res;
		}
	}
	/**
	 *	つぶやく
	 *	@param	string	$tweet
	 *	@return	array
	 *	@see https://dev.twitter.com/docs/api/1.1/post/statuses/update
	 */
	public function tweet($tweet) {
		return $this->request('statuses/update', array('status' => $tweet), 'POST');
	}
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$action
	 *	@param	array	$params	(optional)
	 *	@param	string	$method	(optional)
	 *	@param	boolean	$decode	(optional)
	 *	@return	array
	 */
	public function request($action, array $params = array(), $method = 'GET', $decode = true) {
		$json = parent::request(self::API_URL . $action . '.json', $params, $method);
		
		if ($decode) {
			return Papi_Json::decode($json);
		} else {
			return $json;
		}
	}
}
