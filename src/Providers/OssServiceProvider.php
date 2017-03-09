<?php

namespace Providers;

use Illuminate\Support\ServiceProvider;
use OSS\OssClient;

class OssServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->publishes([
			__DIR__ . '/../Config/aliyunoss.php' => config_path('aliyunoss.php'),
		], 'config');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton('oss', function ($app) {
			$config = config('aliyunoss');
			$accessKeyId = $config['accesskey_id'];
			$accessKeySecret = $config['accesskey_secret'];

			if ($config['internal_upload']) {
				$endpoint = $config['endpoint_internal'];
			} else {
				$endpoint = $config['endpoint'];
			}

			return new OssClient($accessKeyId, $accessKeySecret, $endpoint);
		});
	}

	/**
	 * 获取提供者所提供的服务。
	 *
	 * @return array
	 */
	public function provides() {
		return ['oss'];
	}
}
