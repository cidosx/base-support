<?php

namespace Helper;

class SystemHelpers {

	/**
	 * 设置内存限制
	 * @author gjy
	 *
	 * @param  number $memory
	 * @return void
	 */
	public static function set_memory_limit($memory) {
		if (function_exists('ini_set')) {
			$memoryInBytes = function ($memory) {
				$unit = strtolower(substr($memory, -1, 1));
				$memory = (int) $memory;
				switch ($unit) {
				case 'g':
					$memory *= 1024 * 1024 * 1024;
				case 'm':
					$memory *= 1024 * 1024;
				case 'k':
					$memory *= 1024;
				}

				return $memory;
			};

			$memoryLimit = trim(ini_get('memory_limit'));
			// 对低于传入值的进行设置
			if ($memoryLimit != -1 && $memoryInBytes($memoryLimit) < $memory * 1024 * 1024) {
				@ini_set('memory_limit', $memory . 'M');
			}
			unset($memoryInBytes, $memoryLimit);
		}
	}
}
