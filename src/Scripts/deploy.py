#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import os, sys, re

printColor = '\033[0;32m'

def show_help():
	print( '\033[1;33m' )
	print( '\n可用参数: (默认 --optimize)\n' )
	print( '-------------------首次部署--------------------' )
	print( '--install        安装composer包、生成项目key等等' )
	print( '\n-----------------常规发布--------------------' )
	print( '--optimize       更新配置缓存、更新路由缓存' )
	print( '--composer       更新composer' )
	print( '--fetch-routes   新增路由至数据库' )
	print( '--all            执行"常规发布"中的所有命令' )
	print( '\n-----------------其他功能--------------------' )
	print( '--clearcache             备份并清理程序缓存' )
	print( '--clearlog               备份并清理日志' )
	print( '--config-db=[alias1[,*]]      增加一条数据库配置: 输入 --config-db=test 生成DB_HOST_TEST=null等' )
	print( '--config-serv=[alias1[,*]]    增加一条service配置: 输入 --config-serv=test 生成 SERVICE_API_TEST=null' )
	print( '\n配置可使用逗号分割多条同时添加\n使用配置功能时, env文件中需要的两个标记:' )
	print( '#####TAG_FOR_DB_CONFIG#####' )
	print( '#####TAG_FOR_SERVICE_CONFIG#####' )
	print( '\033[0m' )
	sys.exit(0)

def execute( args = [] ):
	try:
		print( printColor + 'Beging...' )
		if 0 == args.__len__():
			# 无参数时仅运行优化
			cmdList = get_commands( '--optimize' )
			for cmd in cmdList:
				os.system( cmd )

			print_done()

		# 参数检查
		# 只要找到一条 --config- 前缀
		# 就表示正在增加配置
		for arg in args:
			if re.match( r'--config-(db|serv)=\w+', arg ):
				save_config( args )
				print_done()

		if '--all' in args:
			cmdList = get_commands( 'all' )
		elif '--install' in args:
			cmdList = get_commands( '--install' )
		else:
			cmdList = []

		# --config-*, --all, --install
		# 以上需单独运行

		clearList = []

		# 如果没有执行预定的命令, 则判断是否有单独的
		if 0 == cmdList.__len__():
			# composer必须是优先执行的
			if '--composer' in args:
				args.remove( '--composer' )
				args.insert( 0, '--composer' )

			# 路由更新需要放在最后
			if '--fetch-routes' in args:
				args.remove( '--fetch-routes' )
				args.append( '--fetch-routes' )

			# 检查是否有其他操作的参数
			for arg in args:
				if re.match( '--clear', arg ):
					clearList.append( arg )
				else:
					cmdList += get_commands( arg )

		if 0 == cmdList.__len__() and 0 == clearList.__len__():
			show_help()

		for cmd in cmdList:
			os.system( cmd )

		# 清理动作在最后执行
		clear_file( clearList )

		print_done()
	except Exception as e:
		print( '\033[0;31mError!!!' )
		print( Exception )
		print( e )
		print( '\033[0m' )
	finally:
		sys.exit(0)


def get_commands( option ):
	if 'all' == option:
		cmdList = get_commands( '--composer' ) \
		+ get_commands( '--optimize' ) \
		+ get_commands( '--fetch-routes' )

		return cmdList
	elif '--install' == option:
		return [
			'composer install --no-scripts --no-dev',
			'php artisan optimize --force',
			'php artisan key:generate',
		] + get_commands( '--optimize' ) \
		+ get_commands( '--fetch-routes' )

	elif '--optimize' == option:
		return [
			'php artisan cache:clear',
			'php artisan config:cache',
			'php artisan route:cache',
		]
	elif '--fetch-routes' == option:
		return [
			'php artisan route:fetch-update --force'
		]
	elif '--composer' == option:
		return [
			'composer update --no-scripts --no-dev',
			'php artisan optimize --force',
		]
	elif '--dump-autoload' == option:
		return [
			'composer dump-autoload -o',
		]
	else:
		print_error( '未知参数: ' + option )
		return []


def clear_file( clearList ):
	if 0 == clearList.__len__():
		return True

	import tarfile, time

	for cate in clearList:
		if '--clearcache' == cate:
			fileDir = './storage/framework/cache'
			cleartype = '缓存'
		elif '--clearlog' == cate:
			fileDir = './storage/logs'
			cleartype = '日志'
		else:
			print_error( '未知参数: ' + cate )
			return False

		fileList = []
		dirList = []
		# 创建压缩包
		for root, dirs, files in os.walk( fileDir ):
			# 打包文件 遍历文件夹
			# 并加入到待删除列表
			# 会跳过隐藏文件(夹)
			for file in files:
				if re.match( r'\..*', file ):
					continue

				fileList.append( os.path.join( root, file ) )

			for file in dirs:
				if re.match( r'\..*', file ):
					continue

				dirList.append( os.path.join( root, file ) )

		if 0 == fileList.__len__() and 0 == dirList.__len__():
			print_notice( '未发现' + cleartype + '文件.' )
			return

		print( '开始清理' + cleartype )

		if 0 < fileList.__len__():
			# tar -zcvf
			tarFileName = fileDir + '/.backup-' + str( time.time() ) + '.tar.gz'
			tar = tarfile.open( tarFileName, 'w:gz' )
			for file in fileList:
				print( '打包备份: ' + file + ' -> ' + tarFileName )
				tar.add( file )

			tar.close()

			# 开始清理
			for file in fileList:
				print( '删除文件: ' + file )
				if os.path.isfile( file ):
					try:
						os.remove( file )
					except:
						print_error( '文件未正常删除 -> ' + file )

		if 0 < dirList.__len__():
			for file in dirList:
				print( '删除文件夹: ' + file )
				if os.path.isdir( file ):
					try:
						os.rmdir( file )
					except:
						print_error( '文件夹未正常删除 -> ' + file )

		print( '备份并清理了 ' + fileDir + '/*' )
	return True


def save_config( args ):
	envfile = './.env'

	if not os.path.isfile( envfile ):
		print_error( '.env文件不存在' )
		return False

	with open( envfile, 'r' ) as fr:
		content = fr.read()

	count = 0

	for i in args:
		arg = i.split( '--config-' )
		if 2 != arg.__len__():
			print_notice( '此参数不进行配置动作: ' + i )
			continue

		val = arg[1].split( '=' )
		if ( 2 != val.__len__() ):
			continue

		if 'db' == val[0]:
			confItems = val[1].split( ',' )

			for item in confItems:
				if 1 > item.__len__():
					continue

				alias = item.upper()
				if not re.search( '#####TAG_FOR_DB_CONFIG#####', content ):
					print_error( '未找到DB配置标记位: #####TAG_FOR_DB_CONFIG#####' )
					break

				if not re.search( 'DB_HOST_' + alias, content ):
					# 执行配置添加
					content = content.replace(
						'#####TAG_FOR_DB_CONFIG#####',
						'DB_HOST_' + alias + '=null' \
						+ '\nDB_DATABASE_' + alias + '=null' \
						+ '\nDB_USERNAME_' + alias + '=null' \
						+ '\nDB_PASSWORD_' + alias + '=null' \
						+ '\nDB_PORT_' + alias + '=3306' \
						+ '\n\n#####TAG_FOR_DB_CONFIG#####'
					)
					count += 1
					print( '新增DB配置: ' + alias )

		elif 'serv' == val[0]:
			confItems = val[1].split( ',' )

			for item in confItems:
				if 1 > item.__len__():
					continue

				alias = 'SERVICE_API_' + item.upper()
				if not re.search( '#####TAG_FOR_SERVICE_CONFIG#####', content ):
					print_error( '未找到service标记位: #####TAG_FOR_SERVICE_CONFIG#####' )
					break

				if not re.search( alias, content ):
					# 执行配置添加
					content = content.replace(
						'#####TAG_FOR_SERVICE_CONFIG#####',
						alias + '=null' \
						+ '\n\n#####TAG_FOR_SERVICE_CONFIG#####'
					)
					count += 1
					print( '新增service配置: ' + alias )
		else:
			pass

	if ( 0 < count ):
		# 写入配置
		with open( envfile, 'w' ) as fw:
			fw.write( content )

		print( '将配置写入文件...' )
	else:
		print_notice( '未加入新的配置项' )

	return True


def print_done():
	print( printColor + 'Done.\033[0m' )
	sys.exit(0)


def print_error( text = '' ):
	print( '\033[0;31m' + text + printColor )


def print_notice( text = '' ):
	print( '\033[0;33m' + text + printColor )


if __name__ == '__main__':
	args = sys.argv

	if '--help' in args:
		show_help()
	else:
		args.pop(0)
		execute( args )
