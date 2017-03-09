<?php

namespace Lib;

use Const;
use Helper\StringHelpers as Str;

/**
 * 文件上传类
 * @author xjx
 *
 */
class UploadFile {

	/**
	 * 上传uid csv文件
	 * @author xjx
	 *
	 * @param  $alias 文件的name
	 * @return json object
	 */
	public static function uploadIDFile($alias = '') {
		elk_log('正在上传id文件, 检查文件中...');
		// 检查文件格式以及大小
		$check = FileChecker::checkFile($alias);
		if (!$check['status']) {
			$errMsg = isset($check['info']) ? $check['info'] : Const\Error::FILE_UPLOAD_ERR;
			elk_log('文件检查失败. msg -> ' . $errMsg);
			return $check;
		}

		elk_log('(' . $alias . ')id文件上传成功. ' . json_encode($check['data'], JSON_UNESCAPED_UNICODE));

		// 通过检查时 data 为文件
		// 内容按每行一个获取，保存至缓存中
		$arrID = Str::row_explode(file_get_contents($check['data']['tmp_name']));

		if (empty($arrID)) {
			elk_log('文件为空.');
			return [
				'status' => FALSE,
				'info' => Const\Error::NO_DATA,
			];
		} else {
			elk_log('文件内容获取成功.');
			return [
				'status' => TRUE,
				'data' => $arrID,
			];
		}
	}
}
