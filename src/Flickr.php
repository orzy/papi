<?php
/**
 *  Papi_Flickr
 *
 *  require
 *      * Papi_Base
 *
 *  @version 0.1.1
 *  @see     http://code.google.com/p/papi/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class Papi_Flickr extends Papi_Base {
	private $_apiKey;
	
	/**
	 *	コンストラクタ
	 *	@param	string	$apiKey
	 */
	public function __construct($apiKey) {
		$this->_apiKey = $apiKey;
	}
	/**
	 *	写真検索
	 *	@param	string	$keyword	(optional)
	 *	@param	array	$params	(optional)
	 *	@return	array
	 *	@see http://www.flickr.com/services/api/flickr.photos.search.html
	 */
	public function search($keyword = '', array $params = array()) {
		$params['text'] = $keyword;
		$res = $this->_request('flickr.photos.search', $params);
		return $res['photos']['photo'];
	}
	/**
	 *	ユーザー情報取得
	 *	@param	string	$userId	（例）12345678@N00
	 *	@return	array
	 *	@see http://www.flickr.com/services/api/flickr.people.getInfo.html
	 */
	public function getPersonInfo($userId) {
		$res = $this->_request('flickr.people.getInfo', array('user_id' => $userId));
		return $res['person'];
	}
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$apiMethod
	 *	@param	array	$params
	 *	@return	array
	 */
	private function _request($apiMethod, $params) {
		$params = array_merge(
			array(
				'api_key' => $this->_apiKey,
				'format' => 'php_serial',
				'method' => $apiMethod
			),
			$params
		);
		
		$res = $this->request('http://api.flickr.com/services/rest/', $params);
		$arr = unserialize($res);
		$this->addResponse('unserialized', $arr);
		
		if ($arr === false) {
			throw new RuntimeException('Failed to unserialize.');
		} else if ($arr['stat'] !== 'ok'){
			throw new RuntimeException($arr['message']);
		}
		
		return $arr;
	}
	/**
	 *	写真のURLを作成する
	 *	@param	array	$photo	Flickr APIから取得した写真情報
	 *	@param	string	$size	(optional)
	 *	@return	string
	 *	@see http://www.flickr.com/services/api/misc.urls.html
	 */
	public function getImageUrl(array $photo, $size = '') {
		$url = 'http://farm' . $photo['farm'] . '.static.flickr.com/';
		$url .= $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'];
		
		if ($size) {
			$url .= '_' . $size;
		}
		
		$url .= '.jpg';
		return $url;
	}
	/**
	 *	Flickrの写真ページのURLを作成する
	 *	@param	array	$photo	Flickr APIから取得した写真情報
	 *	@param	boolean	$mobileFlg	(optional)
	 *	@return	string
	 *	@see http://www.flickr.com/services/api/misc.urls.html
	 */
	public function getPageUrl(array $photo, $mobileFlg = false) {
		if ($mobileFlg) {
			$domain = 'm.flickr.com/#';
		} else {
			$domain = 'www.flickr.com';
		}
		
		return "http://$domain/photos/" . $photo['owner'] . '/' . $photo['id'] . '/';
	}
}
