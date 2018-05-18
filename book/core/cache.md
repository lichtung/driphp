# 缓存 Cache

缓存是数据交换的缓冲区，用来存放一些数据资源。
当某一个计算过程需要使用数据时候，会先试着从缓存中读取，如果缓存中无法获取数据，重新计算获取数据并放到缓存中，
下次直接使用这些计算结果，可以免去不必要的资源占用。

>是否将数据缓存要参考下面两个标准:
>- 数据的更新频率 
如果数据的实时性要求很高，那么建立缓存是不必要的，因为花在建立缓存的计算资源也会是庞大的开支。
>- 数据的访问频率
输入数据很少被访问到，那么建立缓存只会花费不必要的资源空间。

同时满足较高的更新频率和访问率的情况，那么建立缓存可以极大地提升应用性能。

> 微信公众号菜单的更新策略
(每次在关注公众号时候，以及用户在24小时候再次打开公众号时候，客户端会重新从服务端获取一次最新的菜单)



## 使用

### 示例
下面展示了使用redis的取出键"key01"的值
```php
<?php
use dripex\core\Cache;
$cache = Cache::getInstance('redis');
$value = $cache->get('key01', 'value01');
```

### 获取实例
使用Cache类的"getInstance"方法可以获取驱动实例,getInstance接受一个参数表示使用什么**驱动**来处理该缓存数据

目前支持的驱动包括:
- redis 使用redis缓存数据
- file  使用文件系统缓存数据

下面拿到了两个缓存类实例
```php
<?php
use dripex\core\Cache;

# 获取默认的实例(基于redis)
$cache = Cache::getInstance();
# 获取基于文件系统的Cache实例
$file = Cache::getInstance('file');
# 获取基于redis的实例
$redis = Cache::getInstance('redis');
```
这两个实例拥有一样的方法,一样的操作结果,区别在于他们使用的**驱动**不同,

$file键缓存保存在文件中,而$redis依靠Redis服务器进行处理,这决定了这两个实例的操作效率的高低

### 设置缓存
使用实例的set方法可以设置缓存

它的原型是:
> bool set(string $name, $value, int $expire = L_ONE_HOUR)
- $name     string类型,表示缓存名称
- $value    缓存的值,可以是除了对象和recource类型以外的所有数据类型
- $expire   缓存期,缓存期内数据都可以通过实例获取


```php
<?php
use dripex\core\Cache;
$cache = Cache::getInstance();
# 比如缓存名称叫'cache_name_1',可以使用下面的代码设置它的值是一个数组,并缓存5天的时间(L_ONE_DAY 表示一天的秒数)
$cache->set('cache_name_1',['this is value'], 5 * 24 * DRIP_ONE_HOUR );
```


### 获取缓存
在知道了缓存名称的前提下,可以使用实例get方法

方法的原型是:
>mixed get(string $name, $replace = null, int $expire = L_ONE_HOUR)

比如缓存名称叫'cache_name_1',可以使用下面的代码获取它的值
```php
<?php
use dripex\core\Cache;
$cache = Cache::getInstance();
$value = $cache->get('cache_name_1');
```
如果缓存不存在,则返回null,如果希望缓存不存在时返回其他值(比如返回提示字符串"数据不存在"),可以设置参数二来设置默认返回值
```php
<?php
use dripex\core\Cache;
$cache = Cache::getInstance();
$value = $cache->get('cache_name_1','数据不存在');
```
参数二可以是任何类型,缓存不存在都将被返回,除了闭包类型(Closure),

如果参数二是闭包,那么缓存不存在时将执行这个闭包,并将结果缓存并返回,缓存的时间是参数三(默认为一小时)
```php
<?php
use dripex\core\Cache;
$cache = Cache::getInstance();
# 下面的代码将呈现下面的过程:
#   如果"cache_name_1"的缓存不存在,则执行闭包并将闭包的执行结果缓存5天的时间,之后在将这个新设置的缓存返回
#   下次继续访问"cache_name_1"的缓存,只要是不超过5天,那么就会把缓存直接返回,否则继续执行闭包并设置缓存
$value = $cache->get('cache_name_1',function(){
    $value = null;
    // 这里省略一堆内容..........
    return $value;
},5 * 24 * DRIP_ONE_HOUR );


# 上面的调用与下面的过程等效
$value = $cache->get('cache_name_1');
if(null === $value){
    $value = null;
    // 这里省略一堆内容..........
    $cache->set('cache_name_1',$value,5 * 24 * DRIP_ONE_HOUR );
}
```

### 清楚缓存
使用实例clean,cleanAll方法可以请出缓存数据

根据英文含义可以知道clean指清楚指定的缓存,而cleanAll表示清除所有(谨慎使用)

方法原型:
> bool clean(string $name)

> bool cleanAll()





