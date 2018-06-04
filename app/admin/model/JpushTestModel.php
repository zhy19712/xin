<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/1
 * Time: 15:38
 */
/**
 * 极光推送
 * Class JpushModel
 * @package app\admin\model;
 */
namespace app\admin\model;
use think\Model;
use think\exception\PDOException;
use think\Loader;

vendor('JPush.autoload');

use JPush\Client as JPush;

class JpushTestModel extends Model
{
    //极光推送appkey
    static public function app_key(){

        $app_key = config("Jpush.app_key");
        return $app_key;
    }
    //极光推送master_secret
    static public function master_secret(){

        $master_secret = config("Jpush.master_secret");
        return $master_secret;
    }
    /**
     * 将数据先转换成json,然后转成array
     */
    public function json_array($result){
        $result_json = json_encode($result);
        return json_decode($result_json,true);
    }

    /**
     * 向所有设备推送消息
     * @param string $message 需要推送的消息
     */
    public function sendNotifyAll($message){
        vendor('JPush.autoload');
        $app_key = $this->app_key();
        $master_secret = $this->master_secret();  //填入你的master_secret
        $client = new JPush($app_key, $master_secret);
        $result = $client->push()->setPlatform('all')->addAllAudience()->setNotificationAlert($message)->send();
        return $this->json_array($result);
    }


    /**
     * 向特定设备推送消息
     * @param array $regid 特定设备的设备标识
     * @param string $message 需要推送的消息
     */
    public function sendNotifySpecial($regid,$message){
        vendor('JPush.autoload');
        $app_key = $this->app_key();
        $master_secret = $this->master_secret();   //填入你的master_secret
        $client = new JPush($app_key, $master_secret);
        $result = $client->push()->setPlatform('all')->addRegistrationId($regid)->setNotificationAlert($message)->send();
        return $this->json_array($result);
    }

    /**
     * 向指定设备推送自定义消息
     * @param string $message 发送消息内容
     * @param array $regid 特定设备的id
     * @param int $did 状态值1
     * @param int $mid 状态值2
     */

    public function sendSpecialMsg($regid,$message,$did,$mid){
        vendor('JPush.autoload');
        $app_key = $this->app_key();
        $master_secret = $this->master_secret();   //填入你的master_secret
        $client = new JPush($app_key, $master_secret);
        $result = $client->push()->setPlatform('all')->addRegistrationId($regid)
            ->addAndroidNotification($message,'',1,array('did'=>$did,'mid'=>$mid))
            ->addIosNotification($message,'','+1',true,'',array('did'=>$did,'mid'=>$mid))->send();
        return $this->json_array($result);
    }

    /**
     * 得到各类统计数据
     * @param array $msgIds 推送消息返回的msg_id列表
     */
    public function reportNotify($msgIds){
        vendor('JPush.autoload');
        $app_key = $this->app_key();
        $master_secret = $this->master_secret();   //填入你的master_secret
        $client = new JPush($app_key, $master_secret);
        $response = $client->report()->getReceived($msgIds);
        return $this->json_array($response);
    }
}