# 数据库访问对象
数据访问对象(Data Access Object)是一个数据访问接口，它将所有的数据库访问操作抽象封装到一个公共的API中，，即通过一个统一的接口操作数据库。DAO关心数据的访问和操作，这是和Model相区别的地方

大型系统可能需要操作多个数据库，那么可以创建多个DAO访问各个数据库。

现在要介绍的InFrame框架的DAO实现：

### 一、获取DAO对象
连接数据库的第一步是获取数据库连接
```php
use driphp\db\Dao;
use driphp\db\adapter\MySQL;
use driphp\throws\db\ConnectException;
try {
    $dao = Dao::connect(MySQL::class, [
        'dbname' => 'dbname',
        'user' => 'username',
        'passwd' => '123456',
        'host' => '127.0.0.1',
        'port' => '3306',
        'charset' => 'UTF8',
    ]);
    
    // ...
    
} catch (ConnectException $exception) {
    // 处理数据库连接失败的情况
}
```
调用 **Dao::connect** 成功连接数据库的时候的返回值是一个DAO对象

因其他原因数据库连接失败时间抛出一个ConnectException

### 二、执行SQL
执行(execute)一段SQL返回的是受到影响的记录数目

PDO执行SQL可以通过PDO/PDOStatement的两个方法 **PDO::exec()** 和 **PDOStatement::rowCount()** 实现，官方文档的描述分别是：

###### 1.PDO::exec()
**PDO::exec()** 返回被修改或者删除的记录数目，没有记录被删除时返回0。
此外当表、外键等非记录被删除时候返回的依然是0
>PDO::exec() returns the number of rows that were modified or deleted by the SQL statement you issued. If no rows were affected, PDO::exec() returns 0.

###### 2.PDOStatement::rowCount()
**PDOStatement::rowCount()** 返回情况与 **PDO::exec()** 完全相同
> PDOStatement::rowCount() returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed by the corresponding PDOStatement object.

```php
use driphp\db\Dao;
use driphp\db\adapter\MySQL;
use driphp\throws\db\ExecuteException;
// 获取 Dao 实例 $dao

try {

    // 执行一段简单的SQL
    $countRow = $dao->exec('insert into tba (`name`) VALUE (\'linzh\');');
    
    // 执行一段SQL并作防注入处理
    $countRow = $dao->exec('insert into tba (`name`) VALUE (:name);', [
        ':name' => 'linzhv',
    ])
    
} catch (ExecuteException $exception) {
    // 处理数据库执行失败的情况
}
```
调用Dao对象的exec方法返回的是一个int值表示受到影响的行数，SQL执行出错会抛出一个ExecuteException异常

### 三、查询SQL
查询SQL的执行结果是返回数据（数组形式），如果需要返回浮标形式的结果，可以使用原生的PDO对象进行查询

每个DAO对象创建时都会创建一个PDO对象连接数据库，**DAO::getPdo()** 方法可以获取内置的PDO对象
```php
/** @var PDO $pdo 获取DAO内置的PDO对象 */
$pdo = $dao->gerPdo();
```

DAO另外封装了query方法用于查询：
```php

use driphp\db\Dao;
use driphp\db\adapter\MySQL;
use driphp\throws\db\QueryException;

// 获取 Dao 实例 $dao

try {
    // 简单的通过SQL查询
    $list = $dao->query('select * from tba ;');
    
    // 查询时绑定输入参数防止注入
    $list2 = $dao->query('select * from tba where name = :name;',[
        ':name' => 'linzhv',
    ]);
    
} catch (QueryException $exception) {
    // 处理数据库查询失败的情况
}
```
调用DAO的query方法返回的是一个数组形式的数据列表，查询出错时会抛出一个QueryException异常



### 四、异常
现注意到无论是连接数据库，查询和执行SQL语句都会抛出异常，他们分别是 连接异常（ConnectException）、查询异常（QueryException）、执行异常（ExecuteException），这些异常都继承数据库异常（DatabaseException），所以你嫌弃不想区分可以使用下面的形式统一捕获
```php
use driphp\throws\DatabaseException;
try{
    // 下面是连接数据库的代码，可能抛出连接连接异常 ConnectionException
    
    // 下面是执行SQL的代码，可能抛出执行异常 ExecuteException
    
    // 下面是查询SQL的代码，可能跑出查询异常 QueryException
    
} catch (DatabaseException  $exception) {
    // 统一处理数据库连接、查询、执行异常 
}

```
集中处理不同原因导致的错误可能不是一个好的做法
下面的写法是比较合适
```php
try{
    // 下面是连接数据库的代码，可能抛出连接连接异常 ConnectionException
    
    // 下面是执行SQL的代码，可能抛出执行异常 ExecuteException
    
    // 下面是查询SQL的代码，可能跑出查询异常 QueryException
    
} catch (ConnectionException  $exception) {
    // 统一处理数据库连接异常 
} catch (ExecuteException  $exception) {
    // 统一处理数据库执行异常 
} catch (QueryException  $exception) {
    // 统一处理数据库查询异常 
}
```
最后关键的来了！为什么不采取返回false的形式来判断是否发生了错误呢

原因有几个：

##### PHP版本更新到7以后增加了强类型限制，这虽然增加了学习难度，但是对项目的整体的提升有极大的好处，如Dao::exec()方法查询的结果我们期望的是一个int类型表示修改的数据列数，那么再返回一个false就显得及不合理。 

PHP7的强类型示例如下：

```php
public function query(string $sql, array $params = null): array;

public function exec(string $sql, array $params = null): int;
```

因为返回值分别限制称了array和int，在调用这些方法的时候就不用考虑时空数组还是0还是false，只需要在末尾捕获即可

##### 异常的另一个好处就是可以再构造方法中添加一些代码，当异常发生时就会new一个异常对象，这个时候就会调用到构造方法中的这些代码。
你可以再这些代码中加入记录日志，发送debug邮件给管理员等等。
而使用传统的false判断，那么就不得不加上记录日志或者发送邮件的代码。

PS：PHP异常机制机制完全是学习Java的，关于这方面的资料可以参考Java编程书籍。

### 五、事务
说道数据库就不能少了事务，事务有基本的四个方法，原型分别时：
```php
// 开启事务
public function beginTransaction() : bool ;
// 提交事务
public function commit() : bool ;
// 回滚事务
public function rollback() : bool ;
// 判断事务是否开启
public function inTransaction() : bool ;
```

### 六、获取执行历史
可以通过DAO类的静态方法 **getLastSql** 和 **getLastParams** 获取最近一次查询／执行的SQL语句和输入参数（绑定参数）
```php
$sql = Dao::getLastSql();
$params = Dao::getLastParams();
```
另外通过该方法可以获取全部执行过的SQL和输入参数
```php
$sql = Dao::getLastSql(true);
$params = Dao::getLastParams(true);
```

### 七、获取最近一次插入数据的ID
当调用 **Dao::exec()** 执行insert操作时，可以调用 **Dao::lastInsertId()** 方法获取insert操作插入的数据ID，但前提时该字段是自增的（Auto Increment）
```php
$insert_id = $dao->lastInsertId();
```
