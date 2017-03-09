<?php

namespace Helper;

use DateTime;
class DateHelpers {

	/**
	 * 传入时间, 计算月份差值 [2016-03-03 | 2016-3-3 | 2016-3-03][xx:xx:xx]
	 * TODO: 其实没算进去日期
	 * @author gjy
	 *
	 * @param  string $date
	 * @param  string $date1
	 * @return integer
	 */
	public static function date_diff_month($date = '', $date1 = '') {
		if (empty($date) || empty($date1)) {
			return -1;
		}

		// 这里不再判断格式了, pls传正确的值进来

		return abs(abs(date('Y', strtotime($date)) - date('Y', strtotime($date1))) * 12 - (date('m', strtotime($date)) - date('m', strtotime($date1))));
	}

	/**
	 * 传入时间, 计算天数差值
	 * @author gjy
	 *
	 * @param  string $date
	 * @param  string $date1
	 * @return integer
	 */
	public static function date_diff_days($date = '', $date1 = '') {
		if (empty($date) || empty($date1)) {
			return -1;
		}

		return abs(strtotime($date) - strtotime($date1)) / 86400;
	}

	/**
	 * 当前时间毫秒级时间戳
	 * @author gjy
	 *
	 * @param integer $ex #表示扩展几位数的时间戳
	 * @return string
	 */
	public static function full_timestamp($ex = 3) {
		if ($ex > 3) {
			$ex = 3;
		} elseif ($ex < 1) {
			return time();
		}

		return number_format(microtime(true), $ex, '', '');
	}

	/**
	 * 每月有几天
	 * @author gjy
	 *
	 * @param  integer $month
	 * @param  string $year
	 * @return integer
	 */
	public static function days_in_month($month = 0, $year = '') {
		if ($month < 1 || $month > 12) {
			return 0;
		} elseif (!is_numeric($year) || strlen($year) !== 4) {
			$year = date('Y');
		}

		if (defined('CAL_GREGORIAN')) {
			return cal_days_in_month(CAL_GREGORIAN, $month, $year);
		}

		if ($year >= 1970) {
			return (int) date('t', mktime(12, 0, 0, $month, 1, $year));
		}

		if ($month == 2 && ($year % 400 === 0 || ($year % 4 === 0 && $year % 100 !== 0))) {
			return 29;
		}

		$days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		return $days_in_month[$month - 1];
	}
}
