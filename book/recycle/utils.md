# 简介
phar目录保存着第三方一些有用的工具:
- phpdoc 输出php备注成文档
- phpunit php单元测试
- composer php包依赖工具

**当由更新时更新这些包不会对代码的运行有影响**


## phpdoc
类似javadoc的一个项目,可以将代码注释输出为html文档

最新版的文档:
> https://phpdoc.org/docs/latest/index.html
### 安装
下载地址:
> https://github.com/phpDocumentor/phpDocumentor2
由三种安装方式:
- PEAR (官方推荐)
```bash
pear channel-discover pear.phpdoc.org
pear install phpdoc/phpDocumentor
```
- Via Composer
phpDocumentor is available on Packagist.
It can be installed as a dependency of your project by running the following command.
```bash
composer require --dev phpdocumentor/phpdocumentor
```
WINDOWS USERS you may encounter an error ZipArchive::extractTo(): Full extraction path exceed MAXPATHLEN (260) when trying to run the above command. If you do simply add --prefer-source at the end of the command.

Afterwards you are able to run phpDocumentor directly from your vendor directory:
```bash
php vendor/bin/phpdoc
```
- 使用PHAR
1.Download the phar file from http://phpdoc.org/phpDocumentor.phar, or from a release listed on github 下载发行包
2.Move the file with sudo to your bin directory: sudo mv phpDocumentor.phar /usr/local/bin/phpdoc 移动到环境变量能找到的路径下(或者添加环境变量)
3.Ensure the file has execute rights: sudo chmod +x /usr/local/bin/phpdoc  添加执行权限
4.Confirm it runs (you may have to restart your SSH session if you're connecting remotely first) by running phpdoc --version from any directory. 使用'phpdoc --version'测试是否安装,成功时返回下面的字符串'phpDocumentor version v2.8.5',2.8.5是我目前的版本号

### 使用
```bash
phpdoc run -d [源目录] -t [文档输出目录]
```




# phar说明
PHP5.3之后支持了类似Java的jar包，名为phar。用来将多个PHP文件打包为一个文件

首先需要修改php.ini配置将phar的readonly关闭，默认是不能写phar包的，include是默认开启的。

phar.readonly => On

##创建
```php
$phar = new Phar('lite.phar');
# 指定压缩目录
$phar->buildFromDirectory(__DIR__.'/../', '/\.php$/');
# 指定gzip格式压缩
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();
# setSub用来设置启动加载的文件,默认会自动加载并执行 lib_config.php
$phar->setStub($phar->createDefaultStub('lib_config.php'));
```

## 使用
```php
include 'lite.phar';
include 'lite.phar/code/page.php';
```
