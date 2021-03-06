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
        $id = $this->request->param('id');
            switch ($id) {
                case 'panorama':
                    return $this->redirect('modelmanagement/panorama/index');
                case 'manage':
                    return $this->redirect('modelmanagement/manage/index');
                case 'progress':
                    return $this->redirect('modelmanagement/progress/index');
                case 'safety':
                    return $this->redirect('modelmanagement/safety/index');
                case 'oblique':
                    return $this->redirect('modelmanagement/oblique/index');
                case 'index':
                    return $this->redirect('modelmanagement/index/index');
                default :
                    return $this->fetch();
            }
    }

}