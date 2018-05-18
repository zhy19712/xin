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
        //将所有模型页面按需装载到同一个iframe中，防止用户打开过多模型页面崩溃
        //全景3d、质量3d、进度3d、安全3d、全景模型、倾斜模型
        if(request()->isAjax()) {
            $id = $this->request->param('id');
            switch ($id) {
                case 'panorama':
                    $path = '../app/modelmanagement/view/index/panorama.html';
                    break;
                case 'quality':
                    $path = '../app/modelmanagement/view/index/quality.html';
                    break;
                case 'progress':
                    $path = '../app/modelmanagement/view/index/progress.html';
                    break;
                case 'safety':
                    $path = '../app/modelmanagement/view/index/safety.html';
                    break;
                case 'oblique':
                    $path = '../app/modelmanagement/view/index/oblique.html';
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