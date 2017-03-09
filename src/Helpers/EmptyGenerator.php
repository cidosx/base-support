<?php

namespace Helper;

use ArrayAccess;
use stdClass;

/**
 * 空数据生成
 * 可当做对象或数组直接使用
 *
 * 适用场合:
 * 某个数据为空, 又不想在赋值的时候判断太多
 *
 * $data = somequery();
 * if (empty($data)) {
 *     $data = new EmptyGenerator;
 * }
 *
 * $data->test
 * $data['hehe']
 * $data->并没有值
 * 以上都是空字符
 *
 */
class EmptyGenerator implements ArrayAccess {

	private $__type;

	function __construct($type = 'string') {
		$this->__type = $type;
	}

	function __get($name) {
		switch ($this->__type) {
		case 'int':
		case 'integer':
			return 0;
		case 'string':
			return '';
		case 'array':
			return [];
		case 'object':
			return new stdClass;
		}
	}

	public function offsetSet($offset, $value) {
	}
	public function offsetExists($offset) {
	}
	public function offsetUnset($offset) {
	}

	public function offsetGet($offset) {
		// 调用get
		return $this->__get($offset);
	}
}

