<?php

namespace Const;

/**
 * error dict
 */
class Error {

	/** 全局 */
	CONST PERMISSION_DENIED = '没有访问权限';
	CONST LOGIN_INFO_NOT_FOUND = '登录信息获取失败';
	CONST IERR = '内部错误';

	/** 数据库操作 */
	CONST INSERT_ERROR = '添加失败';
	CONST ALREADY_INSERT = '数据已添加';
	CONST DELETE_ERROR = '删除失败';
	CONST ALREADY_DELETE = '数据已删除';
	CONST UPDATE_ERROR = '更新失败';
	CONST SAME_RECORD_ALREADY_EXIST = '相同记录已存在';

	/** hessian相关 */
	CONST HESSIAN_INIT_ERR = 'hessian初始化失败';
	CONST HESSIAN_CONNECT_ERR = 'hessian服务连接失败';
	CONST HESSIAN_CONF_ERR = 'hessian配置错误';
	CONST HESSIAN_OP_ERR = 'hessian service操作失败';
	CONST HESSIAN_RETURN_ERR = 'hessian service返回数据错误';

	/** 配置 */
	CONST MODEL_NOT_EXIST = '数据模型不存在';
	CONST TEMPLATE_NOT_EXIST = '模板配置不存在';
	CONST TEMPLATE_SYNTAX_ERROR = '模板配置格式错误';

	/** 请求数据-参数 */
	CONST REQUEST_PARAMS_MISSING = '缺少参数';
	CONST REQUEST_PARAMS_ERROR = '参数错误';
	CONST WRONG_DATE_FORMAT = '日期格式不正确';
	CONST TIMESPAN_OUT_OF_LIMIT = '时间区段超出限制';
	CONST VALUE_OUT_OF_USEABLE_LIMIT = '参数值不在可用范围';

	/** 操作相关 */
	CONST OPERATE_NOT_AUTHORIZED = '没有操作权限';
	CONST OPERATE_STATUS_DENIED = '当前状态不允许操作';
	CONST OPERATE_STATUS_FAILURE = '操作失败';

}
