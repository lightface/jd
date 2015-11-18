<?php

namespace app\controllers;

use my\QW;
use Yii;
use yii\web\Controller;
use app\models\Good;

use my\Curl;

class GoodController extends Controller
{
    public $layout = 'good';
    public $good_m;
    public $end =0;
    public function init() {
        header("Content-type:text/html;charset=utf-8");
        $this->good_m = new Good();
    }

    function flush (){
        @ob_flush();
        @flush();
    }

    //阻塞模式
    public function actionGrab()
    {
        set_time_limit(0);
        header("Content-type:text/html;charset=utf-8");
        $curl = new Curl();
        $num = ! empty($_GET['num']) ? $_GET['num'] : 200000;
        $start = $this->good_m->start_id();
        $done = 0;
        $starttime = microtime(true);
        $i = $start;
        echo(str_repeat(' ',300));
        $this->flush();

        do{
            $url = 'http://item.m.jd.com/product/'.$i.'.html';
            $good = $curl->catch_one($url);
            if($good !== false){
                $goods[$i] = $good;
                if(count($goods)>5){
                    $this -> good_m -> insert_goods($goods);
                    unset($goods);
                }
                echo 'id:'.$i.'  '.date('H:i:s',time()).'  '.'产品：'.$good['name'].'   '.'价格：'.$good['price'].'<br/>';
                $this->flush();
                unset($good);
                $done++;
            }
            $i++;
        } while($i<$start+$num);
        $this->end = $start+$num;

//        echo '<pre/>';
        echo $num.'<br/>';
        echo '<hr/><br/>'.'抓了：'.$done.'个，总共耗时：'.(microtime(true)-$starttime).'秒<br/>';
//        print_r($goods);
//        echo QW::date_friendly(time()-60);
    }

    //并发模式
    public function actionGrabMut()
    {
        set_time_limit(0);
        $curl = new Curl();
        $num = isset($_POST['num']) ? intval($_POST['num']) : 100;
        $start = isset($_POST['start']) ? intval($_POST['start'])  : $this->good_m->start_id();
        $starttime = microtime(true);
//        $start = 754883;

        $url_arr = [];
        for($i=0;$i<$num;$i++){
            $url_arr[$start+$i] = 'http://item.m.jd.com/product/'.($start+$i).'.html';
        }

        $result = $curl->catch_multi($url_arr);
        $this -> good_m -> insert_goods($result);
        //模板参数设置
        $view = $this->getView();
        $view->title = '京东商品列表';
        $view->params = [
            'refresh' => 5,
        ];
        //
        $goods = $this -> good_m -> get_good_list();
        return $this->render('list1',array(
            'goods' => $goods['goods'],
            'total' => $goods['total'],
        ));

        /*echo date('Y-m-d H:i:s').': 此次抓取第'.$start.'至'.($start+$num).'条,共'.$num.'条';
        echo '抓了：<span style="color:red">'.count($result).'</span>个，此次总共耗时：<span style="color:red">'.(microtime(true)-$starttime).'</span>秒<br/>'.'<hr/><br/>';
        $this->flush();*/
    }

    public function actionAuto(){
//        ignore_user_abort();
//即使Client断开(如关掉浏览器)，PHP脚本也可以继续执行.
       /* set_time_limit(0);
//执行时间为无限制，php默认的执行时间是30秒，通过set_time_limit(0)可以让程序无限制的执行下去
        $interval=2;
        $num = 100;//每次采集
        $start = $this->good_m->start_id();
        while(1){
            $this->actionGrabMut($start+1,$num);
            sleep($interval);
            $start += $num;
        }*/
        return $this->render('grab');
    }

    public function actionList()
    {
        //模板参数设置
        $view = $this->getView();
        $view->title = '京东商品列表';
        $view->params = [
            'refresh' => 30,
        ];
        //
        $goods = $this -> good_m -> get_good_list();
        return $this->render('list',array(
            'goods' => $goods['goods'],
            'total' => $goods['total'],
        ));
    }
}
