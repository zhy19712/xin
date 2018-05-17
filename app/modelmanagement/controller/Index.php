<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/17
 * Time: 10:29
 */
namespace app\modelmanagement\controller;

use app\admin\controller\Permissions;


class Index extends Permissions
{
    function Index()
    {
        if(request()->isAjax()) {
            $id = $this->request->param('id');
            switch ($id) {
                case 'panorama':
                    $path = '../app/modelmanagement/view/index/panorama3d.html';
                    break;
                case '3d':
                    $path = '../app/modelmanagement/view/index/quality3d.html';
                    break;
                case 'index':
                    $path = '../app/modelmanagement/view/index/index.html';
                    break;
                default :
                    $path = '../app/modelmanagement/view/index/index.html';
            }
            $newContent = file_get_contents($path);
            return $newContent;
        }
        return $this->fetch();
    }

}