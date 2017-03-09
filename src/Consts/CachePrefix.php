<?php

namespace Const;

/**
 * 缓存key的前缀
 * ( 使用兼容redis的写法 )
 */
class CachePrefix {

	/**
	 * 公共的cache
	 */
	CONST COMMON = 'erp:common:';

	/**
	 * 上传信息的中转缓存区
	 * ( 用于在上传后的第二次请求中, 可使用相应参数获取之前上传的信息 )
	 */
	CONST SWAP_UPLOAD = 'erp:swap:upload:';

}
