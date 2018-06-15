<?php
/**
 * User: linzhv@qq.com
 * Date: 06/04/2018
 * Time: 10:04
 */
declare(strict_types=1);


namespace driphp\library;

/**
 * Class CodeFactory
 * @package driphp\library
 */
class CodeFactory
{

    /**
     * 去除代码中的空白和注释
     * @param string $content 代码内容
     * @return string
     */
    public static function stripWhiteSpace($content)
    {
        $stripStr = '';
        //分析php源码
        $tokens = token_get_all($content);
        $last_space = false;
        for ($i = 0, $len = count($tokens); $i < $len; $i++) {
            if (is_string($tokens[$i])) {
                $last_space = false;
                $stripStr .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    //过滤各种php注释
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    //过滤空格
                    case T_WHITESPACE:
                        if (!$last_space) {
                            $stripStr .= ' ';
                            $last_space = true;
                        }
                        break;
                    case T_START_HEREDOC:
                        $stripStr .= "<<<lite\n";
                        break;
                    case T_END_HEREDOC:
                        $stripStr .= "lite;\n";
                        for ($k = $i + 1; $k < $len; $k++) {
                            if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                                $i = $k;
                                break;
                            } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                        break;
                    default:
                        $last_space = false;
                        $stripStr .= $tokens[$i][1];
                }
            }
        }
        return $stripStr;
    }
}