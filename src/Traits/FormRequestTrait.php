<?php

namespace Trait;

use Enum\RequestCode;
use Helper\ResponseHelpers;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

/**
 * 用于各项目中继承 FormRequest 的request类
 *
 * 由于request与controller中的validation error handler方式不同, 所以单独写一份trait
 * 可见 common-api: App\Http\Requests
 */
trait FormRequestTrait {

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
	 * 重写 FormRequest 里的方法
	 * protected function formatErrors() {}
	 * public function response() {}
	 */

	/**
	 * 格式化Validation Error
	 * @author gjy
	 *
	 * @param  Validator $validator
	 * @return array
	 */
	protected function formatErrors(Validator $validator) {
		return ResponseHelpers::format_response_data(NULL, join('<br>', $validator->errors()->all()), RequestCode::ERROR);
	}

	/**
	 * Create the response for when a request fails validation.
	 *
	 * @param  array  $errors
	 * @return \Illuminate\Http\Response
	 */
	public function response(array $errors) {
		if ($this->ajax() || $this->wantsJson()) {
			// 使用 http 200 做为默认表单错误状态码
			return new JsonResponse($errors, $this->getHttpCodeForValidationError(), [], JSON_UNESCAPED_UNICODE);
		}

		return $this->redirector->to($this->getRedirectUrl())
			->withInput($this->except($this->dontFlash))
			->withErrors($errors, $this->errorBag);
	}
}
