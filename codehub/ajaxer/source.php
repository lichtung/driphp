<?php
/**
 * Created by PhpStorm.
 * User: Lin
 * Date: 2015/5/16
 * Time: 9:34
 */
$json = array('data'=>array());
$arr = array(
    'data01'=>'这是数据1',
    'data02'=>'这是数据2'
);
for($i = 0;$i < ($_POST['size']);$i++){
    array_push($json['data'],$arr);
}
if($_POST['tag'] == 'gettotal'){
    $json['total'] = 1000;
}
echo json_encode($json);