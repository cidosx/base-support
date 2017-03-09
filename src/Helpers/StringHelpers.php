<?php

namespace Helper;

use Const\Regex;

class StringHelpers {

	/**
	 * 返回传入的字母附近第n个字母
	 * @author gjy
	 *
	 * @param  string $letter
	 * @param  integer $num
	 * @return string
	 */
	public static function neighbor_letter($letter = '', $num = 1) {
		$num = (int) $num;
		if ($num === 0) {
			return $letter;
		}

		$ascllDec = ord($letter) + $num;
		if ($ascllDec < 65
			|| ($ascllDec > 90 && $ascllDec < 97)
			|| $ascllDec > 122) {
			return '';
		}

		return chr($ascllDec);
	}

	/**
	 * 全角字符转半角
	 * @author anonymous
	 *
	 * @param  string $str
	 * @param  string $coding
	 * @return string
	 */
	public static function str_full2half($str = '', $coding = 'UTF-8') {
		if (empty($str)) {
			return '';
		}

		if ($coding !== 'UTF-8') {
			$str = mb_convert_encoding($str, 'UTF-8', $coding);
		}

		$ret = '';

		for ($i = 0; $i < strlen($str); ++$i) {
			$s1 = $str[$i];
			if (($c = ord($s1)) & 0x80) {
				$s2 = $str[++$i];
				$s3 = $str[++$i];
				$c = (($c & 0xF) << 12) | ((ord($s2) & 0x3F) << 6) | (ord($s3) & 0x3F);
				if ($c == 12288) {
					$ret .= ' ';
				} elseif ($c > 65280 && $c < 65375 && $c != 65374) {
					$c -= 65248;
					$ret .= chr($c);
				} else {
					$ret .= $s1 . $s2 . $s3;
				}
			} else {
				$ret .= $str[$i];
			}
		}

		if ($coding === 'UTF-8') {
			return $ret;
		}

		return mb_convert_encoding($ret, $coding, 'UTF-8');
	}

	/**
	 * 去除制定的html标签
	 * @author gjy
	 *
	 * @param  string $str
	 * @param  string $type
	 * @return string
	 */
	public static function strip_html_tags($str = '', $type = 'script') {
		if (empty($str)) {
			return '';
		}

		$stripType = array(
			'all' => '@<[\/\!]*?[^<>]*?>@si', // 所有标签, 等同于 strip_tags()
			'script' => '@<script[^>]*?>.*?</script>@si', // 去除js
			'style' => '@<style[^>]*?>.*?</style>@siU', // 去除css
			'comments' => '@<![\s\S]*?--[ \t\n\r]*>@', // 去除注释
			'html' => '@<html[^>]*?>.*?</html>@si', // 去除html标签
			'body' => '@<body[^>]*?>.*?</body>@si', // 去除body标签
		);

		if (empty($stripType[$type])) {
			return $str;
		}

		return preg_replace($stripType[$type], '', $str);
	}

	/**
	 * 生成随机字符串
	 * @author CodeIgniter developer
	 * @revisor gjy
	 *
	 * @param  string $type
	 * @param  integer $len
	 * @return string
	 */
	public static function random_string($type = 'distinct_num', $len = 8) {
		switch (TRUE) {
		default:
		case 'numeric' === $type:
			$pool = '1234567890';
			break;
		case 'nozero' === $type:
			$pool = '123456789';
			break;
		case 'alnum' === $type:
			$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 'alpha' === $type:
			$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 'distinct' === $type:
			$pool = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
			break;
		case 'distinct_num' === $type:
			$pool = '3456789abcdefghijkmnpqrstuvwxyABCDEFGHIJKLMNPQRSTUVWXY';
			break;
		case 'abc123' === $type:
			$pool = '3456789abcdefghijkmnpqrstuvwxy';
			break;
		case 'md5' === $type:
			return md5(uniqid(mt_rand()));
		case 'sha1' === $type:
			return sha1(uniqid(mt_rand(), TRUE));
		}

		return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
	}

	/**
	 * 生成可识别的序列号
	 * @author gjy
	 *
	 * @param  string $type
	 * @return string 至少32位
	 */
	public static function gen_serial_no($type = 'numeric', $len = 0) {
		if ('numeric' === $type) {
			/** 长度为32+的基本数字流水号 */
			$time = microtime(TRUE);
			$micro = sprintf('%06d', ($time - floor($time)) * 1000000);
			// 时间&毫秒(20) + 随机数位(12+n)
			return date('YmdHis' . $micro, $time) . self::random_string('numeric', 12 + ($len < 1 ? 0 : intval($len)));
		} else {
			return md5(uniqid(mt_rand()));
		}
	}

	/**
	 * 去除字符两边的标点符号 (中英文)
	 * @author gjy
	 *
	 * @param  string $string
	 * @return string
	 */
	public static function trim_punctuation($string = '', $pos = '') {
		$pos = strtolower($pos);
		switch (TRUE) {
		case 'l' === $pos:
			$pattern = '/^' . Regex::PUNCTUATION_UNICODE . '*/';
			break;
		case 'r' === $pos:
			$pattern = '/' . Regex::PUNCTUATION_UNICODE . '*$/';
			break;

		default:
			$pattern = [
				// left
				'/^' . Regex::PUNCTUATION_UNICODE . '*/',
				// right
				'/' . Regex::PUNCTUATION_UNICODE . '*$/',
			];
			break;
		}

		return urldecode(preg_replace($pattern, '', urlencode($string)));
	}

	/**
	 * 下划线样式转换成驼峰字符串
	 * @author xsp
	 *
	 * @param  string $string
	 * @param  boolean $ucfirst 首字符是否转换
	 * @return string
	 */
	public static function toCamelCase ($str , $ucfirst = false)
	{

		while(($pos = strpos($str , '_')) !== false)
			$str = substr($str , 0 , $pos) . ucfirst(substr($str , $pos+1));

		return $ucfirst ? ucfirst($str) : $str;
	}


	/**
	 * url样式转换成驼峰字符串
	 * @author xsp
	 *
	 * @param  string $string
	 * @param  boolean $ucfirst 首字符是否转换
	 * @return string
	 */
	public static function urlToCamelCase($str, $ucfirst = false)
	{
		$str = preg_replace_callback(
			'|([\\/_])([a-z])|',
			function ($matches)
			{
				return ucfirst($matches[2]);
			},
			$str);

		return $ucfirst ? ucfirst($str) : $str;
	}

	/**
	 * 驼峰字符串转换成下划线样式
	 *
	 * @author xsp
	 *
	 * @param  string $string
	 * @return string
	 *
	 * toUnderline		=> to_underline
	 * ToUnderline		=> _to_underline
	 * _toUnderline		=> _to_underline
	 * _to_Underline	=> _to_underline
	 * _A_A_A_A_		=> _a_a_a_a_
	 */
	public static function toUnderline ($str)
	{
		return strtolower(preg_replace('/(?<!\_)(?=[A-Z])/', '_', $str));
	}

	/**
	 * 按行将字符处理为数组
	 * @author gjy
	 *
	 * @param  string $string
	 * @return array
	 */
	public static function row_explode($string) {
		return ArrayHelpers::unique( explode( ',', str_replace( array( "\n\r", "\r\n", "\r", "\n" ), ',', $string ) ) );
	}

	/**
	 * 解析参数为sql关系和中文标签
	 *
	 * group_desc=测试&other_0={add_date:20160801-20160901}&other_1={city:杭州, last_order_source_desc:ios}
	 * 组成的数组
	 *
	 * @param array $receive
	 * @param boolean $add_date   add_date=true必须含时间区间
	 * @param string $table_prefix 表别名前缀,用于join条件
	 *
	 * @return mixed bool | ['sql_text' => 'sql..', 'tag_demo' => 'sql标签..']
	 *
	 * @wc
	 */
	public static function sql_text($receive = [], $add_date_must = false, $table_prefix = '')
	{
		$sql_text = $tag_demo = '';
		$add_date_container = [];
		$prefix = $table_prefix ? "{$table_prefix}." : "";

		foreach ($receive as $k => $v) {

			if (preg_match('/^(other_)+/', $k)) {
				$string = rtrim(ltrim($v, '{'), '}');
				$result = explode(',', $string);

				if ($result) {

					$in = '';
					$in_tag = '';

					// 或关系的数组
					foreach ($result as $kv) {
						list($column, $value) = explode(':', $kv);

						$column = str_replace(array('"',"'"), array('',''), $column);
						$value = str_replace(array('"',"'"), array('',''), $value);

						if (strpos($value, '-')) {

							list($start, $end) = explode('-', $value);

							// filter
							$add_date_container[] = $column;
							if ( $add_date_must && ($column == 'add_date') && (empty($start) || empty($end)) ) {
								return FALSE;
							}

							$in .= $in ? " OR ( {$prefix}{$column} BETWEEN '{$start}' AND '{$end}' ) " : " ( {$prefix}{$column}  BETWEEN '{$start}' AND '{$end}' ";
							$in_tag .= $in_tag ? " 或 ( {$prefix}{$column} 为 {$start} 到 {$end} ) " : " ( {$prefix}{$column} 为 {$start} 到 {$end} ";
						} else {

							$in .= $in ? " OR {$prefix}{$column}='{$value}'" : " ( {$prefix}{$column}='{$value}' ";
							$in_tag .= $in_tag ? " 或 {$prefix}{$column} 为 {$value} " : " ( {$prefix}{$column} 为 {$value}  ";
						}
					}
					$in .= " ) ";
					$in_tag .= " ) ";
				}

				$sql_text = $sql_text ? $sql_text . " AND " . $in : $in;
				$tag_demo = $tag_demo ? $tag_demo . " 且 " . $in_tag : $in_tag;
			}

		}

		if ($add_date_must && ! in_array('add_date', $add_date_container)) {
			return FALSE;
		}

		return [
			'sql_text' => $sql_text,
			'tag_demo' => $tag_demo,
		];
	}
}
