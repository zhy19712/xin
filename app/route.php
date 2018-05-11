<?php


//url美化 例：Route::rule('blog/:id','index/blog/read');
$url = \think\Db::name("admin_urlconfig")->where(['status' => 1])->column('aliases,url');
foreach ($url as $k => $val) {
	\think\Route::rule($k,$val);
}


return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];


