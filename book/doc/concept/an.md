# 匿名类
```php
<?php

$invoke = new class extends stdClass implements Countable
{
    public function count() {
        return 0;
    }
};
```