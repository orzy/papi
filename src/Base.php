<?php
/**
 *  Papi_Base
 *
 *  require
 *      * (none)
 *
 *  @version 0.2.0
 *  @see     https://github.com/orzy/papi
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class Papi_Base {
	private $_workDir;
	private $_interval;
	private $_req = array();
	private $_res = array();
	
	/**
	 *	宣言していないpublic変数へのアクセスを防止
	 *	@param	string	$key
	 */
	public function __get($key) {
		throw new LogicException(get_class($this) . " dosen't have '$key' to get.");
	}
	/**
	 *	宣言していないpublic変数へのアクセスを防止
	 *	@param	string	$key
	 *	@param	mixed	$value
	 */
	public function __set($key, $value) {
		throw new LogicException(get_class($this) . " dosen't have '$key' to set.");
	}
	/**
	 *	コンストラクタ
	 */
	public function __construct() {
	}
	/**
	 *	アクセス時間の間隔を設定する
	 *	@param	string	$workDir	ロック用ファイルを置くディレクトリのパス
	 *	@param	integer	$interval	(optional) 時間間隔（秒）
	 */
	public function interval($workDir, $interval = 1) {
		$this->_workDir = $workDir;
		$this->_interval = $interval;
	}
	/**
	 *	APIにリクエストを送る
	 *	@param	string	$url
	 *	@param	array	$params	(optional)
	 *	@param	string	$method	(optional)
	 *	@return	string
	 */
	public function request($url, array $params = array(), $method = 'GET') {
		//デバグ用
		$this->_req = compact('url', 'params', 'method');
		
		if ($this->_workDir) {
			//アクセス間隔を遵守する
			$fp = fopen($this->_workDir . '/lock', 'w');
			
			if (!$fp || !flock($fp, LOCK_EX)) {
				throw new RuntimeException('Failed to lock.');
			}
			
			$path = $this->_workDir . '/timestamp';
			
			if (file_exists($path)) {
				$sleep = filemtime($path) + $this->_interval - time();
				
				if ($sleep > 0) {
					sleep($sleep);
				}
			}
		}
		
		$res = $this->_getFile($url, $params, $method);
		
		//最終更新時刻を更新してロックを開放
		if ($this->_workDir) {
			touch($this->_workDir . '/timestamp');
			flock($fp, LOCK_UN);
			fclose($fp);
		}
		
		return $res;
	}
	/**
	 *	APIにHTTPアクセスする
	 *	@param	string	$url
	 *	@param	array	$params	(optional)
	 *	@param	string	$method	(optional)
	 *	@return	string
	 */
	private function _getFile($url, array $params, $method) {
		$http = array('ignore_errors' => true);	//HTTPエラー時のWarningを抑制（PHP 5.2.10+）
		$queryString = http_build_query($params);
		
		switch (strToUpper($method)) {
			case 'GET':
				if ($queryString) {
					$url .= '?' . $queryString;
				}
				break;
			case 'POST':
				$http['method'] = 'POST';
				$http['header'] = 'Content-type: application/x-www-form-urlencoded';
				$http['content'] = $queryString;
				break;
			default:
				throw new LogicException("Invalid method ($method)");
		}
		
		$body = file_get_contents(
			$url,
			false,
			stream_context_create(array('http' => $http))
		);
		
		$header = $http_response_header;
		
		if ($header) {
			preg_match('@^HTTP/1\\.. ([0-9]{3}) @i', $header[0], $matches);
			$status = (integer)$matches[1];
		}
		
		//デバグ用
		$this->_res = compact('status', 'header', 'body');
		
		if ($body === false) {
			throw new RuntimeException('Failed to get the response.');
		} else if ($status !== 200) {
			throw new RuntimeException($header[0]);
		}
		
		return $body;
	}
	/**
	 *	Request情報を取得する
	 *	@return	array
	 */
	public function getRequest() {
		return $this->_req;
	}
	/**
	 *	Response情報を取得する
	 *	@return	array
	 */
	public function getResponse() {
		return $this->_res;
	}
	/**
	 *	HTTP Response Statusを取得する
	 *	@return	array
	 */
	public function getStatus() {
		return $this->_res['status'];
	}
	/**
	 *	Response情報を追加する
	 *	@param	string	$key
	 *	@param	mixed	$value
	 */
	public function addResponse($key, $value) {
		$this->_res[$key] = $value;
	}
}
