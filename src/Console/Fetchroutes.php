<?php

namespace Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * 遍历新增接口(路由)并写入数据库
 * @author gjy <ginnerpeace@live.com>
 */
class Fetchroutes extends Command {

	/**
	 * routes data handle
	 * @var array
	 */
	protected $routes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'route:fetch-update { --table= }  { --connection= } { --use } { --force }';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Fetch routes and insert into database.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Router $router) {
		parent::__construct();
		$this->routes = $router->getRoutes();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
		if ($this->option('use')) {
			$this->info(PHP_EOL . '可选参数:');
			$this->comment('--table=[要写入的表名称]' . PHP_EOL
				. '--connection=[项目预先配置好的连接名]' . PHP_EOL);
			exit;
		}

		$notForce = !$this->option('force');
		$tableName = $this->option('table');

		if (empty($tableName)) {
			if ($notForce && !$this->confirm("确认继续吗? 没有输入 --table 参数时, 将向默认数据库中的 erp_module 表添加数据.")) {
				$this->comment('取消操作');
				exit;
			}

			$tableName = 'module';
		}

		$conn = $this->option('connection');
		if (empty($conn)) {
			$conn = 'mysql';
		}

		$dbConfig = Config::get('database.connections.' . $conn);
		// 优先判断是否有读写分离的配置
		if (isset($dbConfig['write']['database'])) {
			$db = $dbConfig['write']['database'];
		} else if (isset($dbConfig['database'])) {
			$db = $dbConfig['database'];
		}

		if (empty($db)) {
			$this->comment('Fetch route failed - The configuration for this connection was not found: ' . $conn);
			exit;
		}

		// 没有配置prefix的项目自动加上表前缀
		$tableName = (empty($dbConfig['prefix']) ? 'erp_' : $dbConfig['prefix']) . $tableName;

		$dbHandle = DB::connection($conn);

		$dbHandle->setFetchMode(\PDO::FETCH_ASSOC);

		$query = $dbHandle->select("SELECT COUNT(`TABLE_NAME`) AS exist
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = '$db'
			AND TABLE_NAME = '$tableName'");

		if (empty(reset($query)['exist'])) {
			$this->comment("Fetch route failed - Database/Table doesn't exist: {$db}.{$tableName}" . PHP_EOL);
			exit;
		}

		$routeArr = $this->getRoutes();
		$total = 0;
		$data = [];
		foreach ($routeArr as $val) {
			$query = $dbHandle->table($tableName)
				->where('method', $val['method'])
				->where('path', $val['path'])
				->get();

			if (empty($query)) {
				$this->line('New route: ' . $val['method'] . ' | ' . $val['path'] . '  (' . $val['name'] . ')');
				$data[] = $val;
				$total++;
			}
		}

		if ($total < 1) {
			$this->info('Fetch route completed: The current routes were latest.');
			exit;
		}

		if ($notForce && !$this->confirm("发现( {$total} )条新路由, 请确认 $tableName 表中有字段: name, path, method, status")) {
			$this->comment('取消操作');
			exit;
		}

		$dbHandle->beginTransaction();
		$query = $dbHandle->table($tableName)->insert($data);
		if (empty($query)) {
			$dbHandle->rollBack();
			$this->info('Fetch route failed - SQL execute failed. :-(');
		} else {
			$dbHandle->commit();
			$this->info('Fetch route success - All SQL has been executed. ;-)');
		}
	}

	protected function getRouteInformation($route) {
		$routeAction = $route->getAction();
		if (isset($routeAction['title'])) {
			$routeName = $routeAction['title'];
		} else {
			$routeName = $route->getName();
		}

		$routeUri = $route->uri();

		/**
		 * 由类型衍生出的子路由
		 * @var array
		 */
		$subRouteUri = [];

		if (isset($routeAction['match_replace'])) {
			foreach ($routeAction['match_replace'] as $replaceType => $arrMatch) {
				// 正则替换url
				if (preg_match_all('/{' . $replaceType . '(.*)}/isU', $routeUri, $matches)) {
					if (isset($matches[1][0]) && isset($arrMatch[$matches[1][0]])) {
						foreach ($arrMatch[$matches[1][0]] as $keyword) {
							if (empty($subRouteUri[$keyword])) {
								$subRouteUri[$keyword] = [];
							}

							$subRouteUri[$keyword]['path'] = str_replace(
								'{' . $replaceType . $matches[1][0] . '}',
								$keyword,
								$routeUri
							);
						}
					}
				}

				// 替换title
				if (preg_match_all('/{' . $replaceType . '(.*)}/isU', $routeName, $matches)) {
					if (isset($matches[1][0]) && isset($arrMatch[$matches[1][0]])) {
						foreach ($arrMatch[$matches[1][0]] as $keyword => $val) {
							if (empty($subRouteUri[$keyword])) {
								// 没有多个path的时候忽略
								continue;
							}

							$subRouteUri[$keyword]['name'] = str_replace(
								'{' . $replaceType . $matches[1][0] . '}',
								$val,
								$routeName
							);
						}
					}
				}
			}
		}

		/**
		 * 直接用于添加数据库的route信息
		 * @var array
		 */
		$routeInfo = [];

		$routePath = $this->_parseUri($routeUri);
		$methods = $route->methods();
		foreach ($methods as $method) {
			$method = strtoupper($method);
			if (empty($method) || $method === 'HEAD') {
				continue;
			}

			if (empty($subRouteUri)) {
				$routeInfo[] = [
					'name' => $routeName,
					'path' => $routePath,
					'method' => $method,
					'status' => 1,
				];
			}
			else {
				foreach ($subRouteUri as $value) {
					$routeInfo[] = [
						'name' => isset($value['name']) ? $value['name'] : $routeName,
						'path' => $this->_parseUri($value['path']),
						'method' => $method,
						'status' => 1,
					];
				}
			}
		}

		return $routeInfo;
	}

	/**
	 * 对uri解析并转换为分发层规则名称
	 * @author gjy
	 *
	 * @param  string $uri
	 * @return string
	 */
	private function _parseUri($uri) {
		return '/' . ltrim(
			preg_replace(
				["/\/{_[a-zA-Z]+\w+\?}/", "/{_[a-zA-Z]+\w+}/", "/\/{[a-zA-Z]+\w+\?}/", "/{[a-zA-Z]+\w+}/"],
				['(/[\\w-]+)?', '[\\w-]+', '(/\\d+)?', '\\d+'],
				$uri
			), '/'
		);
	}

	protected function getRoutes() {
		$results = [];
		foreach ($this->routes as $route) {
			$results = array_merge($results, $this->getRouteInformation($route));
		}
		return array_filter($results);
	}

}
