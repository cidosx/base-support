<?php

namespace Helper;

/**
 * 自定义数组辅助函数
 */
class ArrayHelpers {

	/**
	 * 性能更高的数组去重
	 * 解决 array_unique 使用快排算法处理大数组时的性能损耗问题
	 * @author gjy
	 *
	 * @param  array $arrData
	 * @return array
	 */
	public static function unique(array $arrData = array()) {
		return array_merge(array_flip(array_flip(array_filter($arrData))));
	}

	/**
	 * array_map扩展, 实现多维数组处理
	 * @author gjy
	 *
	 * @param  string $filter # 处理数组值的函数名称, 如有自定义的函数务必写在 class{} 外面 !
	 * @param  array $data
	 * @return array
	 */
	public static function array_map_recursive($filter, $data) {
		$return = array();
		foreach ($data as $key => $val) {
			$return[$key] = is_array($val) ? self::array_map_recursive($filter, $val) : $filter($val);
		}
		return $return;
	}

	/**
	 * 随机取数组中一个值
	 * @author gjy
	 *
	 * @param  array $array
	 * @return array | mixed
	 */
	public static function random_value(array $array) {
		return empty( $array ) ? [] : $array[array_rand($array)];
	}

	/**
	 * fetch some item
	 * @author gjy
	 *
	 * @param  mixed $items
	 * @param  array $array
	 * @param  mixed $default
	 * @return array
	 */
	public static function fetch_items(array $array, $items, $default = NULL) {
		if (is_numeric($items) || is_string($items)) {
			return isset($array[$items]) ? $array[$items] : $default;
		}

		if (!is_array($items)) {
			return $default;
		}

		$return = array();
		foreach ($items as $item) {
			$return[$item] = isset($array[$item]) ? $array[$item] : $default;
		}

		return $return;
	}

	/**
	 * 二维数组里fetch, 保留键名
	 * @author gjy
	 *
	 * @param  array $array
	 * @param  array $items
	 * @param  mixed $default
	 * @return array
	 */
	public static function fetch_more(array $array, $items, $default = NULL) {
		$return = array();

		foreach ($array as $key => $val) {
			$return[$key] = array();
			foreach ($items as $v) {
				$return[$key][$v] = isset($val[$v]) ? $val[$v] : $default;
			}
		}

		return $return;
	}

	/**
	 * 使用传入的key当作外层array的index
	 * @author gjy
	 *
	 * @param  array $array
	 * @param  string | integer $key
	 * @return array
	 */
	public static function change_index(array $array, $key) {
		$check = reset($array);
		if (!isset($check[$key])) {
			return array();
		}

		$results = array();
		foreach ($array as $v) {
			if (isset($results[$v[$key]])) {
				continue;
			}

			$results[$v[$key]] = $v;
		}

		return $results;
	}

	/**
	 * fetch array return map
	 * @author gjy
	 *
	 * @param  array $array
	 * @param  string $keyValue # ex: item_id.title
	 * @return array
	 */
	public static function fetch_map(array $array, $keyValue) {
		$check = reset($array);
		$kv = explode('.', $keyValue);
		if (count($kv) !== 2 || !isset($check[$kv[0]], $check[$kv[1]])) {
			return array();
		}

		$indexKey = $kv[0];
		$valueKey = $kv[1];

		$results = array();
		foreach ($array as $v) {
			if (isset($results[$v[$indexKey]])) {
				continue;
			}

			$results[$v[$indexKey]] = $v[$valueKey];
		}

		return $results;
	}
}
