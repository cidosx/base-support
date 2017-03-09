<?php

namespace Lib;

use Const;

/**
 * 文件检查
 */
class FileChecker {

	/**
	 * 获取配置项
	 * @author gjy
	 *
	 * @param  string $alias
	 * @return array | boolean false
	 */
	public static function getConf($alias = '') {
		if (empty($alias)) {
			return FALSE;
		}

		return load_config('fileupload.alias.' . $alias);
	}

	/**
	 * 根据配置中对应的type获取后缀名
	 * @author gjy
	 *
	 * @param  string $type
	 * @return string
	 */
	public static function getExt($type = '') {
		if (empty($type)) {
			return '';
		}

		return load_config('fileupload.type.' . $type . '.ext');
	}

	/**
	 * 验证文件
	 * @author gjy
	 *
	 * @param  string $alias
	 * @return array
	 */
	public static function checkFile($alias = '') {
		$alias = trim($alias);
		// 检查文件类型是否存在
		$conf = self::getConf($alias);
		if (empty($conf['path']) || empty($conf['verify'])) {
			return array('status' => FALSE, 'info' => Const\Error::FILE_CONF_ERR);
		}

		// 检查文件上传
		$file = @$_FILES[$alias];
		if (empty($file) || $file['error'] !== 0) {
			return array('status' => FALSE, 'info' => Const\Error::FILE_UPLOAD_ERR);
		}

		// 文件大小
		if ($file['size'] >= $conf['verify']['size']) {
			return array(
				'status' => FALSE,
				'info' => Const\Error::FILE_SIZE_OLIMIT . ($conf['verify']['size'] / 1024 / 1024) . 'M');
		}

		// 检查文件格式
		$file['type'] = strtolower($file['type']);
		if (!in_array($file['type'], $conf['verify']['type'])) {
			return array('status' => FALSE, 'info' => Const\Error::FILE_FORMAT_ERR);
		}

		/** 后面的检查逻辑仅针对图片 */
		if (0 !== strpos($file['type'], 'image')) {
			goto tag_for_file_already_checked;
		}

		// 获取图片信息
		list($width, $height) = getimagesize($file['tmp_name']);

		// 检查文件是否为正常图片
		if (empty($width) || empty($height)) {
			return array('status' => FALSE, 'info' => Const\Error::FILE_FORMAT_ERR);
		}

		/**
		 * 检查是否限定宽高
		 * 未限定时再判断是否有范围限制
		 */
		if (isset($conf['verify']['height'])) {
			if ($height != $conf['verify']['height']) {
				return array(
					'status' => FALSE,
					'info' => Const\Error::IMG_H_ERROR . " ({$conf['verify']['height']}px)",
				);
			}
		} else {
			if (isset($conf['verify']['max-height'])) {
				if ($height > $conf['verify']['max-height']) {
					return array(
						'status' => FALSE,
						'info' => Const\Error::IMG_H_OVER_LIMIT . " ({$conf['verify']['max-height']}px)",
					);
				}
			}

			if (isset($conf['verify']['min-height'])) {
				if ($height < $conf['verify']['min-height']) {
					return array(
						'status' => FALSE,
						'info' => Const\Error::IMG_H_UNDER_LIMIT . " ({$conf['verify']['min-height']}px)",
					);
				}
			}
		}

		if (isset($conf['verify']['width'])) {
			if ($width != $conf['verify']['width']) {
				return array(
					'status' => FALSE,
					'info' => Const\Error::IMG_W_ERROR . " ({$conf['verify']['width']}px)",
				);
			}
		} else {
			if (isset($conf['verify']['max-width'])) {
				if ($width > $conf['verify']['max-width']) {
					return array(
						'status' => FALSE,
						'info' => Const\Error::IMG_H_UNDER_LIMIT . " ({$conf['verify']['max-width']}px)",
					);
				}
			}

			if (isset($conf['verify']['min-width'])) {
				if ($width < $conf['verify']['min-width']) {
					return array(
						'status' => FALSE,
						'info' => Const\Error::IMG_H_UNDER_LIMIT . " ({$conf['verify']['min-width']}px)",
					);
				}
			}
		}
		/** 宽高检查完毕 */

		tag_for_file_already_checked:
		return array('status' => TRUE, 'data' => $file, 'path' => $conf['path']);
	}
}
