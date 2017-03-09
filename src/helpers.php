<?php

use Helper\ArrayHelpers;
use Helper\DateHelpers;
use Helper\StringHelpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 这里把一些常用的从Helper59取出来定义成全局的辅助函数了
 * 要增加的话墙裂建议写好看点，按照功能写到一起...
 *
 * helper类里还有一些, 不常用的, 如: Helper\StringHelpers::str_full2half();
 */

if (!function_exists('get_user_info')) {
	/**
	 * 在缓存中取用户id
	 * @author gjy
	 *
	 * @return array
	 */
	function get_user_info() {
		if (empty($_COOKIE['erp_token'])) {
			goto tag_for_get_test_user;
		}

		$token = $_COOKIE['erp_token'];

		// 使用静态变量保证本次请求中无论调用多少次函数都是相同的值
		static $_user_info = [];

		if (isset($_user_info[$token])) {
			return $_user_info[$token];
		}

		$user = Redis::get('erp_token_' . $token);

		if (empty($user)) {
			goto tag_for_get_test_user;
		}

		$_user_info[$token] = json_decode($user, TRUE);
		return $_user_info[$token];

		/**
		 * 判断是否测试环境
		 * 获取预定用户信息或返回空数组
		 * 写在末尾 避免每次正常请求都进行判断
		 */
		tag_for_get_test_user:
		if (env('IS_TESTING')) {
			// 根据需要, 测试时env中可填写不同的TEST_UID
			return [
				'uid' => env('TEST_UID', isset($_COOKIE['TESTINGUID']) ? $_COOKIE['TESTINGUID'] : NULL),
				'isSupper' => env('TEST_USER_IS_SUPER', 1),
				'name' => 'test user',
			];
		} else {
			return [];
		}
	}
}

// 规范化elk日志的辅助函数
if (!function_exists('elk_init')
	&& !function_exists('elk_start')
	&& !function_exists('elk_log')) {

	/**
	 * 初始化日志, 记录操作人
	 * @author gjy
	 *
	 * @param boolean $force 是否强制运行
	 * @return boolean
	 */
	function elk_init($force = FALSE) {

		// 除非强制运行, 其余仅在非命令行模式下记录SERVER信息和操作人
		if (defined('ELK_ALREADY_INIT') || PHP_SAPI === 'cli') {
			return TRUE;
		}

		if (!$force && ('GET' === $_SERVER['REQUEST_METHOD'] || 'HEAD' === $_SERVER['REQUEST_METHOD'])) {
			return FALSE;
		}

		try {
			define('ELK_ALREADY_INIT', 1);
			// 在一个请求中第一次调用时, 记录相关信息

			$requestInfo = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];

			if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])) {
				$requestInfo .= ", from: {$_SERVER['REMOTE_ADDR']}";
			}

			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$requestInfo .= ", agent: {$_SERVER['HTTP_USER_AGENT']}";
			}

			Log::info(REQUEST_UUID . ' >>>>>>>>> ' . $requestInfo);
			Log::info(REQUEST_UUID . ' >>>>>>>>> ' . '操作人信息: ' . json_encode(get_user_info(), JSON_UNESCAPED_UNICODE));

			return TRUE;
		} catch (Exception $e) {
		}

		return FALSE;
	}

	/**
	 * 开始记录日志
	 * @author gjy
	 *
	 * @param  string $operateTitle #这组操作的名称
	 * @param  boolean $overwrite #是否覆盖当前请求记录
	 * @return string #返回日志的唯一请求前缀
	 */
	function elk_start($operateTitle = '', $overwrite = FALSE) {

		static $log_uniq_sign = NULL;

		// 若不是覆盖和第一次执行, 就直接返回log前缀标识
		if (!$overwrite && isset($log_uniq_sign)) {
			return $log_uniq_sign;
		}

		// $overwrite 参数的第二种用法
		// 若这次请求不在监听范围内( 没有运行过elk_init ), 而且是第一次手动调用elk_start
		// 则elk_init函数会初始化强制记录一下相关信息
		if (!defined('ELK_ALREADY_INIT')) {
			elk_init($overwrite && empty($log_uniq_sign));
		}

		$log_uniq_sign = REQUEST_UUID . ' - ' . (empty($operateTitle) ? '' : "[{$operateTitle}] ");
		return $log_uniq_sign;
	}

	/**
	 * 记录信息
	 * @author gjy
	 *
	 * @param  string $msg #日志信息
	 * @param  string $level # laravel支持的7种 RFC 5424 标准记录等级
	 * @return void
	 */
	function elk_log($msg = '', $level = 'info') {
		switch (TRUE) {
		case 'info' === $level:
			$method = 'info';
			break;
		case 'warning' === $level:
			$method = 'warning';
			break;
		case 'error' === $level:
			$method = 'error';
			break;
		case 'notice' === $level:
			$method = 'notice';
			break;
		case 'alert' === $level:
			$method = 'alert';
			break;
		case 'critical' === $level:
			$method = 'critical';
			break;
		case 'debug' === $level:
			$method = 'debug';
			break;
		default:
			$method = 'info';
			break;
		}

		// 记录日志功能不可以造成程序中断
		try {
			Log::{$method}(elk_start() . $msg);
		} catch (Exception $e) {
		}
	}

}

// ------------------------
// 		数组处理函数
// ------------------------

if (!function_exists('array_fetch_map')) {
	/**
	 * 传入数组以及 $keyValue, 返回相应的key => value映射
	 * @author gjy
	 *
	 * @param  array $array
	 * @param  string $keyValue # ex: item_id.title
	 * @return array
	 */
	function array_fetch_map(array $array, $keyValue) {
		return ArrayHelpers::fetch_map($array, $keyValue);
	}
}

if (!function_exists('array_change_index')) {
	/**
	 * 使用某一个下标的值, 当作外层array的index
	 * ！！！key的值需要在数组中唯一, 不然结果不准确
	 * @author gjy
	 *
	 * @param  array $array
	 * @param  string | integer $key
	 * @return array
	 */
	function array_change_index(array $array, $key) {
		return ArrayHelpers::change_index($array, $key);
	}
}

if (!function_exists('array_quick_unique')) {
	/**
	 * 去null后两次flip, 然后merge用来修复数组index
	 * 时间复杂度是O(1) php原生的array_unique是O(n)
	 * @author gjy
	 *
	 * @param  array $array
	 * @return array
	 */
	function array_quick_unique(array $array) {
		return ArrayHelpers::unique($array);
	}
}

if (!function_exists('array_random_value')) {
	/**
	 * 随机取出数组中的值
	 * @author gjy
	 *
	 * @param  array $array
	 * @return array
	 */
	function array_random_value(array $array) {
		return ArrayHelpers::random_value($array);
	}
}

// ------------------------
// 		日期处理函数
// ------------------------

if (!function_exists('full_timestamp')) {
	/**
	 * 当前时间毫秒级时间戳
	 * @author gjy
	 *
	 * @param integer $ex #表示扩展几位数的时间戳
	 * @return string
	 */
	function full_timestamp($ex = 3) {
		return DateHelpers::full_timestamp($ex);
	}
}

if (!function_exists('days_in_month')) {
	/**
	 * 每月有几天
	 * @author gjy
	 *
	 * @param  integer $month
	 * @param  string $year
	 * @return integer
	 */
	function days_in_month($month = 0, $year = '') {
		return DateHelpers::days_in_month($month, $year);
	}
}

// ------------------------
// 		字符处理函数
// ------------------------

if (!function_exists('left_zero_pad')) {
	function left_zero_pad($str, $len) {
		return str_pad($str, $len, '0', STR_PAD_LEFT);
	}
}

// ------------------------
// 			其他函数
// ------------------------

if (!function_exists('record_error')) {
	/**
	 * 手动记录异常\错误信息
	 * @author gjy
	 *
	 * @param  Error | Exception $e
	 * @return boolean
	 */
	function record_error($e) {
		if ($e instanceof Error) {
			@Log::error('-> ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . 'detail: ' . $e->getTraceAsString());
		} else if ($e instanceof Exception) {
			@Log::warning('-> ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . 'detail: ' . $e->getTraceAsString());
		} else {
			return FALSE;
		}

		return TRUE;
	}
}

if (!function_exists('build_pages')) {
	/**
	 * 计算分页参数
	 * @author gjy
	 *
	 * @param  Illuminate\Http\Request $request
	 * @param  integer $limit
	 * @return array
	 */
	function build_pages(Illuminate\Http\Request $request, $limit = Const\DefaultValue::PAGE_SIZE) {
		$page = intval($request->input('page', 1));
		$limit = intval($request->input('limit', $limit));
		return [
			'page' => $page,
			'limit' => $limit,
			'offset' => $page > 1 ? ($page - 1) * $limit : 0,
		];
	}
}



if (!function_exists('new_hessian_client')) {

	/**
	 * 实例化hessian客户端
	 * @author gjy
	 *
	 * @param  string $url
	 * @param  array|NULL $options
	 * @return HessianClient
	 */
	function new_hessian_client($url = '', array $options = NULL) {
		if (empty($url)) {
			return NULL;
		}

		static $_hessian_client_list = [];

		$optionSign = md5(json_encode($options));

		if (isset($_hessian_client_list[$url][$optionSign])) {
			return $_hessian_client_list[$url][$optionSign];
		}

		if (class_exists('HessianClient')) {
			goto tag_for_new_hessian_client;
		}

		if (!is_file(__DIR__ . '/Libraries/HessianPHP_v2.0.3/src/HessianClient.php')) {
			throw new Exception('HessianPHP加载失败', 10086);
		}

		require __DIR__ . '/Libraries/HessianPHP_v2.0.3/src/HessianClient.php';

		tag_for_new_hessian_client:

		if (empty($options)) {
			$options = NULL;
		}

		$_hessian_client_list[$url][$optionSign] = new HessianClient($url, $options);
		return $_hessian_client_list[$url][$optionSign];
	}
}


if (!function_exists('fastcgi_finish_request')) {
	/**
	 * 空函数，
	 * 用于使用了fastcgi_finish_request之后，代码能在非fpm环境下运行
	 *
	 * @return boolean
	 */
	function fastcgi_finish_request() {
		return TRUE;
	}
}

if (!function_exists('support_file_return')) {

	/**
	 * 返回文件配置
	 * # 用于load_config等函数进行加载support项目中的公共配置
	 * @author gjy
	 *
	 * @param  string $type
	 * @param  string $name
	 * @param  mixed $default
	 * @return mixed
	 */
	function support_file_return($type, $name, $default = NULL) {

		if (empty($name)) {
			return $default;
		}

		static $_common_config = [
			'config' => [],
			'template' => [],
		];

		if (!isset($_common_config[$type])) {
			return FALSE;
		}

		if (isset($_common_config[$type][$name])) {
			return $_common_config[$type][$name];
		}

		$params = explode('.', $name);

		if (!is_file($file = __DIR__ . '/' . ucfirst($type) . '/' . array_shift($params) . '.php')) {
			return $default;
		}

		$result = include $file;
		if (!is_array($result)) {
			return $default;
		}

		// 只有一个参数时返回整个文件的数据
		if (!empty($params)) {
			foreach ($params as $v) {
				if (empty($result[$v])) {
					return $default;
				}

				$result = $result[$v];
			}
		}

		$_common_config[$type][$name] = $result;
		return $result;
	}
}

if (!function_exists('load_template')) {
	/**
	 * 加载公共模板
	 * @author gjy
	 *
	 * @param  string $name
	 * @param  mixed $default
	 * @return array
	 */
	function load_template($name = '', $default = NULL) {
		return support_file_return('template', $name, $default);
	}
}

if (!function_exists('load_config')) {
	/**
	 * 加载公共配置
	 * @author gjy
	 *
	 * @param  string $name
	 * @param  mixed $default
	 * @return array
	 */
	function load_config($name = '', $default = NULL) {
		return support_file_return('config', $name, $default);
	}
}
