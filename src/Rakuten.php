<?php
/**
 *  Papi_Rakuten
 *
 *  require
 *      * Papi_Json
 *
 *  @version 0.2.0
 *  @see     http://code.google.com/p/papi/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  @see https://webservice.rakuten.co.jp/document/
 */
class Papi_Rakuten extends Papi_Json {
	const BASE_URL = 'https://app.rakuten.co.jp/services/api/';
	
	private $_applicationId = '';
	private $_affiliateId = '';
	
	/**
	 *	コンストラクタ
	 *	@param	string	$applicationId	アプリID
	 *	@param	string	$affiliateId	(Optional) アフィリエイトID
	 *	@see https://webservice.rakuten.co.jp/api/ichibaitemsearch/
	 */
	public function __construct($applicationId, $affiliateId = '') {
		$this->_applicationId = $applicationId;
		$this->_affiliateId = $affiliateId;
	}
	
	/**
	 *	宿泊施設を検索する
	 *	@param	array	$params	検索条件
	 *	@return	array	検索結果
	 *	@see https://webservice.rakuten.co.jp/api/ichibaitemsearch/
	 */
	public function searchHotels(array $params) {
		return $this->_request('Travel/SimpleHotelSearch/20131024', $params);
	}
	
	/**
	 *	書籍を検索する
	 *	@param	array	$params	検索条件
	 *	@return	array	検索結果
	 *	@see https://webservice.rakuten.co.jp/api/booksbooksearch/
	 */
	public function searchBooks(array $params) {
		return $this->_request('BooksBook/Search/20130522', $params);
	}
	
	private function _request($url, array $params) {
		$params['applicationId'] = $this->_applicationId;
		
		if ($this->_affiliateId) {
			$params['affiliateId'] = $this->_affiliateId;
		}
		
		return $this->request(self::BASE_URL . $url, $params);
	}
}
