<?php

namespace Enum;

/**
 * 存储位置
 */
class StorageLocation extends Enum {

	CONST OSS = 'oss';
	CONST DISK = 'disk';

	private $_map = [
		self::SL_OSS => 'oss',
		self::SL_DISK => '本地',
	];

	function __construct($name) {
		return $this->{$name};
	}
}
