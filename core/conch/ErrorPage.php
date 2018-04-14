<?php
/**
 * User: linzhv@qq.com
 * Date: 12/04/2018
 * Time: 23:00
 */
declare(strict_types=1);


namespace sharin\core\conch;

use sharin\Component;
use sharin\Kernel;

/**
 * Class ErrorPage 错误页面
 *
 * @method ErrorPage getInstance(...$params) static
 *
 * @package sharin\core\response
 */
class ErrorPage extends Component
{


    protected $output = '';

    public function __toString()
    {
        return $this->output;
    }

    /**
     * @param array $traces
     * @return string
     */
    private function _build_trace(array $traces)
    {
        $html = '';
        foreach ($traces as $id => $trace) {
            # Error和Exception获取的trace是不一样的，Exception包含自身提示信息的部分，但是Error少了这一部分，所以把下面的代码注释
            $relative_file = (isset($trace['file'])) ? ltrim(str_replace(array(SR_PATH_ROOT, '\\'), array('', '/'), $trace['file']), '/') : '';
            $current_line = (isset($trace['line'])) ? $trace['line'] : '';
            $html .= '<li>';
            $html .= '<b>' . ((isset($trace['class'])) ? $trace['class'] : '') . ((isset($trace['type'])) ? $trace['type'] : '') . $trace['function'] . '</b>';
            $html .= <<<endline
    - <a style='font-size: 12px;  cursor:pointer; color: blue;' onclick='document.getElementById("psTrace_{$id}").style.display = (document.getElementById("psTrace_{$id}").style.display != "block") ? "block" : "none"; return false'>[line {$current_line} - {$relative_file}]</a>
endline;

            if (isset($trace['args']) && count($trace['args'])) {
                $html .= ' - <a style="font-size: 12px;  cursor:pointer; color: blue;" onclick="document.getElementById(\'psArgs_' . $id . '\').style.display = (document.getElementById(\'psArgs_' . $id . '\').style.display != \'block\') ? \'block\' : \'none\'; return false">[' . count($trace['args']) . ' Arguments]</a>';
            }

            if ($relative_file) {
                $html .= $this->_build_file_block($trace['file'], $trace['line'], $id);
            }
            if (isset($trace['args']) && count($trace['args'])) {
                $html .= $this->_build_args_block($trace['args'], $id);
            }
            $html .= '</li>';
        }
        return $html;
    }

    /**
     * Display lines around current line
     *
     * @param string $file
     * @param int $line
     * @param string $id
     * @return string
     */
    private function _build_file_block($file, $line, $id = null)
    {
        dumpout($file);
        $lines = file($file);
        $offset = $line - 6;
        $total = 11;
        if ($offset < 0) {
            $total += $offset;
            $offset = 0;
        }
        $lines = array_slice($lines, $offset, $total);
        ++$offset;

        $html = '<div class="psTrace" id="psTrace_' . $id . '" ' . ((is_null($id) ? 'style="display: block"' : '')) . '><pre>';
        foreach ($lines as $k => $l) {
            $string = ($offset + $k) . '. ' . htmlspecialchars($l);
            if ($offset + $k == $line) {
                $html .= '<span class="selected">' . $string . '</span>';
            } else {
                $html .= $string;
            }
        }
        return $html . '</pre></div>';
    }


    /**
     * Display arguments list of traced function
     *
     * @param array $args List of arguments
     * @param string $id ID of argument
     * @return string
     */
    private function _build_args_block($args, $id)
    {
        $html = '<div class="psArgs" id="psArgs_' . $id . '"><pre>';
        foreach ($args as $arg => $value) {
            $html .= '<b>args [' . Kernel::filter((string)$arg) . "]</b>\n";
            $html .= Kernel::filter(print_r($value, true));
            $html .= "\n";
        }
        return $html . '</pre>';
    }

    /**
     * Return the content of the Exception
     * @param string $message
     * @param string $file
     * @param int $line
     * @return string content of the exception
     */
    private static function _build_content($message, $file, $line)
    {
        dumpin($file);
        $format = '<p><b style="color: mediumslateblue">%s</b><br/><i>at line </i><b>%d</b><i> in file </i><b style="color: darkorchid">%s</b></p>';
        return sprintf($format, $message, $line, ltrim(str_replace(array(SR_PATH_ROOT, '\\'), array('', '/'), $file), '/'));
    }

    public function displayError(string $message, string $className, string $file, int $line, int $code, array $traces)
    {
// Display error message
        $this->output .= '<style>
                    #lite_throwable_display{ font-size: 14px;font-family: "Consolas", "Bitstream Vera Sans Mono", "Courier New", Courier, monospace}
                    #lite_throwable_display h2{color: #F20000}
                    #lite_throwable_display p{padding-left: 20px}
                    #lite_throwable_display ul li{margin-bottom: 10px}
                    #lite_throwable_display a{font-size: 12px; color: #000000}
                    #lite_throwable_display .psTrace, #lite_throwable_display .psArgs{display: none}
                    #lite_throwable_display pre{border: 1px solid #236B04; background-color: #EAFEE1; padding: 5px;  width: 99%; overflow-x: auto; margin-bottom: 30px;}
                    #lite_throwable_display .psArgs pre{background-color: #f1fdfe;}
                    #lite_throwable_display pre .selected{color: #F20000; font-weight: bold;}
                </style>
                <div id="lite_throwable_display">';
        $this->output .= "<h2>[{$className}] {$code}</h2>";
        $this->output .= $this->_build_content($message, $file, $line);

        $this->output .= $this->_build_file_block($file, $line);

// Display debug backtrace
        $this->output .= '<ul>' . $this->_build_trace($traces) . '</ul></div>';
        return $this->output;
    }

}