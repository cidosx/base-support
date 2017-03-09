# 用composer管理项目公共包的代码结构(demo)

[![as](https://img.shields.io/badge/laravel-v5.1-orange.svg)](https://github.com/laravel/laravel)

## 快速开始

#### 1. 用命令全局添加composer仓库

```
composer config -g repo.ginnerpeace vcs git@github.com:ginnerpeace/base-support.git
```

#### 2. 添加依赖

```
composer require ginnerpeace/base-support
```


## Document

...


## 项目结构

```
support
├── composer.json
├── src                         // namespace       备注
│   ├── Config                  // ----          配置
│   ├── Console                 // Console,      控制台程序
│   ├── Consts                  // Const,        常量定义
│   ├── Contracts               // Contract,     接口
│   ├── Enums                   // Enum,         枚举类
│   ├── Exceptions              // Exceptions,   异常
│   ├── Helpers                 // Helper,       辅助方法
│   ├── Libraries               // Lib,          类库
│   ├── Middlewares             // Middleware,   中间件
│   ├── Scripts                 // ----          脚本
│   ├── Services                // Services,     服务
│   ├── Template                // ----          模板文件
│   ├── Traits                  // Trait,        Traits
│   ├── consts.php              // ----          全局常量定义
│   └── helpers.php             // ----          全局辅助方法定义
└─ tests                        //                单元测试

```


## 开发中快速变更

- 可以在`vendor`目录下直接修改, 这样可以很快的增加一些错误信息、枚举、立即要用的辅助类、函数等等，本地开发环境就即时生效了。
- 改完可以用`git`提交
