<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/10 0010
 * Time: 20:09
 */

namespace {

    use driphp\core\database\Dao;
    use driphp\service\Excel;
    use driphp\KernelException;

    require __DIR__ . '/../boot.php';

    $input = __DIR__ . '/../../vendor/example-0514.xlsx';
    $output = __DIR__ . '/../../vendor/example-0514.' . time() . '.xlsx';
    const TYPE_MAP = [ # 海外
        'SDK' => 19,
        'DSP' => 20,
        'SEM' => 21,
        'SNS' => 22,
        'BRAND' => 23,
        'INCENT' => 24,
        'VIDEO' => 25,
        'CPC' => 26,
        'CPA' => 27,
        'CPM' => 29,
    ];
    try {

        $dao = Dao::getInstance();
        $dao->config('drivers.default.config.name', 'ad_control');

//        $dao->config('drivers.default.config.user', 'dbuser');
//        $dao->config('drivers.default.config.passwd', 'xVeY4jV/zNbcKc8Z');
//        $dao->config('drivers.default.config.host', '10.18.97.205');
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
            'A' => 'platform_id',
            'B' => 'platform_name',
            'C' => 'ad_type',
            'D' => 'supplier_name',
            'E' => 'resource_name',
        ])->getBodies();


        foreach ($list as &$item) {
            if (empty(trim($item['supplier_name']))) {
                continue;
            }
//            $res = $dao->query('select id,platform_name from ad_basis.ad_gtaplatforms where platform_name like :name;', [
//                ':name' => '%' . str_replace(' ', '%', trim($item['platform_name'])) . '%',
//            ]);
//            if ($res) {
//                $c = 0;
//                foreach ($res as $i) {
//                    $item["platform[$c]_matches_id"] = $i['id'];
//                    $item["platform[$c]_matches_name"] = $i['platform_name'];
//                    $supplier_ids[] = $i['id'];
//                    $c++;
//                }
//            }

            $item['ad_type_code'] = TYPE_MAP[$item['ad_type']];

            $supplier_ids = $resource_ids = [];
            $res = $dao->query('select id,supplier_name from ad_basis.ad_suppliers where supplier_name like :name
and deleted_at is null and status =1;', [
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


            $res = $dao->query('select id,resource_name from ad_basis.ad_resources where resource_name like :name and deleted_at is null and status =1;', [
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
                    $res = $dao->query('select m.id,m.master_name,m.platform_id,
bm.master_id,bm.company_id
 from ad_basis.ad_masters m 
INNER JOIN ad_basis.ad_business_master bm on bm.master_id = m.id
where
		bm.company_id in (3,2,6) and bm.platform_type = 1 and
              bm.supplier_id = :supplier_id and
              bm.resource_id = :resource_id and m.deleted_at is null and m.status > 0;
', [
                        ':supplier_id' => $supplier_id,
                        ':resource_id' => $resource_id,
                    ]);
                    if ($res) {
                        $c = 0;
                        foreach ($res as $i) {
                            $platform = intval($i['platform_id']);
                            if ($platform != 4) continue;# 国内全部过滤
                            switch ($i['company_id']) {
                                case '3':
                                    isset($item["master_matches_id[{Ican?][$c]"]) or $item["master_matches_id[{Ican?][$c]"] = $i['id'];
//                                    isset($item["master_matches_name[{Ican?][$c]"]) or $item["master_matches_name[{Ican?][$c]"] = $i['master_name'];
                                    break;
                                case '2':
                                    isset($item["master_matches_id[Gtarcade][$c]"]) or $item["master_matches_id[Gtarcade][$c]"] = $i['id'];
//                                    isset($item["master_matches_name[Gtarcade][$c]"]) or $item["master_matches_name[Gtarcade][$c]"] = $i['master_name'];
                                    break;
                                case '6':
                                    isset($item["master_matches_id[YooZoo India][$c]"]) or $item["master_matches_id[YooZoo India][$c]"] = $i['id'];
//                                    isset($item["master_matches_name[YooZoo India][$c]"]) or $item["master_matches_name[YooZoo India][$c]"] = $i['master_name'];
                                    break;
                            }
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
        KernelException::dispose($throwable);
    }
}