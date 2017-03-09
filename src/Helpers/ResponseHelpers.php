<?php

namespace Helper;

/**
 * ResponseHelpers
 *
 * BaseResponseTrait中也使用了这里的函数, 推荐对象方式调用
 * 这里也可以直接调用, 适用于非控制器中
 */
class ResponseHelpers {

	/**
	 * 格式化response数据
	 *
	 * @param  mixed $data
	 * @param  string $message
	 * @param  integer $status
	 * @return array
	 */
	public static function format_response_data($data, $message, $status) {
		return [
			'status' => $status,
			'msg' => $message,
			'data' => $data,
			'request_uuid' => defined('REQUEST_UUID') ? REQUEST_UUID : NULL,
		];
	}

	/**
	 * 输出json字符串
	 * ps: 注意不要在这个函数之后再echo任何内容了 !!!
	 * @author gjy
	 *
	 * @param  array $data
	 * @return void
	 */
	public static function echo_json(array $data = []) {
		header('Content-type: application/json');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 结束请求
	 * ps: 仅在 php-fpm 环境下有实际效果
	 * @author gjy
	 *
	 * @param  array $data
	 * @return boolean
	 */
	public static function finish_request(array $data = []) {
		// 如果传了数组就顺便echo一个json
		if (!empty($data)) {
			self::echo_json($data);
		}

		return fastcgi_finish_request();
	}
}
