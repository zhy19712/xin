<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/30
 * Time: 17:43
 */
/**
 * 极光推送
 * Class JpushModel
 * @package app\admin\model;
 */
namespace app\admin\model;
use think\Model;
use think\exception\PDOException;
require '../../../extend/jpush/autoload.php';

use JPush\Client as JPush;

class JpushModel extends Model
{
    //极光推送appkey
    static public function app_key(){

        $app_key = "极光账号的app_key";
        return $app_key;
    }
    //极光推送master_secret
    static public function master_secret(){

        $master_secret = "极光账号的master_secret";
        return $master_secret;
    }
    //获取alias和tags
    public function getDevices($registrationID){

        require_once '../../../extend/jpush/autoload.php';

        $app_key = $this->app_key();
        $master_secret = $this->master_secret();

        $client = new JPush($app_key, $master_secret);

        $result = $client->device()->getDevices($registrationID);

        return $result;

    }
    //添加tags
    public function addTags($registrationID,$tags){

        require_once '../../../extend/jpush/autoload.php';

        $app_key = $this->app_key();
        $master_secret = $this->master_secret();

        $client = new JPush($app_key, $master_secret);

        $result = $client->device()->addTags($registrationID,$tags);

        return $result;

    }

    //移除tags
    public function removeTags($registrationID,$tags){

        require_once '../../../extend/jpush/autoload.php';

        $app_key = $this->app_key();
        $master_secret = $this->master_secret();

        $client = new JPush($app_key, $master_secret);

        $result = $client->device()->removeTags($registrationID,$tags);

        return $result;

    }
    //标签推送
    public function push($tag,$alert){

        require_once '../../../extend/jpush/autoload.php';

        $app_key = $this->app_key();
        $master_secret = $this->master_secret();

        $client = new JPush($app_key, $master_secret);

        $tags = implode(",",$tag);

        $client->push()
            ->setPlatform(array('ios', 'android'))
            ->addTag($tags)                          //标签
            ->setNotificationAlert($alert)           //内容
            ->send();

    }

    //别名推送
    public function push_a($alias,$alert){

        require_once '../../../extend/jpush/autoload.php';

        $app_key = $this->app_key();
        $master_secret = $this->master_secret();

        $client = new JPush($app_key, $master_secret);

        $alias = implode(",",$alias);

        $client->push()
            ->setPlatform(array('ios', 'android'))
            ->addAlias($alias)                      //别名
            ->setNotificationAlert($alert)          //内容
            ->send();
    }
}