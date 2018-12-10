<?php
/**
 * Created by PhpStorm.
 * User: Lin
 * Date: 2015/5/16
 * Time: 9:34
 */
$json = null;
if($_POST['tag'] == 'gettotal'){
    $json['data'] = array(
        array('key01'=>'sdsdsd','key02'=>'1'),
        array('key01'=>'sd','key02'=>'2'),
        array('key01'=>'ds','key02'=>'3'),
        array('key01'=>'ad','key02'=>'4'),
        array('key01'=>'ca','key02'=>'5'),
        array('key01'=>'sds','key02'=>'6'),
        array('key01'=>'ds','key02'=>'7'),
//        array('key01'=>'dsa','key02'=>'8'),
//        array('key01'=>'sd','key02'=>'9'),
//        array('key01'=>'sda','key02'=>'10'),
//        array('key01'=>'sad','key02'=>'11'),
//        array('key01'=>'cz','key02'=>'12'),
//        array('key01'=>'sda','key02'=>'13'),
//        array('key01'=>'sd','key02'=>'14'),
//        array('key01'=>'sa','key02'=>'15'),
//        array('key01'=>'ad','key02'=>'16'),
//        array('key01'=>'da','key02'=>'17'),
//        array('key01'=>'sacx','key02'=>'18'),
//        array('key01'=>'sad','key02'=>'19'),
//        array('key01'=>'saw','key02'=>'20'),
//        array('key01'=>'cxd','key02'=>'21'),
//        array('key01'=>'das','key02'=>'22'),
//        array('key01'=>'daw','key02'=>'23'),
//        array('key01'=>'eef','key02'=>'24'),
//        array('key01'=>'ead','key02'=>'25'),
//        array('key01'=>'sad','key02'=>'26'),
//        array('key01'=>'xca','key02'=>'27'),
    );
    $json['total'] = count($json['data']);
    $json['data'][] = array(
        'bigtitle'=>'你看到的是预加载数据'
        );
    $json['data'][] = array(
        'bigtitle'=>'你看到的是预加载数据2'
    );
    exit(json_encode($json));
}

$json = array('data'=>array());
foreach($_POST['keydata'] as $key=>$val){
    if(++$_POST['curindex'] > $_POST['total']){
        continue;
    }
    $arr = array(
        'tagindex'=>$_POST['curindex'],
        'bigtitle'=>serialize($val),
        'midtitle'=>array('data02'=>'这是数据02','data03'=>'这是数据03'),
        //如果键是纯数字，则不按照class类名寻找
        'smatitle'=>array(0=>array('data05'=>'这是数据051','data06'=>'这是数据061','data07'=>'这是数据071'),
                        array('data05'=>'这是数据052','data06'=>'这是数据062','data07'=>'这是数据072'),
        ));
    array_push($json['data'],$arr);
}

echo json_encode($json);