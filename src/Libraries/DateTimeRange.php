<?php

namespace Lib;

use Const\Regex;
use DateTime;
use Exceptions\DateTimeRangeException;

/**
 * 构建时间范围工具
 */
class DateTimeRange {

	/**
	 * 开始时间
	 * @var integer | string
	 */
	var $start;

	/**
	 * 结束时间
	 * @var integer | string
	 */
	var $end;

	/**
	 * 开始时间对象
	 * @var DateTime
	 */
	private $__startDateTime;

	/**
	 * 结束时间对象
	 * @var DateTime
	 */
	private $__endDateTime;

	/**
	 * __construct
	 *
	 * @param integer | string $start
	 * @param integer | string $end
	 */
	function __construct($start, $end) {
		if (is_numeric($start) && is_numeric($end)) {
			if ($start == $end) {
				$start = date(YMD . ' 00:00:00', $start);
				$end = date(YMD . '23:59:59', $end);
			} else {
				$start = date(YMDHIS, $start);
				$end = date(YMDHIS, $end);
			}

			$this->__startDateTime = new DateTime($start);
			$this->__endDateTime = new DateTime($end);
		} else {
			// 检查时间字符串格式
			if (1 === preg_match(Regex::YMD, $start)
				|| 1 === preg_match(Regex::YMD_LAX, $start)
				|| 1 === preg_match(Regex::YMDHIS, $start)) {
				$this->__startDateTime = new DateTime($start);
			} else {
				self::throwFormatError('[start ' . $start . ']');
			}

			// 结束没有具体时间时默认到当天最后一秒
			if (1 === preg_match(Regex::YMD, $end)
				|| 1 === preg_match(Regex::YMD_LAX, $end)) {
				$this->__endDateTime = new DateTime($end . ' 23:59:59');
			} else if (1 === preg_match(Regex::YMDHIS, $end)) {
				$this->__endDateTime = new DateTime($end);
			} else {
				self::throwFormatError('[end ' . $end . ']');
			}
		}

		$this->resetVar();

		if ($this->start > $this->end) {
			self::throwRangeError($start . ' ~ ' . $end);
		}
	}

	/**
	 * 创建DateTimeRange
	 * @author gjy
	 *
	 * @param  string $start
	 * @param  string $end
	 * @return DateTimeRange
	 */
	public static function create($start, $end) {
		return new self($start, $end);
	}

	/**
	 * 转换边界变量值
	 * @author gjy
	 *
	 * @param  string $format
	 * @return DateTimeRange
	 */
	public function trans($format = YMD) {
		$this->start = $this->__startDateTime->format($format);
		$this->end = $this->__endDateTime->format($format);

		return $this;
	}

	/**
	 * 重置边界变量 start end
	 * @author gjy
	 *
	 * @return DateTimeRange
	 */
	public function resetVar() {
		$this->start = $this->__startDateTime->getTimeStamp();
		$this->end = $this->__endDateTime->getTimeStamp();

		return $this;
	}

	/**
	 * 根据传入格式进行diff
	 * @author gjy
	 *
	 * @param  string $differenceFormat # 见文件最下方的注释: RESULT FORMAT
	 * @return string
	 */
	public function diff($differenceFormat) {
		$interval = date_diff($this->__startDateTime, $this->__endDateTime);
		return $interval->format($differenceFormat);
	}

// -------------------------- getters --------------------------
	public function getStartDateTime() {
		return $this->__startDateTime;
	}
	public function getEndDateTime() {
		return $this->__endDateTime;
	}
// -------------------------- getters --------------------------

// -------------------------- exception --------------------------
	private static function throwRangeError($param = '') {
		throw new DateTimeRangeException("Params [{$param}] ain't a valid range.");
	}
	private static function throwFormatError($param = '') {
		throw new DateTimeRangeException("{$param} can't parse from supported format.");
	}
// -------------------------- exception --------------------------

}

//////////////////////////////////////////////////////////////////////
//PARA: Date Should In YYYY-MM-DD Format
//RESULT FORMAT:
// '%y Year %m Month %d Day %h Hours %i Minute %s Seconds'  =>  1 Year 3 Month 14 Day 11 Hours 49 Minute 36 Seconds
// '%y Year %m Month %d Day'                                =>  1 Year 3 Month 14 Days
// '%m Month %d Day'                                        =>  3 Month 14 Day
// '%d Day %h Hours'                                        =>  14 Day 11 Hours
// '%d Day'                                                 =>  14 Days
// '%h Hours %i Minute %s Seconds'                          =>  11 Hours 49 Minute 36 Seconds
// '%i Minute %s Seconds'                                   =>  49 Minute 36 Seconds
// '%h Hours                                                =>  11 Hours
// '%a Days                                                 =>  468 Days
//////////////////////////////////////////////////////////////////////
