# 简介
lite是一款php轻型框架,框架提供了最基本的功能（不断地完善中...）
即便如此,按需加载地特性使得该框架的性能依然接近原生
规范的命名可以使开发具有便捷性


只需要知道下面的特性就可以快速使用原生php进行开发：

- 类的命名完全基于PSR-0,可以更具类名快速找到它的位置以及初步判断它的作用
如类名"app\admin\controller\user"可以判断这个类是应用(app)后台(admin)账户(user)相关的控制器(controller)
- URL访问在thinkphp的基础上引入了模块嵌套的概念
如路径"/ctler/action"将调用ctler类的action方法(此时模块名为空)
路径"/admin/ctler/action"讲调用admin模块下的ctler类的action方法(此时模块为admin)
路径"/admin/product/ctler/action"讲调用app.admin模块ctler类的action方法(此时模块为admin下面的子模块product)
模块和子模块之间强制要求如何嵌套,根据业务需要可以无限嵌套
**详细可参考URL章节**

## 常量

使用常量代替直接的字符串好处是在IDE环境下可以快速地输入,非IDE环境下可以尽量在测试阶段暴露问题

框架内部所有的常量都以"L_"为前缀,以避免在和其他框架同时引入时名称冲突

### 普通常量
|名称|类型 |说明 |
|-----|------|-----|
|L_VERSION          |float  |框架版本|
|L_APP_NAME         |string |应用目录的名称|
|L_DEBUG_MODE_ON    |bool   |debug模式是否开启|
|L_NOW_MICRO        |float  |请求时的微秒时间|
|L_NOW              |int    |请求时的时间戳|
|L_NOW              |int    |请求时的时间戳|
|L_MEM              |int    |bytes单位计算的初始内存|
|L_IS_WIN          |bool  |当前系统是否是windows|
|L_IS_CLI          |bool  |当前是否是命令行模式|

### 路径常量
|名称|类型 |说明 |
|-----|------|-----|
|L_PATH_BASE          |string  | 项目基础目录(lite框架所在的目录)|
|L_PATH_FRAMEWORK          |string  | 框架目录|
|L_PATH_CONFIG          |string  | 配置目录|
|L_PATH_RUNTIME          |string  | 运行时目录|
|L_PATH_PUBLIC          |string  | 公共目录,url可以访问的目录|
|L_PATH_VENDOR          |string  | 第三方库目录|
|L_PATH_APP          |string  | 应用目录|

### WEB模式常量
|名称|类型 |说明 |
|-----|------|-----|
|L_IS_AJAX          |bool  |当前请求是否是ajax|
|L_REQUEST_METHOD          |bool  |当前请求的方法:post,get|
|L_IS_POST          |bool  |当前请求是否是post方法|
|L_PUBLIC_URL          |bool  |公共访问路径(不包含主机名)|
|L_PUBLIC_FULL_URL     |bool  |公共访问路径(包含主机名)|
|L_IS_HTTPS          |bool  |当前请求是否https协议|
|L_HOST_URL          |bool  |当前请求的主机:https://www.litera.com|


### 语言常量
|名称|类型 |说明 |
|-----|------|-----|
|L_LANG_ZH_CN          |string  |简体中文|
|L_LANG_ZH_TW         |string |繁体中文|
|L_LANG_EN_US    |string   |英语(美国)|
|L_LANG_ZH              |string    |中文|
|L_LANG_EN              |string    |英语|


### 编码常量
|名称|类型 |说明 |
|-----|------|-----|
|L_CHARSET_UTF8          |string  |UTF8|
|L_CHARSET_GBK         |string |GBK|
|L_CHARSET_ASCII    |string   |ASCII|
|L_CHARSET_GB2312              |string    |GB2312|
|L_CHARSET_LATIN1              |string    |LATIN1|

### 数据类型常量

|名称|类型 |说明 |
|-----|------|-----|
|L_TYPE_BOOL          |string  ||
|L_TYPE_INT         |string ||
|L_TYPE_FLOAT    |string   ||
|L_TYPE_STR              |string    ||
|L_TYPE_ARRAY              |string    ||
|L_TYPE_OBJ          |string  ||
|L_TYPE_RESOURCE         |string ||
|L_TYPE_NULL    |string   ||
|L_TYPE_UNKNOWN              |string    | |


### 时间常量
|名称|类型 |说明 |
|-----|------|-----|
|L_ONE_HOUR          |int  |一小时的秒数 |
|L_ONE_DAY         |int |一天的秒数 |
|L_ONE_WEEK    |int   | 一周的秒数|
|L_ONE_MONTH              |int    | 一月的秒数|

### 其他常量
这些常量通常用作数组的键,怕打错才将它们设定为常量

|名称|类型 |说明 |
|-----|------|-----|
|DAO_DSN          |string  | dsn |
|DAO_HOST          |string  | 数据库主机|
|DAO_PORT          |string  | 数据库端口|
|DAO_DBNAME          |string  | 数据库名|
|DAO_USERNAME          |string  | 数据库用户名|
|DAO_PASSWORD          |string  | 数据库密码|
|DAO_CHARSET          |string  | 数据库编码|
|DAO_OPTIONS          |string  | 数据库选项|
|L_ADAPTER_CLASS          |string  | 适配器类列表(Component类)|
|L_ADAPTER_CONFIG          |string  | 适配器配置列表(Component类)|


## 类自动加载autoload
所有的类加载按照"PSR-0"规范进行

比如定义了下面一个类
```php
<?php
namespace app\admin\library;

class Demo{}
```
那么这个类的路径应该如下 "** ~/app/admin/library/Demo.php **" ("~"表示项目根目录)

即将命名空间分隔符转为目录分隔符并加上".php"后缀即对应着类的相对路径(相对于项目)

## 组件Component
组件是一类实例化的时候自动读取配置的类

配置存放于文件"/config/lite.php"中,如:
```php
<?php
return [
    'lite.core.router' => [
        // ... 这里是类 lite\core\Router 的配置参数
    ],
    'lite.database.dao' => [
        // ... 这里是类 lite\database\Dao 的配置参数
    ],
];
```

配置可以在入口文件"index.php"中,只要将'use_config_bundle'配置为false(默认为true)
如:
```php
<?php
require __DIR__ . '/../lite/engine.inc';
lite::initialize([
    'use_config_bundle' => false, # 将配置
    'lite.core.router' => [
        // ... 这里是类 lite\core\Router 的配置参数
    ],
    'lite.database.dao' => [
        // ... 这里是类 lite\database\Dao 的配置参数
    ],
]);
```
将类名称全部小写并且把分隔符'\'转为'.',即得到组件的配置名称,之后组件类将在每一次在实例化时自动读取配置

