<?php
/**
 *  Papi_Json
 *
 *  require
 *      * Papi_Base
 *
 *  @version 0.2.1
 *  @see     http://code.google.com/p/papi/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class Papi_Json extends Papi_Base {
	/**
	 *	JSONをデコードする
	 *	@param	string	デコード前のの文字列
	 *	@return	array
	 *	@see http://www.php.net/manual/function.json-last-error.php
	 */
	public static function decode($text) {
		$arr = json_decode($text, true);
		
		if (function_exists('json_last_error')) {	// >= 5.3.0
			$error = json_last_error();
			
			if ($error !== JSON_ERROR_NONE) {
				$errors = array(
					'JSON_ERROR_DEPTH',
					'JSON_ERROR_STATE_MISMATCH',
					'JSON_ERROR_CTRL_CHAR',
					'JSON_ERROR_SYNTAX',
					'JSON_ERROR_UTF8',
				);
				
				foreach ($errors as $str) {
					if ($error === constant($str)) {
						throw new RuntimeException($str);
					}
				}
				
				throw new LogicException("Unknown JSON error ($error)");
			}
		}
		
		return $arr;
	}
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$url
	 *	@param	array	$params	(optional)
	 *	@param	string	$method	(optional)
	 *	@return	array
	 */
	public function request($url, array $params = array(), $method = 'GET') {
		return self::decode(parent::request($url, $params, $method));
	}
}
