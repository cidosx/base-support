<?php

namespace Lib;

use Const;

/**
 * 图片上传类
 * @author gjy
 *
 */
class UploadImage {

	/**
	 * 随机文件名
	 * @author gjy
	 *
	 * @param string $dir
	 * @return string
	 */
	public static function getRandName() {
		return md5(uniqid(mt_rand()));
	}

	/**
	 * 上传
	 * @author gjy
	 *
	 * @param  string $alias
	 * @return array # status 为 bool是验证时返回, 为0\1是上传时返回
	 */
	public static function doUpload($alias = '', $subFolder = '') {
		$checkResult = FileChecker::checkFile($alias);
		if (TRUE !== $checkResult['status']) {
			return $checkResult;
		}

		// 可以开始上传了
		$subFolder = trim($subFolder);
		$filename = '/' . $checkResult['path']
		. '/' . (empty($subFolder) ? date('Ymd') : $subFolder) . '/'
		. self::getRandName() . FileChecker::getExt($checkResult['data']['type']);

		// 获取配置节操作员
		$operator = config('filesystems.disks.upyun.username');
		// 获取配置节密码
		$bucketPassword = config('filesystems.disks.upyun.password');
		// 获取配置节bucketname
		$bucketName = config('filesystems.disks.upyun.bucketname');
		// 获取图片cdn
		$imageCDN = config('filesystems.disks.upyun.image-cdn');

		$upyun = new UpYun($bucketName, $operator, $bucketPassword);

		$fp = fopen($checkResult['data']['tmp_name'], 'r');
		$uploadResult = $upyun->writeFile($filename, $fp, TRUE);
		fclose($fp);

		if (empty($uploadResult)) {
			return [
				'status' => 0,
				'info' => Const\Error::FILE_SAVE_ERR,
			];
		} else {
			return [
				'status' => 1,
				'image' => [
					'full_img_url' => $imageCDN . $filename,
					'imgInfo' => [
						'height' => $uploadResult['x-upyun-height'],
						'width' => $uploadResult['x-upyun-width'],
						'type' => $uploadResult['x-upyun-file-type'],
					],
				],
			];
		}
	}
}
