<?php
/**
 * User: linzhv@qq.com
 * Date: 12/04/2018
 * Time: 23:02
 */
declare(strict_types=1);


namespace sharin\core\conch;

use sharin\Component;
use sharin\Kernel;

class TracePage extends Component
{
    public function __toString()
    {

        if (SR_IS_CLI) return false;
        $status = Kernel::status();
//吞吐率  1秒/单次执行时间
        if (count($status) > 1) {
            $last = end($status);
            $first = reset($status);            //注意先end后reset
            $stat = [
                1000 * round($last[0] - $first[0], 6),
                number_format(($last[1] - $first[1]), 6)
            ];
        } else {
            $stat = [0, 0];
        }
        $requestPerSecond = empty($stat[0]) ? 'Unknown' : 1000 * number_format(1 / $stat[0], 8) . ' req/s';

//包含的文件数组
        $files = get_included_files();
        $info = [];
        $len = strlen(SR_PATH_ROOT);
        foreach ($files as $currentKey => $file) {
            $size = number_format(filesize($file) / 1024, 2);
            $file = substr($file, $len);
            $info[] = "{$file} Size:{$size} KB";
        }

//运行时间与内存开销
        $prevKey = null;
        $runtimeStatusString = ['Total' => "{$stat[0]}ms",];
        foreach ($status as $currentKey => $val) {
            if (null === $prevKey) {
                $prevKey = $currentKey;
                continue;
            }
            $runtimeStatusString["[$prevKey --> $currentKey] "] =
                number_format(1000 * floatval($status[$currentKey][0] - $status[$prevKey][0]), 6) . 'ms&nbsp;&nbsp;' .
                number_format((floatval($status[$currentKey][1] - $status[$prevKey][1]) / 1024), 2) . ' KB';
            $prevKey = $currentKey;
        }


        $vars = [
            'General' => [
                'request' => date('Y-m-d H:i:s', time()) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . $_SERVER['REQUEST_METHOD'],
                'time' => "{$stat[0]}ms",
                'qps' => $requestPerSecond,//吞吐率
                'session id' => session_id(),
                'obcache-size' => number_format((ob_get_length() / 1024), 2) . ' KB',//不包括trace
            ],
            'Files' => array_merge(['Total' => count($info)], $info),
            'Status' => $runtimeStatusString,
            'GET' => $_GET,
            'POST' => $_POST,
            'REQUEST' => $_REQUEST,
            'SERVER' => $_SERVER,
            'ENV' => $_ENV,
            'COOKIE' => empty($_COOKIE) ? [] : $_COOKIE,
            'SESSION' => isset($_SESSION) ? $_SESSION : ['SESSION state disabled'],//session_start()之后$_SESSION数组才会被创建
            'IP' => [
                '$_SERVER["HTTP_X_FORWARDED_FOR"]' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'NULL',
                '$_SERVER["HTTP_CLIENT_IP"]' => isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : 'NULL',
                '$_SERVER["REMOTE_ADDR"]' => $_SERVER['REMOTE_ADDR'],
                'getenv("HTTP_X_FORWARDED_FOR")' => getenv('HTTP_X_FORWARDED_FOR'),
                'getenv("HTTP_CLIENT_IP")' => getenv('HTTP_CLIENT_IP'),
                'getenv("REMOTE_ADDR")' => getenv('REMOTE_ADDR'),
            ],
        ];

        $nav = $win = '';
        foreach ($vars as $currentKey => $value) {
            $nav .= "<span style=\"color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700\">$currentKey</span>";
            $win .= '<div style="display:none;"><ol style="padding: 0; margin:0">';
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    if (!is_string($val)) $val = var_export($val, true);
                    $win .= '<li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px"><span style="color: blue">' .
                        (is_numeric($k) ? '' : $k . ':</span>') .
                        "<span  style='color:black'>{$val}</span></li>";
                }
            } else {
                $win .= htmlspecialchars($value, ENT_COMPAT, 'utf-8');
            }
            $win .= '</ol></div>';
        }
        return <<<endline
    <div style="border-left:thin double #ccc;position: fixed;bottom:0;right:0;font-size:14px;width:840px;z-index: 999999;color: #000;text-align:left;font-family:'Times New Roman';cursor:default;">
        <div id="ptt" style="display: none;background:white;margin:0;height: 480px;">
            <!-- navigation bar -->
            <div id="pttt" style="height:32px;padding: 6px 12px 0;border-bottom:1px solid #ececec;border-top:1px solid #ececec;font-size:16px">
                {$nav}
            </div>
            <!-- detail window -->
            <div id="pttc" style="overflow:auto;height:478px;padding: 0; line-height: 24px">
                {$win}
            </div>
        </div>
        <!-- 关闭按钮 -->
        <div id="ptc" style="display:none;text-align:right;height:15px;position:absolute;top:10px;right:12px;cursor: pointer;">
            <img style="vertical-align:top;" src="data:image/png;base64,R0lGODlhDwAPAJEAAAAAAAMDA////wAAACH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4wLWMwNjAgNjEuMTM0Nzc3LCAyMDEwLzAyLzEyLTE3OjMyOjAwICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUQxMjc1MUJCQUJDMTFFMTk0OUVGRjc3QzU4RURFNkEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUQxMjc1MUNCQUJDMTFFMTk0OUVGRjc3QzU4RURFNkEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRDEyNzUxOUJBQkMxMUUxOTQ5RUZGNzdDNThFREU2QSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRDEyNzUxQUJBQkMxMUUxOTQ5RUZGNzdDNThFREU2QSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PgH//v38+/r5+Pf29fTz8vHw7+7t7Ovq6ejn5uXk4+Lh4N/e3dzb2tnY19bV1NPS0dDPzs3My8rJyMfGxcTDwsHAv769vLu6ubi3trW0s7KxsK+urayrqqmop6alpKOioaCfnp2cm5qZmJeWlZSTkpGQj46NjIuKiYiHhoWEg4KBgH9+fXx7enl4d3Z1dHNycXBvbm1sa2ppaGdmZWRjYmFgX15dXFtaWVhXVlVUU1JRUE9OTUxLSklIR0ZFRENCQUA/Pj08Ozo5ODc2NTQzMjEwLy4tLCsqKSgnJiUkIyIhIB8eHRwbGhkYFxYVFBMSERAPDg0MCwoJCAcGBQQDAgEAACH5BAAAAAAALAAAAAAPAA8AAAIdjI6JZqotoJPR1fnsgRR3C2jZl3Ai9aWZZooV+RQAOw==" />
        </div> 
    </div>
    <!-- 开启按钮 -->
    <div id="pto" style="height:30px;float:right;text-align: right;overflow:hidden;position:fixed;bottom:0;right:0;color:#000;line-height:30px;cursor:pointer;">
        <div style="background:#232323;color:#FFF;padding:0 6px;float:right;line-height:30px;font-size:14px"></div>
    <img style="width: 30px" title="ShowPageTrace" src="data:image/png;base64,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" />
    </div>
    <script type="text/javascript">
    (function(){
        var tab_tit  = document.getElementById('pttt').getElementsByTagName('span');
        var tab_cont = document.getElementById('pttc').getElementsByTagName('div');
        var open     = document.getElementById('pto');
        var close    = document.getElementById('ptc');
        var trace    = document.getElementById('ptt');
        var cookie   = document.cookie.match(/_spt=(\d\|\d)/);
        var history  = (cookie && typeof cookie[1] != 'undefined' && cookie[1].split('|')) || [0,0];
        open.onclick = function(){
            trace.style.display = '';
            close.style.display = '';
            history[0] = 1;
            document.cookie = '_spt='+history.join('|');
        };
        close.onclick = function(){
            trace.style.display = 'none';
            open.style.display = 'block';
            history[0] = 0;
            document.cookie = '_spt='+history.join('|');
        };
        for(var i = 0; i < tab_tit.length; i++){
            tab_tit[i].onclick = (function(i){
                return function(){
                    for(var j = 0; j < tab_cont.length; j++){
                        tab_cont[j].style.display = 'none';
                        tab_tit[j].style.color = '#999';
                    }
                    tab_cont[i].style.display = 'block';
                    tab_tit[i].style.color = '#000';
                    history[1] = i;
                    document.cookie = '_spt='+history.join('|')
                }
            })(i);
        }
        parseInt(history[0]) && open.click();
        (tab_tit[history[1]] || tab_tit[0]).click();
    })();
    </script>
endline;

    }
}