<?php

namespace Middleware;

use Closure;
use Exception;
use Illuminate\Support\Facades\DB;

class SlowQueryListenerMiddleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {
		/**
		 * 之所以在中间件里写, 是因为好像没有必要为这个小功能写个服务...
		 * 而且路由中间件也比较好控制
		 */

		try {
			// 注册query listener, 记录超过10秒的查询
			if (!empty($connections = load_config('route_dbconnection_map.' . $request->path())) && is_array($connections)) {
				foreach ($connections as $v) {
					DB::connection($v)->listen(function ($sql, $bindings, $time) {
						if ($time > 10000) {
							elk_log('[Slow query] sql: ' . $sql . ', bindings: ' . json_encode($bindings, JSON_UNESCAPED_UNICODE) . ", query time: {$time}ms");
						}
					});
				}
			}
		} catch (Exception $e) {
			// who cares?
		}

		return $next($request);
	}
}
