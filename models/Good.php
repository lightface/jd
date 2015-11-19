<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Class Good物品处理
 * @package app\models
 */
class Good extends Model
{
    protected $db = 1;
    public function init(){
        $this->db = Yii::$app->db;
    }
    public function get_good_list()
    {
        $db = Yii::$app->db;
        $command = $db->createCommand('SELECT * FROM goods ORDER BY update_time DESC LIMIT 200');
        $goods = $command->queryAll();

        $count_com = $db->createCommand('SELECT COUNT(id) FROM goods ');
        $count = $count_com->queryScalar();
        return ['goods'=>$goods,'total'=>$count];
    }

    public function insert_goods($goods)
    {
        if(is_array($goods) && count($goods)>0){
            $values = '';
            foreach($goods as $id => $good){
                $values .= ',('.$id." ,'".addslashes($good['name'])."' ,'".$good['price']."' ,"."unix_timestamp(),unix_timestamp() )";
            }
            $values = substr($values,1);
        } else {
            return false;
        }
        $command = $this->db->createCommand('INSERT INTO goods (id,name,price_old,add_time,update_time) VALUES '.$values." ON DUPLICATE KEY UPDATE price_new = VALUES(price_old) , update_time = unix_timestamp()");
        $result = $command->execute();
        return $result;
    }

    public function start_id(){
        $command = $this->db->createCommand('SELECT id FROM goods ORDER BY update_time DESC LIMIT 1');
        $max_id = $command->queryOne();
        return $max_id['id'];
    }
}
