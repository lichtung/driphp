# 什么是面向对象
简单的一句话概括就是：
将业务中涉及到的特定数据和对数据的操作封装到对象中，数据是对象的属性，操作是对象的方法
数据不同但是操作方法相同的对象可以向上抽象出类，类之间有继承的关系，子类可以使用父类非私有的属性和方法
面向对象的另一个特性多态用得比较少，主要用在函数或者方法的参数和返回值，如：
```php
<?php
class Father{
    public function __toString()
    {
        return 'I\'m ' . static::class . PHP_EOL;
    }
}

class Child extends Father {}

function foo(Father $father): Father
{
    return $father;
}
function bar(bool $isFather): Father
{
    return $isFather?new Father():new Child();
}

echo foo(new Father('Lia')); # I'm Father
echo foo(new Child('Lia')); # I'm Child
echo bar(true); # I'm Father
echo bar(false); # I'm Child
```
可以看到：
- foo函数接受一个Father对象作为参数，但是可以传入Father对象或者是Child对象
- bar函数要求返回一个Father对象，但是可以返回Father对象或者是Child对象

这是因为Child继承了Father（"IS-A"关系），Father公开的东西（属性和方法）Child也全都有并且公开，那么Child便可以作为一个Father对象使用
但是Father不可以被当作Child使用，因为Father可能没有Child公开的一些东西，那么Father就不能代替Child
