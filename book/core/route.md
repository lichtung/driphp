# 路由

在介绍路由之前先了解下三个概念：
- 控制器 Controller
- 操作 Action
- 模块 Module

## 控制器和操作
控制器的定义是：
完成一类逻辑业务的操作集合，如用户控制器可以添加和修改用户信息，文章控制器添加和编辑文章。
那么修改用户和删除用户就是操作，这些操作都放在用户控制器里面
而添加文章和编辑文章就是文章控制器的操作
用OOP语言描述就是
```php
<?php
class user{
    /**
    * 添加用户 
    */
    public function add(){}
    /**
    * 删除用户 
    */
    public function delete(){}
}

class article {
    /**
    * 添加文章 
    */
    public function add(){}
    /**
    * 删除文章
    */
    public function edit(){}
}
```
##### 设计技巧：
- user和article控制器都有add和edit方法，尽管操作名称相同，但因为在不同的控制器，具体的含义也就不同（可以避免**addUser/addArticle**这样的名称，保持简洁）
- user和article控制器有方法不想被外部访问，可以将访问修饰符改为**protected**或**private**
- 避免一个控制器调用另一个控制器的方法，可以将共同的操作封装到具体的逻辑类中，控制器共同调用该逻辑类

## 模块
模块是对控制器的进一步归类，大型系统中通常包含多个模块或者模块嵌套的情况

如后台模块(admin)下有用户模块(user)，用户模块下用户验证(auth)作为一个独立的控制器
那么编写下面的类
```php
<?php
namespace demo\controller\admin\user;

class auth {
    public function index(){ }
}
```
输入地址[http://demo.me/index.php/admin/user/auth/index](http://demo.me/index.php/admin/user/auth/index)
便可以访问index操作


- Module 模块
URL映射规则
swoole框架使用强规则来做URL映射。如下面的URL

http://127.0.0.1/user/index/
将会映射到 [project_name]/controller/user.php 中的 user::index 方法。(控制器大小写与url中大小写一致)