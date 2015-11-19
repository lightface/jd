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
        session_start();
        if(isset($_SESSION['start']) && !empty($_SESSION['start'])){
            $_SESSION['start'] = 0;
        }
        if(isset($_GET['start']) && !empty($_GET['start'])){
            $_SESSION['start'] = $_GET['start'];
        }
        $_SESSION['error']=0;
        $this->good_m = new Good();
    }

    function flush (){
        @ob_flush();
        @flush();
    }

    //����ģʽ
    public function actionGrab()
    {
        set_time_limit(0);
        header("Content-type:text/html;charset=utf-8");
        $curl = new Curl();
        $num = ! empty($_GET['num']) ? $_GET['num'] : 200;
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
                echo 'id:'.$i.'  '.date('H:i:s',time()).'  '.'名称'.$good['name'].'   '.'价钱'.$good['price'].'<br/>';
                $this->flush();
                unset($good);
                $done++;
            }
            $i++;
        } while($i<$start+$num);
        $this->end = $start+$num;

//        echo '<pre/>';
        echo $num.'<br/>';
        echo '<hr/><br/>'.'抓到'.$done.'条，消耗时间'.(microtime(true)-$starttime).'秒<br/>';
//        print_r($goods);
//        echo QW::date_friendly(time()-60);
    }

    //����ģʽ
    public function actionGrabMut()
    {
        set_time_limit(0);
        $curl = new Curl();
        $num = 30;
        if(isset($_SESSION['start']) && !empty($_SESSION['start'])){
            $start = $_SESSION['start'];
        } else {
            $start = $this->good_m->start_id();
        }

        $starttime = microtime(true);
//        $start = 2100740;

        $url_arr = [];
        for($i=0;$i<$num;$i++){
            $url_arr[$start+$i] = 'http://item.m.jd.com/product/'.($start+$i).'.html';
        }
        echo date('Y-m-d H:i:s').': 开始'.$start.'至'.($start+$num).',一共'.$num.'条<br/><br/>';
        $this->flush();

        $result = $curl->catch_multi($url_arr);
        if(count($result)<1){
            $_SESSION['error'] ++;
            if($_SESSION['error']>50){
                sleep(60);
                $_SESSION['error']=0;
            }
        } else {
            $_SESSION['error']=0;
        }
        if(isset($_SESSION['good']) && !empty($_SESSION['good'])){
            $_SESSION['good'] += $result;
            if(count($_SESSION['good'])>30){
                $this -> good_m -> insert_goods($_SESSION['good']);
                echo count($_SESSION['good']).'存档';
                $this->flush();
//                sleep(1);
                unset($_SESSION['good']);
            }
        } else {
            $_SESSION['good'] = $result;
        }
//        $this -> good_m -> insert_goods($result);
        //ģ���������

        echo '抓到<span style="color:red">'.count($result).'</span>时间<span style="color:red">'.(microtime(true)-$starttime).'</span>秒<br/>'.'<hr/><br/>';
        $this->flush();
        $_SESSION['start'] = $start + ($num+1);
    }

    public function actionAuto(){
        ignore_user_abort();
//��ʹClient�Ͽ�(��ص������)��PHP�ű�Ҳ���Լ���ִ��.
        set_time_limit(0);
//ִ��ʱ��Ϊ�����ƣ�phpĬ�ϵ�ִ��ʱ����30�룬ͨ��set_time_limit(0)�����ó��������Ƶ�ִ����ȥ
        $interval=1;
        while(1){
            $this->actionGrabMut();
            sleep($interval);
            echo "<script>document.body.innerHTML='';</script>";
        }
    }

    public function actionList()
    {
        //ģ���������
        ignore_user_abort();
        set_time_limit(0);
        $view = $this->getView();
        $view->title = '京东抓取列表';
        $view->params = [
            'refresh' => 60,
        ];
        //
        $goods = $this -> good_m -> get_good_list();
        return $this->render('list',array(
            'goods' => $goods['goods'],
            'total' => $goods['total'],
        ));
    }
}
