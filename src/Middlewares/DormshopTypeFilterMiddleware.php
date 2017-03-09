<?php

namespace Middleware;

use Closure;
use Const;
use Enum\Dorm\DormShopType;
use Trait\BaseResponseTrait;

/**
 * 店铺类型过滤中间件, 使用 url last segment 识别
 */
class DormshopTypeFilterMiddleware {

	use BaseResponseTrait;

	/**
	 * 根据路由分配查询类型
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {
		$queryType = last($request->segments());
		if (!DormShopType::has_const($queryType)) {
			return $this->respondWithError(Const\Error::PERMISSION_DENIED);
		}

		// 转换为值
		$request->dormShopType = $queryType;
		$request->dormShopTypeValue = DormShopType::const_to_value($queryType);

		return $next($request);
	}
}
