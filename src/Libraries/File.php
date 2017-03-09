<?php

namespace Lib;

use Helper\CsvHelpers;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 文件类
 */
class File {

	/**
	 * 导出文件
	 * @param  string $filename
	 * @param  string $sheetname
	 * @param  array $data
	 * @return void
	 */
	public static function export($filename, $sheetname, array $data, $type = 'csv') {

		// csv文件不推荐使用第三方包导出, 文件兼容性和格式有问题
		if ($type === 'csv') {
			CsvHelpers::download($filename, [
				'rows' => $data
			]);
			return;
		}

		Excel::create($filename, function ($excel) use ($data, $sheetname) {
			// 5w条数据分sheet
			foreach (array_chunk($data, 50000) as $k => $v) {
				$excel->sheet($sheetname . ($k + 1), function ($sheet) use ($v) {
					$sheet->rows($v, TRUE);
				});
			}
			unset($data);
		})->export($type);
	}
}
