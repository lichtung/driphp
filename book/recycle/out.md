## 简介

lite的原意是"精简的,淡化的"

设计的目标是清晰简单,剔除冗余.
在这个稳定的基础上构建的上层建筑才能稳定并且可定制

相对于初版的改进和特定：
- 类的存放路径完全按照PSR-0规则存放
- 静态方式的调用全部改为基于实例的调用
- 目录全部小写，类名称按照Java风格书写
- 命令行模式和web模式统一进engine.inc，包括完整的细节处理
- 使用PHPUnit测试对重要的类进行单元测试以提高稳定性
- 框架的安装由php脚本过渡到bash脚本处理

## 结构
~~~
www     WEB部署目录（或者子目录）
├─app                   应用目录...
│
├─public                WEB目录（对外访问目录）
│  ├─index.php          入口文件
│  └─.htaccess          用于apache的重写
│
├─lite   框架目录
│  ├─book/          文档目录
│  ├─composer/      存放composer.json,执行install.sh时将根据OS选择合适的清单文件进行安装
│  ├─core/          核心目录
│  ├─helper/        助手类目录
│  ├─database/      数据库处理库
│  ├─throwable/     预定义的异常和错误库
│  ├─i18n/          语言文件目录        
│  ├─deprecated/    放弃的代码库.等待回收        
│  ├─include/       通用的包含文件
│  ├─install/       安装文件夹（执行install.sh时该目录下的文件被拷贝到相应的位置）    
│  ├─library/       类库
│  ├─template/      模板     
│  ├─service/       内置服务    
│  ├─interface/     预定义接口     
│  ├─test/          phpunit测试库 
│  ├─traits/        通用trait存放
│  ├─vendor/        内置第三方目录
│  ├─engine.inc     框架引擎，命令行或者web模式下用于被包含   
│  ├─install.sh     安装bash脚本         
│  └─phpunit.xml    phpunit配置
│
├─data      数据目录，由程序运行时生成和管理
├─runtime   运行时目录，与数据目录的区别是该目录下的文件可以随意删除
├─vendor    外部第三方目录
└─exec      可执行文件的目录，由lite/install.sh产生
~~~
## URL风格

路由的解析式将path按照'/'拆分，从后往前一次是"操作","控制器","模块"
模块可以分为多级，如"/Admin/Member/Index/index"将访问模块"Admin/Member"下的"Index"的"index"方法
当模块缺失或者控制器缺失时，将指向默认的模块和控制器，如默认的模块和控制器分别是"DefaultModule"和"DefaultController"的情况下，解析结果如下：
```
    # 规定表达式"Modules@Controller/action"表示"模块序列@控制器/操作"
    
    /admin/member/Index/index   => admin/Member@Index/index
    /admin/Index/index          => admin@Index/index
    Index/index                 => DefaultModule@Index/index
    index                       => DefaultModule@DefaultController/index
```
优点是：
当web应用不是很复杂时，可以指定默认模块和控制器以简化url
当web应用组织复杂而需要分多个模块时，可以任意指定模块的嵌入深度(例如"/Admin/Member/Index/index"指定了Admin模块和Member子模块，你可以嵌得更深)

PS:绝大部分应用不需要分模块，也就是说，使用'/ControllerName/actionName'就可以完成大部分业务需求

## 应用组织
应用是由模块组成的，模块将作为一个单独的目录存放到app目录下或者app下的模块目录中

默认的模块是空(模块)，所有的控制器都放在app目录下

模块的组织：
~~~
parent_module/app
├─module    模块目录，决定了url中的大小写
│  ├─controller     控制器目录
│  ├─view           视图目录
│  ├─...            非必要的目录，可以是model（模型）等，可以按照需要组织
~~~
示例：
~~~
app
├─controller 默认模块的控制器
├─view 默认模块的模板库
├─admin
│   ├─controller
│   ├─view
│   ├─...
│   ├─member
│   │   ├─controller
│   │   ├─...
│   │   
│   ├─sign
│   │   ├─controller
│   │   ├─...
│   │   
│   └─system
│       ├─controller
│       ├─...
│     
│ 
└─home
    ├─controller
    │   ├─Index.php #index下面有一个'index'方法，并使用了tait 'Render'
    └─view
        └─Index
            └─index.php #Index控制器的index操作调用调用了Render的display方法后将该html渲染
~~~
例如上面的组织结构存在5个模块，其中'member','sign'和'system'都是'admin'的子模块
只要模块下存在contoller目录并且controller目录存在可以实例化并且存在公共方法的类，那么这个模块是可以访问
 

####　模板引擎
内置模板引擎见　　**core/controller/Render.php** 
``` 
# 内置的模板常量
# __PUBLIC__
'__PUBLIC__' => L_PUBLIC_FULL_URL,

# 模板变量使用 assign 方法分配
# 模板中使用 {{变量名称}}进行使用
例如：
方法中调用
$this->assign('title', '登录');
在模板中可以使用
<title>{{title}}</title>

```
因为模板引擎体积比较小，所以在做前台界面时为了得到好的SEO优化效果，可以使用原生PHP标签


## 升级
目前框架最低支持的PHP版本是7.0
- 在PHP 7.0下，不要将函数的返回值声明为null或者void，在PHP7.1下可以

## 安全
httpd.conf
> ServerTokens Prod
>
> ServerSignature off

php.ini
> expose_php = Off

## 安装
```bash
composer install
```
之后将vender/bin添加到环境变量中

## 其他
####一. 配置
因为配置全部在'public/index.php'中,那么如果遇到配置文件很长的情况,可以将配置写成include的形式,
```php
<?php
require __DIR__ . '/engine.inc';

lite::initialize([
    'show_trace' => true,
    'lite.core.router' => include L_PATH_CONFIG.'router.php',
]);
    lite::start();
```
#### 导出API文档
所有的类全部按照phpdocumentor2进行注释的情况下可以采取下面的命令导出API文档
```bash
./vendor/bin/phpdoc run -d ./core/ -t ./document
```
