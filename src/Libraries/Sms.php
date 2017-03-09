<?php

namespace Lib;

use Curl\Curl;
use Illuminate\Support\Facades\Config;

class Sms {

	/**
	 * 调用的http api地址
	 * @var string
	 */
	private static $_apiUrl = '';

	/**
	 * 支持了一种诡异的调用方法_(:з」∠)_
	 * ex: (new Lib\Sms)('set', 'http://xxxx/sms')('send', $param1, $param2)
	 * @author gjy
	 *
	 * @return object | send()
	 */
	public function __invoke() {
		$args = func_get_args();
		$method = array_shift($args);

		if (!empty($args)) {
			if ($method === 'set') {
				self::setApiUrl(reset($args));
			} elseif ($method === 'send') {
				$argsL = count($args);
				if ($argsL === 1) {
					return self::send(reset($args));
				} else if ($argsL === 2) {
					return self::send(reset($args), next($args));
				}
			}
		}

		return $this;
	}

	/**
	 * 设置api调用地址
	 * 为兼容之前的代码配置名称不相同, 增加此方法
	 * @author gjy
	 *
	 * @param  string $url
	 * @return boolean
	 */
	public static function setApiUrl($url = '') {
		self::$_apiUrl = trim($url, '/');
		return TRUE;
	}

	/**
	 * 不走模板的业务短信
	 * @author gjy
	 *
	 * @param  array $arrData
	 * @param  array $curlOptions
	 * @return boolean | array # 部分失败时返回失败的list
	 */
	public static function send($arrData = [], $curlOptions = []) {
		if (empty($arrData)) {
			return FALSE;
		}

		if (!Config::get('switch.sms')) {
			return TRUE;
		}

		$curl = new Curl();
		// 默认十秒后超时, 可接收参数更改这个值
		$curl->setopt(CURLOPT_TIMEOUT, isset($curlOptions['timeout']) ? $curlOptions['timeout'] : 10);

		$failureList = [];

		if (empty(self::$_apiUrl)) {
			self::setApiUrl(Config::get('service_api.api.sms'));
		}

		$apiUrl = self::$_apiUrl . '/smsPhone';

		foreach ($arrData as $v) {
			$param = [
				'phone' => $v['phone'],
				'content' => $v['content'],
			];

			$result = $curl->get($apiUrl, $param);

			if (FALSE === $result) {
				$failureList[] = $param;
			}
		}

		return $failureList ?: TRUE;
	}
}
