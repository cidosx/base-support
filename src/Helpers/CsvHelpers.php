<?php

namespace Helper;

use RuntimeException;

/**
 * 处理csv的辅助函数
 */
class CsvHelpers {

	/** end of line */
	CONST EOL = "\r\n";

	/** 字符定界 */
	CONST ENCLOSURE = '"';

	/** 默认逗号分隔符 */
	CONST DELIMITER = ',';

	/** tab分隔符 */
	CONST DELIMITER_TAB = '	';

	/**
	 * 保存为csv文件
	 * @author gjy
	 *
	 * @param  string $filePathName
	 * @param  array $data
	 * @return boolean
	 */
	public static function store($filePathName, array $data, $overwrite = FALSE) {

		if (strrchr($filePathName, '.csv') !== '.csv') {
			throw new RuntimeException('The file extension must be .csv');
		}

		if (is_file($filePathName) && !$overwrite) {
			throw new RuntimeException('File already exists.');
		}

		if (empty($data['rows'])) {
			throw new RuntimeException('Missing required data for rows.');
		}

		$fp = fopen($filePathName, 'w');

		// 添加bom头 EFBBBF 为utf8 bom
		fwrite($fp, pack('H*', 'EFBBBF'));

		// 自定义一个数组header, 与cell分开处理
		if (!empty($data['header'])) {
			fwrite($fp, self::ENCLOSURE
				. join(self::ENCLOSURE . self::DELIMITER . self::ENCLOSURE, $data['header'])
				. self::ENCLOSURE
				. self::EOL);
		}

		// cell中需要判断是否为长数字
		foreach ($data['rows'] as $row) {
			if (is_array($row)) {
				fwrite($fp, rtrim(self::each_row_cell($row), self::DELIMITER) . self::EOL);
			} else {
				fwrite($fp, $row . self::EOL);
			}
		}

		fclose($fp);
		return realpath($filePathName);
	}

	/**
	 * 下载文件
	 * @author gjy
	 *
	 * @param  string $filename
	 * @param  array $data
	 * @return void
	 */
	public static function download($filename, array $data) {
		// 类似的逻辑, 但结果变为输出到浏览器

		if (empty($data['rows'])) {
			throw new RuntimeException('Missing required data for rows.');
		}

		// 自动加上文件后缀
		$filename = trim($filename);
		if (strrchr($filename, '.csv') !== '.csv') {
			$filename .= '.csv';
		}

		ob_end_clean();
		ob_start();

		header('Content-Type:text/csv;charset=utf8');
		header('Content-Disposition:attachment;filename=' . $filename);
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:Mon, 26 Jul 1997 05:00:00 GMT');
		header('Pragma:public');

		// 添加bom头 EFBBBF 为utf8 bom
		echo pack('H*', 'EFBBBF');

		// 自定义一个数组header, 与cell分开处理
		if (!empty($data['header'])) {
			echo self::ENCLOSURE
			. join(self::ENCLOSURE . self::DELIMITER . self::ENCLOSURE, $data['header'])
			. self::ENCLOSURE
			. self::EOL;
		}

		// cell中需要判断是否为长数字
		foreach ($data['rows'] as $row) {
			if (is_array($row)) {
				echo rtrim(self::each_row_cell($row), self::DELIMITER) . self::EOL;
			} else {
				echo $row . self::EOL;
			}
		}

		// 输出缓冲区的内容到浏览器
		ob_end_flush();
		exit;
	}

	/**
	 * 格式化每行的数据
	 * @author gjy
	 *
	 * @param  array $rowData
	 * @return string
	 */
	private static function each_row_cell(array $rowData) {
		$contents = '';

		foreach ($rowData as $cell) {
			if (is_numeric($cell)) {
				// 增加一个tab防止数字被转换成科学计数
				$contents .= self::ENCLOSURE . $cell . self::DELIMITER_TAB . self::ENCLOSURE . self::DELIMITER;
			} else {
				// 转义内容中的引号
				$contents .= self::ENCLOSURE .
				str_replace(
					self::ENCLOSURE,
					self::ENCLOSURE . self::ENCLOSURE,
					$cell
				) . self::ENCLOSURE . self::DELIMITER;
			}
		}

		return $contents;
	}
}
