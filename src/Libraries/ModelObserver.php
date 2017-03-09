<?php

namespace Lib;

use Model\Store\OperateLog;

/**
 * Class ModelObserver
 * @author xujx@59store.com
 * @package Lib59
 * @desc 模型观察者类, 对模型的更新/新增/删除记录日志
 */
class ModelObserver {

	/**
	 * 缓存更新前的数据
	 * @var array
	 */
	protected static $temp = [];

	/**
	 * 模型类
	 * @var array
	 */
	protected $Model;

	/**
	 * 主键
	 * @var string
	 */
	protected $pk = '';

	/**
	 * 表名
	 * @var string
	 */
	protected $table = '';

	/**
	 * 更新时触发
	 */
	public function updating($model) {
		$this->setTableInfo($model);
		if (isset($model[$this->pk])) {
			static::$temp = $this->Model->find($model[$this->pk]);
		}
	}

	/**
	 * 更新后触发
	 */
	public function updated($model) {
		$this->setTableInfo($model);
		$updateJson = [
			'before' => static::$temp,
			'after' => $model,
		];

		$this->_recordLog(
			json_encode($updateJson, JSON_UNESCAPED_UNICODE),
			'UPDATE',
			$this->table,
			isset($model[$this->pk]) ? $model[$this->pk] : 0
		);
	}

	/**
	 * 创建后触发
	 */
	public function created($model) {
		$this->setTableInfo($model);

		$this->_recordLog(
			$model->toJSON(),
			'CREATE',
			$this->table,
			isset($model[$this->pk]) ? $model[$this->pk] : 0
		);
	}

	/**
	 * 删除后触发
	 */
	public function deleted($model) {
		$this->setTableInfo($model);
		$this->_recordLog(
			$model->toJSON(),
			'DELETE',
			$this->table,
			isset($model[$this->pk]) ? $model[$this->pk] : 0
		);
	}

	/**
	 * 根据模型设置表的信息
	 * @param $model 模型
	 */
	public function setTableInfo($model) {
		$class = get_class($model);
		$this->Model = new $class;
		$this->pk = $this->Model->getKeyName();
		$this->table = $this->Model->getConnection()->getDatabaseName()
			. '.' . $this->Model->getConnection()->getTablePrefix()
			. $this->Model->getTable();
	}

	/**
	 * 记录日志
	 * 原先在模型中的整理出来了
	 *
	 * @param  string $message
	 * @param  string $tag
	 * @param  string $table
	 * @param  string $table_id
	 * @return boolean
	 */
	private function _recordLog($message = '', $tag = '', $table = '', $primaryKey = '') {
		$userInfo = get_user_info();

		$log = [
			'uid' => isset($userInfo['uid']) ? $userInfo['uid'] : 0,
			'uname' => isset($userInfo['name']) ? $userInfo['name'] : 'unknow',
			'message' => $message,
			'tag' => $tag,
			'operate_table' => $table,
			'pk' => $primaryKey,
		];

		/** 非命令行运行时记录ip */
		if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])) {
			$log['ip'] = $_SERVER['REMOTE_ADDR'];
		}

		return OperateLog::insert($log);
	}
}
