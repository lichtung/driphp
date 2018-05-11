<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/10 0010
 * Time: 20:09
 */

namespace {

    use sharin\core\database\Dao;
    use sharin\service\Excel;
    use sharin\SharinException;

    require __DIR__ . '/../boot.php';

    $input = __DIR__ . '/example.xlsx';
    $output = __DIR__ . '/new.online.xls';
    try {

        $dao = Dao::getInstance();
        $dao->config('drivers.default.config.name', 'ad_control');

//    $dao->config('drivers.default.config.user', 'dbuser');
//    $dao->config('drivers.default.config.passwd', 'xVeY4jV/zNbcKc8Z');
//    $dao->config('drivers.default.config.host', '10.18.97.205');

        $dao->config('drivers.default.config.name', 'ad_control');
        $dao->config('drivers.default.config.user', 'root');
        $dao->config('drivers.default.config.passwd', 'asdqwe123_ZXC');
        $dao->config('drivers.default.config.host', '127.0.0.1');

//        dumpout(Excel::getInstance()->read(__DIR__ . '/example.xlsx', [
//            'A' => 'platform_name',
//            'B' => 'ad_type',
//            'C' => 'supplier_name',
//            'D' => 'resource_name',
//            'E' => 'raw_master_name',
//        ])->getTitles());

        $list = Excel::getInstance()->read($input, [
            'A' => 'platform_name',
            'B' => 'ad_type',
            'C' => 'supplier_name',
            'D' => 'resource_name',
            'E' => 'raw_master_name',
        ])->getBodies();


        foreach ($list as &$item) {
            $res = $dao->query('select id,platform_name from ad_basis.ad_gtaplatforms where platform_name like :name;', [
                ':name' => '%' . str_replace(' ', '%', trim($item['platform_name'])) . '%',
            ]);
            if ($res) {
                $c = 0;
                foreach ($res as $i) {
                    $item["platform[$c]_matches_id"] = $i['id'];
                    $item["platform[$c]_matches_name"] = $i['platform_name'];
                    $supplier_ids[] = $i['id'];
                    $c++;
                }
            }


            $supplier_ids = $resource_ids = [];
            $res = $dao->query('select id,supplier_name from ad_basis.ad_suppliers where supplier_name like :name;', [
                ':name' => '%' . str_replace(' ', '%', trim($item['supplier_name'])) . '%',
            ]);
            if ($res) {
                $c = 0;
                foreach ($res as $i) {
                    $item["supplier[$c]_matches_id"] = $i['id'];
                    $item["supplier[$c]_matches_name"] = $i['supplier_name'];
                    $supplier_ids[] = $i['id'];
                    $c++;
                }
            }


            $res = $dao->query('select id,resource_name from ad_basis.ad_resources where resource_name like :name;', [
                ':name' => '%' . str_replace(' ', '%', trim($item['resource_name'])) . '%',
            ]);
            if ($res) {
                $c = 0;
                foreach ($res as $i) {
                    $item["resource[$c]_matches_id"] = $i['id'];
                    $item["resource[$c]_matches_name"] = $i['resource_name'];
                    $resource_ids[] = $i['id'];
                    $c++;
                }
            }


            foreach ($supplier_ids as $supplier_id) {
                foreach ($resource_ids as $resource_id) {
                    $res = $dao->query('select id,master_name,platform_id from ad_basis.ad_masters where 
              supplier_id = :supplier_id and 
              resource_id = :resource_id and deleted_at is null and status > 0;', [
                        ':supplier_id' => $supplier_id,
                        ':resource_id' => $resource_id,
                    ]);
                    if ($res) {
                        $c = 0;
                        foreach ($res as $i) {
                            $platform = intval($i['platform_id']);
                            if ($platform < 3) continue;# 国内全部过滤
                            switch ($platform) {
                                case 3:
                                    $platform = 'PC';
                                    continue;
                                    break;
                                case 4:
                                    $platform = '手游';
                                    break;
                            }
                            $item["master[$c]_matches_id"] = $i['id'];
                            $item["master[$c]_matches_name"] = $i['master_name'];
                            $item["master[$c]_matches_type"] = $platform;
                            $c++;
                        }
                    }
                }
            }
        }


        $titles = [];
        foreach ($list as $item) {
            foreach ($item as $title => $subItem) {
                $titles[$title] = [
                    'title' => $title,
                ];
            }
        }
        ksort($titles);

        Excel::getInstance()->build($titles, $list)->save($output);


    } catch (Throwable $throwable) {
        SharinException::dispose($throwable);
    }
}