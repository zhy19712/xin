<?php
// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');
// 定义版本号
define('XIN_VERSION', '0.0.1');
//重定义扩展类库目录
define('EXTEND_PATH', __DIR__ . '/../extend/');
//重定义第三方类库目录
define('VENDOR_PATH', __DIR__ . '/../vendor/');
//
define('SITE_URL', 'http://127.0.0.1/tp5');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';