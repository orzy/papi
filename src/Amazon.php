<?php
/**
 *  Papi_Amazon
 *
 *  require
 *      * Papi_Xml
 *
 *  @version 0.2.0
 *  @see     http://code.google.com/p/papi/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class Papi_Amazon extends Papi_Xml {
	private $_accessKeyId;
	private $_secretAccessKey;
	private $_associateTag;
	private $_domain;
	
	/**
	 *	コンストラクタ
	 *	@param	string	$id	Product Advertising APIのAccess Key Id
	 *	@param	string	$secret	Product Advertising APIのSecret Access Key
	 *	@param	string	$associateTag	Amazon AssociateのAssociate Tag
	 *	@param	string	$domain	(Optional) 例：USなら"com"、UKなら"co.uk"
	 */
	public function __construct($id, $secret, $associateTag, $domain = 'jp') {
		$this->_accessKeyId = $id;
		$this->_secretAccessKey = $secret;
		$this->_associateTag = $associateTag;
		$this->_domain = $domain;
	}
	/**
	 *	商品を探す
	 *	@param	mixed	$params	検索キーワード or 検索条件の配列
	 *	@return	SimpleXMLElement
	 *	@see http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/ItemSearch.html
	 */
	public function search($params) {
		if (!is_array($params)) {
			$params = array('Keywords' => $params);
		}
		
		$params = array_merge(
			array(
				'SearchIndex' => 'Blended',	//主なカテゴリー全て（ただしsortできない）
				'ResponseGroup' => 'Small,Images'
			),
			$params
		);
		
		$xml = $this->_request('ItemSearch', $params);
		
		if ($xml->Request->Errors->Error->Code == 'AWS.ECommerceService.NoExactMatches') {
			return null;
		} else {
			return $xml;
		}
	}
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$opration
	 *	@param	array	$params
	 *	@return	SimpleXMLElement
	 *	@see http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/rest-signature.html
	 */
	private function _request($opration, array $params) {
		$url = 'http://ecs.amazonaws.' . $this->_domain . '/onca/xml';
		$params = array_merge(
			array(
				'Service' => 'AWSECommerceService',
				'AWSAccessKeyId' => $this->_accessKeyId,
				'AssociateTag' => $this->_associateTag,
				'Operation' => $opration,
				'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
			),
			$params
		);
		ksort($params);
		
		//暗号化する文字列を作る
		$parsed = parse_url($url);
		$src = "GET\n";
		$src .= $parsed['host'] . "\n";
		$src .= $parsed['path'] . "\n";
		//RFC 3986のURLエンコードに合わせる
		$src .= strtr(http_build_query($params), array('%7E' => '~', '+' => '%20'));
		//署名を作る
		$hash = hash_hmac('sha256', $src, $this->_secretAccessKey, true);
		$params['Signature'] = base64_encode($hash);
		
		$xml = $this->request($url, $params);
		
		if ($xml->Items->Request->IsValid == 'False') {
			throw new RuntimeException($xml->Items->Request->Errors);
		}
		
		return $xml->Items;
	}
}
