<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 20:02
 */
declare(strict_types=1);


namespace sharin\library;

use sharin\Component;
use sharin\core\Cache;
use sharin\SharinException;

class ThinkCaptcha extends Component
{
    protected $config = [
        'seKey' => 'ThinkPHP.CN',   // 验证码加密密钥
        'codeSet' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',             // 验证码字符集合
        'expire' => 600,            // 验证码过期时间（s）
        'useZh' => false,           // 使用中文验证码
        'zhSet' => '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行',              // 中文验证码字符串
        'useImgBg' => false,           // 使用背景图片
        'fontSize' => 40,              // 验证码字体大小(px)
        'useCurve' => true,            // 是否画混淆曲线
        'useNoise' => true,            // 是否添加杂点
        'imageH' => 100,               // 验证码图片高度
        'imageW' => 300,               // 验证码图片宽度
        'length' => 4,               // 验证码位数
        'font' => '',              // 验证码字体，不设置随机获取
        'bg' => array(243, 251, 254),  // 背景颜色
        'reset' => true,           // 验证成功后是否重置
    ];
    private static $_image = NULL;     // 验证码图片实例
    private static $_color = NULL;     // 验证码字体颜色

    protected function initialize()
    {
    }

    /**
     * 验证验证码是否正确
     * @access public
     * @param string $id 标识
     * @param string $code 用户验证码
     * @return bool 用户验证码是否正确
     * @throws \sharin\throws\core\ClassNotFoundException
     * @throws \sharin\throws\core\DriverNotDefinedException
     */
    public function check($id, $code)
    {
        // 验证码不能为空
        $seCode = Cache::getInstance()->get($id);
        if (empty($code) || empty($seCode['verify_code']) || !is_array($seCode['verify_code'])) {
            return false;
        }
        // session 过期
        if (time() - $seCode['verify_time'] > $this->config['expire']) {
            Cache::getInstance()->delete($id);
            return false;
        }
        $string = strtoupper(implode('', $seCode['verify_code']));
        return $string === strtoupper($code);
    }

    /**
     * 输出验证码并把验证码的值保存的session中
     * 验证码保存到session的格式为： array('verify_code' => '验证码值', 'verify_time' => '验证码创建时间');
     * @access public
     * @param string $id 标识
     * @return void
     * @throws SharinException
     */
    public function entry($id)
    {
        $config = &$this->config;
        // 图片宽(px)
        $config['imageW'] || $config['imageW'] = $config['length'] * $config['fontSize'] * 1.5 + $config['length'] * $config['fontSize'] / 2;
        // 图片高(px)
        $config['imageH'] || $config['imageH'] = $config['fontSize'] * 2.5;
        // 建立一幅 $config['imageW'] x $config['imageH'] 的图像
        self::$_image = imagecreate((int)$config['imageW'], (int)$config['imageH']);
        // 设置背景
        imagecolorallocate(self::$_image, $config['bg'][0], $config['bg'][1], $config['bg'][2]);

        // 验证码字体随机颜色
        self::$_color = imagecolorallocate(self::$_image, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));
        // 验证码使用随机字体
        $ttfPath = __DIR__ . '/captcha/' . ($config['useZh'] ? 'zhttfs' : 'ttfs') . '/';

        if (empty($config['font'])) {
            $dir = dir($ttfPath);
            $ttfs = array();
            while (false !== ($file = $dir->read())) {
                if ($file[0] != '.' && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $dir->close();
            $config['font'] = $ttfs[array_rand($ttfs)];
        }
        $config['font'] = $ttfPath . $config['font'];


        if ($config['useImgBg']) {
            $this->_background();
        }

        if ($config['useNoise']) {
            // 绘杂点
            $this->_writeNoise();
            $this->_writeNoise();
        }
        if ($config['useCurve']) {
            // 绘干扰线
            $this->_writeCurve();
            $this->_writeCurve();
        }

        // 绘验证码
        $code = array(); // 验证码
        $codeNX = 0; // 验证码第N个字符的左边距
        if (!function_exists('imagettftext')) {
            throw new SharinException('gd2/freetype is required');
        }
        if ($config['useZh']) { // 中文验证码
            for ($i = 0; $i < $config['length']; $i++) {
                $code[$i] = iconv_substr($config['zhSet'], floor(mt_rand(0, mb_strlen($config['zhSet'], 'utf-8') - 1)), 1, 'utf-8');
                imagettftext(
                    self::$_image,
                    $config['fontSize'],
                    mt_rand(-40, 40),
                    $config['fontSize'] * ($i + 1) * 1.5,
                    $config['fontSize'] + mt_rand(10, 20),
                    self::$_color,
                    $config['font'],
                    $code[$i]);
            }
        } else {
            for ($i = 0; $i < $config['length']; $i++) {
                $code[$i] = $config['codeSet'][mt_rand(0, strlen($config['codeSet']) - 1)];
                $codeNX += mt_rand($config['fontSize'] * 1.2, $config['fontSize'] * 1.6);
                imagettftext(self::$_image, $config['fontSize'], mt_rand(-40, 40), $codeNX, $config['fontSize'] * 1.6, self::$_color, $config['font'], $code[$i]);
            }
        }

        // 保存验证码
        $seCode = [
            'verify_code' => $code, # 把校验码保存到session
            'verify_time' => time(), # 验证码创建时间
        ];
        Cache::getInstance()->set($id, $seCode, $config['expire']);

        header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header("content-type: image/png");

        // 输出图像
        imagepng(self::$_image);
        imagedestroy(self::$_image);
        die;
    }

    /**
     * 删除
     * @param string $key
     * @return bool
     */
    public function unlink(string $key): bool
    {
        Cache::getInstance()->delete($key);
        return true;
    }

    /**
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数)
     *
     *      高中的数学公式咋都忘了涅，写出来
     *        正弦型函数解析式：y=Asin(ωx+φ)+b
     *      各常数值对函数图像的影响：
     *        A：决定峰值（即纵向拉伸压缩的倍数）
     *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *        ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     */
    private function _writeCurve()
    {
        $py = 0;
        $config = &$this->config;

        $imageH = intval($config['imageH'] ?? 0);
        $imageW = intval($config['imageW'] ?? 0);

        // 曲线前部分
        $A = mt_rand(1, $imageH / 2);                  // 振幅
        $b = mt_rand(-$imageH / 4, $imageH / 4);   // Y轴方向偏移量
        $f = mt_rand(-$imageH / 4, $imageH / 4);   // X轴方向偏移量
        $T = mt_rand($imageH, $imageW * 2);  // 周期
        $w = (2 * M_PI) / $T;

        $px1 = 0;  // 曲线横坐标起始位置
        $px2 = mt_rand($imageW / 2, (int)($imageW * 0.8));  // 曲线横坐标结束位置

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $imageH / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($config['fontSize'] / 5);
                while ($i > 0) {
                    imagesetpixel(self::$_image, $px + $i, $py + $i, self::$_color);  // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多
                    $i--;
                }
            }
        }

        // 曲线后部分
        $A = mt_rand(1, $imageH / 2);                  // 振幅
        $f = mt_rand(-$imageH / 4, $imageH / 4);   // X轴方向偏移量
        $T = mt_rand($imageH, $imageW * 2);  // 周期
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $imageH / 2;
        $px1 = $px2;
        $px2 = $imageW;

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $imageH / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($config['fontSize'] / 5);
                while ($i > 0) {
                    imagesetpixel(self::$_image, $px + $i, $py + $i, self::$_color);
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    private function _writeNoise()
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate(self::$_image, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring(self::$_image, 5, mt_rand(-10, $this->config['imageW']), mt_rand(-10, $this->config['imageH']), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }

    /**
     * 绘制背景图片
     * 注：如果验证码输出图片比较大，将占用比较多的系统资源
     */
    private function _background()
    {
        $path = __DIR__ . '/captcha/bgs/';
        $dir = dir($path);

        $bgs = array();
        while (false !== ($file = $dir->read())) {
            if ($file[0] != '.' && substr($file, -4) == '.jpg') {
                $bgs[] = $path . $file;
            }
        }
        $dir->close();

        $gb = $bgs[array_rand($bgs)];

        list($width, $height) = @getimagesize($gb);
        $bgImage = @imagecreatefromjpeg($gb);
        @imagecopyresampled(self::$_image, $bgImage, 0, 0, 0, 0, $this->config['imageW'], $this->config['imageH'], $width, $height);
        @imagedestroy($bgImage);
    }

}