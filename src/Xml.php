<?php
/**
 *  Papi_Xml
 *
 *  require
 *      * Papi_Base
 *
 *  @version 0.1.1
 *  @see     http://code.google.com/p/papi/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class Papi_Xml extends Papi_Base {
	private $_errors = array();
	
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$url
	 *	@param	array	$params	(optional)
	 *	@param	string	$method	(optional)
	 *	@return	SimpleXMLElement
	 *	@see http://www.php.net/manual/libxml.constants.php
	 *	@see http://www.php.net/manual/function.libxml-get-errors.php
	 */
	public function request($url, array $params = array(), $method = 'GET') {
		$res = parent::request($url, $params, $method);
		libxml_use_internal_errors();	//エラー出力を抑制
		$xml = new SimpleXMLElement($res, LIBXML_COMPACT);
		$this->_errors = libxml_get_errors();
		
		if ($this->_errors) {
			throw new RuntimeException('Invalid XML.');
		}
		
		return $xml;
	}
	/**
	 *	XMLパースエラー情報を取得する
	 *	@return	array
	 */
	public function getErrors() {
		return $this->_errors;
	}
}
