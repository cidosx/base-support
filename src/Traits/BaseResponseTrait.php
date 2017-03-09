<?php

namespace Trait;

use Enum\RequestCode;
use Helper\ResponseHelpers;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 用于基础控制器的trait
 */
trait BaseResponseTrait {

	use ValidatesRequests;

	/**
	 * 集中管理返回数据
	 *
	 * @param  mixed $data
	 * @param  string $message
	 * @param  integer $status
	 * @return array
	 */
	public static function formatResponseData($data, $message, $status) {
		return ResponseHelpers::format_response_data($data, $message, $status);
	}

	/**
	 * 返回错误信息(状态)
	 *
	 * @param  string $message
	 * @param  integer $status
	 * @return JsonResponse
	 */
	public function respondWithError($message = 'Error', $status = RequestCode::ERROR) {
		return $this->jsonResponse(NULL, $message, $status);
	}

	/**
	 * 返回json消息
	 *
	 * @param  mixed $data
	 * @param  string $message
	 * @param  numeric $status
	 * @param  array $headers
	 * @return JsonResponse
	 */
	public function jsonResponse($data = NULL, $message = 'OK', $status = RequestCode::SUCCESS, $httpCode = JsonResponse::HTTP_OK, $headers = []) {
		if ('GET' !== $_SERVER['REQUEST_METHOD']) {
			elk_log("Request End: status -> {$status}, msg -> {$message}, http code -> {$httpCode}");
		}

		return new JsonResponse(
			self::formatResponseData($data, $message, $status),
			$httpCode, $headers, JSON_UNESCAPED_UNICODE
		);
	}

	/**
	 * 输出json字符串
	 * @author gjy
	 *
	 * @param  array $data
	 * @return void
	 */
	public function echoJson(array $data = []) {
		ResponseHelpers::echo_json($data);
	}

	/**
	 * 结束请求
	 * ps: 仅在 php-fpm 环境下有实际效果
	 * @author gjy
	 *
	 * @param  array $data
	 * @return boolean
	 */
	public function finishRequest($data = NULL, $message = 'OK', $status = RequestCode::SUCCESS, $httpCode = JsonResponse::HTTP_OK) {
		if ('GET' !== $_SERVER['REQUEST_METHOD']) {
			elk_log("Request End: status -> {$status}, msg -> {$message}, http code -> {$httpCode}");
		}

		// 返回json并调用fastcgi_finish_request
		return ResponseHelpers::finish_request(
			self::formatResponseData($data, $message, $status)
		);
	}

	/**
	 * 使用code返回信息, 若没有指定, 就返回http标准信息
	 * @author gjy
	 *
	 * @param  integer $httpCode
	 * @param  string|NULL $message
	 * @return JsonResponse
	 */
	public function responseByCode($httpCode, $message = NULL) {
		if (empty($message)) {
			$codesMap = JsonResponse::$statusTexts;
			if (!isset($codesMap[$httpCode])) {
				$httpCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
			}

			$message = $codesMap[$httpCode];
			unset($codesMap);
		}

		return $this->jsonResponse(NULL, $message, RequestCode::ERROR, $httpCode);
	}

	/**
	 * 重写 Illuminate\Foundation\Validation\ValidatesRequests 中的方法
	 *
	 * protected function formatValidationErrors() {}
	 * protected function buildFailedValidationResponse() {}
	 */

	/**
	 * 表单验证失败后返回的 HTTP status
	 * 默认为 200
	 * @author gjy
	 *
	 * @return integer
	 */
	protected function getHttpCodeForValidationError() {
		return JsonResponse::HTTP_OK;
	}

	/**
	 * 格式化表单验证错误, 用于Controller中的验证
	 * @author gjy
	 *
	 * @param  \Illuminate\Contracts\Validation\Validator $validator
	 * @return array
	 */
	protected function formatValidationErrors(Validator $validator) {
		// BaseResponseTrait 中定义
		return self::formatResponseData(NULL, join('<br>', $validator->errors()->all()), RequestCode::ERROR);
	}

	/**
	 * Create the response for when a request fails validation.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  array  $errors
	 * @return \Illuminate\Http\Response
	 */
	protected function buildFailedValidationResponse(Request $request, array $errors) {
		if ($request->ajax() || $request->wantsJson()) {
			// 将httpCode 422 改为 200
			return new JsonResponse($errors, $this->getHttpCodeForValidationError(), [], JSON_UNESCAPED_UNICODE);
		}

		return redirect()->to($this->getRedirectUrl())
					->withInput($request->input())
					->withErrors($errors, $this->errorBag());
	}

}
