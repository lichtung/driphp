# 简介 
dripex是一款极简的PHP框架，框架设计以ThinkPHP为基础，只保留最核心的功能

目前仍然处于测试版
@see http://www.jetbrains.com/help/phpstorm/2016.3/spellchecking.html

npm install -g plugman
## 目录结构 
dripex主要目录结构如下：
~~~
 dripex               框架
    ├─book/          文档
    ├─core/          核心累
    ├─database/      数据库，包括关系型、NoSQL数据库
    ├─throwable/     内置异常类
    ├─i18n/          语言文件      
    ├─include/       被包含文件，如模板
    ├─library/       类库
    ├─service/       第三方整合服务类库    
    ├─test/          phpunit测试库
    ├─vendor/        内置第三方
    └─bootstrap.php  框架引导文件  
~~~

## 第一个项目 
假如dripex放在网站更目录下，项目名称为demo
那么在dripex并行的目录下用创建一个目录demo，并分别创建文件**public/index.php**和**controller/index.php**
~~~
 webroot 网站更目录
    │
    ├─demo
    │  ├─public         公共访问目录（对外访问目录）
    │  │   └─index.php   入口文件
    │  └─controller     控制器目录
    │      └─index.php   入口文件
    │
    └─dripex  应用目录...
~~~
其中**public**是公共访问目录，使用nginx或者apache创建虚拟主机时需要目录为虚拟主机的根目录

以apache为例：
```apacheconfig
<VirtualHost *:80>
    DocumentRoot "/srv/webroot/demo/public"
    ServerName  demo.me
</VirtualHost>
```
**public/index.php**是项目的入口文件，所有的请求都会经过该文件，再流向控制器，
其内容如下:
```php
<?php
# 第一步：定义了项目的名称，这是必须设定的
const DRIP_PROJECT_NAME = 'demo'; 
# 第二步：如果框架引导文件
require __DIR__ . '/../../dripex/bootstrap.php'; 
# 第三步：获取框架引擎实例(getInstance) => 初始化(initialize) => 开启应用(start)
\dripex\Dripex::getInstance()->initialize()->start();
```

**controller/index.php**是默认的控制器index类，内容如下：
```php
<?php
# 命名空间与文件夹为止一一对应
namespace demo\controller;
# 控制器名称大小写敏感，全部与URL相对应
class index
{
    public function index()
    {
        echo 'hello world';
    }
    public function foo(){
        echo 'this is foo';
    }
}
```
之后访问地址[http://demo.me](http://demo.me)就可以看到hello world了

输入地址[http://demo.me/index.php/foo](http://demo.me/index.php/foo),
就是说pathinfo中的foo被解释成了方法的名称（我们称之为action），引擎会到index控制器中找到foo方法并执行

这个时候按规律，我创建了一个控制器**controller/user.php**，里面内容如下
```php
<?php
namespace demo\controller;
class user
{
    public function lists()
    {
        echo 'this is user-list';
    }
}
```
输入地址[http://demo.me/index.php/user/lists](http://demo.me/index.php/user/lists),可以访问user控制器的lists方法

###### URL重写
如果希望地址**http://demo.me/index.php/user/lists**中去掉**/index.php**
如果是apache服务器，开启AllowOverride的情况下在public目录下创建文件**.htaccess**即可
```apacheconfig
<IfModule mod_rewrite.c>
    Options +FollowSymlinks

    # 开启URL重写
    RewriteEngine On
    # 目录存在时直接访问目录
    RewriteCond %{REQUEST_FILENAME} !-d
    # 文件存在时直接访问文件
    RewriteCond %{REQUEST_FILENAME} !-f
    # 这些后缀的文件，就直接访问文件，不进行Rewrite
    RewriteCond %{REQUEST_URI} !^.*(\.css|\.js|\.gif|\.png|\.jpg|\.jpeg)$
    RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]

</IfModule>
<Files *>
    #防止罗列资源文件
    Options -Indexes
</Files>
```
