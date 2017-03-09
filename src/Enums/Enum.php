<?php

namespace Enum;

use Helper\HessianHelpers;
use ReflectionClass;
use UnexpectedValueException;

/**
 * Abstract class that enables creation of PHP enums. All you
 * have to do is extend this class and define some constants.
 * Enum is an object with value from on of those constants
 * (or from on of superclass if any). There is also
 * __default constat that enables you creation of object
 * without passing enum value.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @revice gjy <ginnerpeace@live.com>
 * @link http://php4every1.com
 */
abstract class Enum {

	/**
	 * Constant with default value for creating enum object
	 */
	const __default = NULL;

	/**
	 * 枚举值
	 * @var mixed
	 */
	private $value;

	/**
	 * 是否为严格比对
	 * @var boolean
	 */
	private $strict;

	/**
	 * 枚举名称
	 * 后期想改造一下, 这个属性之外的所有属性定义成私有
	 * 尽量可以无缝对接Java枚举类
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * value => constName
	 * @var array
	 */
	protected $consts = [];

	/**
	 * constName => value
	 * @var array
	 */
	protected $constsRev = [];

	/**
	 * constName => chinese
	 * @var array
	 */
	protected $keyMap = [];

	/**
	 * 类常量
	 * @var array
	 */
	private static $__constants = [];

	/**
	 * 保存类实例
	 * @var array
	 */
	private static $__instance = [];


	/**
	 * Creates new enum object. If child class overrides __construct(),
	 * it is required to call parent::__construct() in order for this
	 * class to work as expected.
	 *
	 * @param mixed $initialValue Any value that is exists in defined constants
	 * @param bool $strict If set to TRUE, type and value must be equal
	 * @throws UnexpectedValueException If value is not valid enum value
	 */
	public function __construct($initialValue = NULL, $strict = TRUE) {

		if (!array_key_exists($this->getClass(), self::$__constants)) {
			$this->populateConstants();
		}

		$temp = self::$__constants[$this->getClass()];

		if ($initialValue === NULL) {
			$initialValue = $temp['__default'];
		}

		if (!in_array($initialValue, $temp, $strict)) {
			throw new UnexpectedValueException('Value is not in enum ' . $this->getClass());
		}

		// get const name
		unset($temp['__default']);
		$nameMap = array_flip($temp);

		if (array_key_exists($initialValue, $nameMap)) {
			$this->name = $nameMap[$initialValue];
		}

		$this->value = $initialValue;
		$this->strict = $strict;

		// const->value
		$this->consts = $temp;
		// value->const
		$this->constsRev = $nameMap;
		// const->text
		foreach ($nameMap as $k => $v) {
			$this->keyMap[$v] = $this->_map[$k];
		}
	}

	/**
	 * Returns string representation of an enum. Defaults to
	 * value casted to string.
	 *
	 * @return string String representation of this enum's value
	 */
	public function __toString() {
		return (string) $this->value;
	}

	/**
	 * Returns list of all defined constants in enum class.
	 * Constants value are enum values.
	 *
	 * @param bool $includeDefault If TRUE, default value is included into return
	 * @return array Array with constant values
	 */
	public function getConstList($includeDefault = FALSE) {

		if (!array_key_exists($this->getClass(), self::$__constants)) {
			$this->populateConstants();
		}

		return $includeDefault ? array_merge(self::$__constants[__CLASS__], array(
			'__default' => self::__default,
		)) : self::$__constants[__CLASS__];
	}

	private function populateConstants() {

		$r = new ReflectionClass($this->getClass());
		$constants = $r->getConstants();

		self::$__constants = array(
			$this->getClass() => $constants,
		);
	}

	/**
	 * Checks if two enums are equal. Only value is checked, not class type also.
	 * If enum was created with $strict = TRUE, then strict comparison applies
	 * here also.
	 *
	 * @return bool True if enums are equal
	 */
	public function equals($object) {
		if (!($object instanceof Enum)) {
			return FALSE;
		}

		return $this->strict ? ($this->value === $object->value)
		: ($this->value == $object->value);
	}

	/**
	 * 判断是否有此名称的枚举
	 * @author gjy
	 *
	 * @param  string $constName
	 * @return boolean
	 */
	public function hasConst($constName) {
		return array_key_exists($constName, $this->consts);
	}

	/**
	 * 判断枚举中是否有这个值
	 * @author gjy
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function hasValue($value) {
		return in_array($value, $this->consts, $this->strict);
	}

	/**
	 * 名称转换值
	 * @author gjy
	 *
	 * @param  string $constName
	 * @return mixed
	 */
	public function constToValue($constName) {
		if (!$this->hasConst($constName)) {
			throw new UnexpectedValueException("Const {$constName} is not in Enum" . $this->getClass());
		}

		return $this->consts[$constName];
	}

	/**
	 * 值转名称
	 * @author gjy
	 *
	 * @param  mixed $value
	 * @return string
	 */
	public function valueToConst($value) {
		if (!$this->hasValue($value)) {
			throw new UnexpectedValueException("Value {$value} is not in Enum" . $this->getClass());
		}

		return $this->constsRev[$value];
	}

	/**
	 * const名称转中文
	 * @author gjy
	 *
	 * @param  string $constName
	 * @return string
	 */
	public function transConst($constName) {
		if ($this->hasConst($constName)) {
			return $this->keyMap[$constName];
		}

		return $constName;
	}

	/**
	 * 值转中文
	 * @author gjy
	 *
	 * @param  mixed $value
	 * @return string
	 */
	public function transValue($value) {
		if ($this->hasValue($value)) {
			return $this->_map[$value];
		}

		return $value;
	}

	/** getter */

	public function getName() {
		return $this->name;
	}

	public function getValue() {
		return $this->value;
	}

	public function getConsts() {
		return $this->consts;
	}

	public function getConstsRev() {
		return $this->constsRev;
	}

	public function getMap() {
		return $this->_map;
	}

	public function getKeyMap() {
		return $this->keyMap;
	}

	public function getClass() {
		return static::class;
	}

	/**
	 * 使用静态方法创建类的实例
	 * @author gjy <ginnerpeaceg@live.com>
	 *
	 * @return object
	 */
	public static function getInstance() {
		if (empty(self::$__instance[static::class]) || !self::$__instance[static::class] instanceof static) {
			self::$__instance[static::class] = new static();
		}

		return self::$__instance[static::class];
	}

	/**
	 * __callStatic
	 * 使用蛇形命名静态调用驼峰命名的对象方法
	 * 如:
	 * 1. xxxEnum::_hasConst('CONST_NAME')
	 * 2. xxxEnum::has_const('CONST_NAME')
	 *
	 * camel_case() 是laravel中的 string helper function
	 *
	 * @author gjy
	 *
	 * @param  string $func
	 * @param  array $arguments
	 * @return mixed
	 */
	public static function __callStatic($func, $arguments) {
		return call_user_func_array([self::getInstance(), camel_case($func)], $arguments);
	}
}
