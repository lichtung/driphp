<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 15:48
 */
declare(strict_types=1);


namespace driphp\core\redis;

use driphp\throws\core\RedisException;

/**
 * Class Hash
 * @package driphp\core\redis
 */
class Hash extends Structure
{

    /**
     * Adds a value to the hash stored at key.
     * @param string $key
     * @param mixed $value
     * @param bool $setOnlyNotExist Adds a value to the hash stored at key only if this field isn't already in the hash.
     * @return bool TRUE if the field was set, FALSE if it was already present while $setOnlyNotExist is TRUE.
     *              TRUE if value didn't exist and was added successfully, FALSE if the value was already present and was replaced while $setOnlyNotExist is default to false
     * @throws RedisException When Add a value to the hash failed
     */
    public function set(string $key, string $value, bool $setOnlyNotExist = false): bool
    {
        if ($setOnlyNotExist) {
            return $this->adapter->hSetNx($this->name, $key, $value);
        } else {
            $res = $this->adapter->hSet($this->name, $key, $value);
            if ($res === false) {
                $this->context->checkError();
            }
            return $res > 0;
        }
    }

    /**
     *
     * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings
     * @param   array $pairs key → value array
     * @param   bool $clean It will clean the hash table before setting if set to true
     * @link    http://redis.io/commands/hmset
     * @example
     * <pre>
     * $redis->delete('user:1');
     * $redis->hMset('user:1', array('name' => 'Joe', 'salary' => 2000));
     * $redis->hIncrBy('user:1', 'salary', 100); // Joe earns 100 more now.
     * </pre>
     * @return bool
     */
    public function setInBatch(array $pairs, bool $clean = false): bool
    {
        $clean and $this->adapter->delete($this->name);
        return $this->adapter->hMset($this->name, $pairs);
    }

    /**
     * Gets a value from the hash stored at key.
     * @param string $key
     * @param mixed $replace
     * @return string
     */
    public function get(string $key = '', string $replace = ''): string
    {
        $value = $this->adapter->hGet($this->name, $key); # If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
        return false === $value ? $replace : $value;
    }

    /**
     * Check a key is exist in hash table
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return false !== $this->adapter->hGet($this->name, $key);
    }

    /**
     *
     * Retirieve the values associated to the specified fields in the hash.
     * OR
     * Returns the whole hash, as an array of strings indexed by strings.
     *
     * <pre>
     *
     * $redis->delete();
     * $redis->set( 'field1', 'value1');
     * $redis->set( 'field2', 'value2');
     * $redis->set( 'field3', 'value3');
     *
     *  $redis->getAll(); ==>
     *      [
     *          'field1'    =>  'value1',
     *          'field2'    =>  'value2',
     *          'field3'    =>  'value3',
     *      ]
     *
     *  $redis->getAll(array('field1', 'field2')); ==>
     *      [
     *          'field1'    =>  'value1',
     *          'field2'    =>  'value2',
     *      ]
     *
     *
     * </pre>
     *
     * @param string ...$keys
     * @return  array   An array of elements, the contents of the hash.
     * @link    http://redis.io/commands/hgetall
     * @return array
     */
    public function getAll(string ...$keys): array
    {
        return $keys ? $this->adapter->hMGet($this->name, $keys) : $this->adapter->hGetAll($this->name);
    }

    /**
     *
     * Removes a values from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @link    http://redis.io/commands/hdel
     * @example
     * <pre>
     * $redis->hMSet('h',[
     *     'f1' => 'v1',
     *     'f2' => 'v2',
     *     'f3' => 'v3',
     *     'f4' => 'v4',
     * ]);
     * var_dump( $redis->hDel('h', 'f1') );        // int(1)
     * var_dump( $redis->hDel('h', 'f2', 'f3') );  // int(2)
     * var_dump( $redis->hGetAll('h') );
     * //// Output:
     * //  array(1) {
     * //    ["f4"]=> string(2) "v4"
     * //  }
     * </pre>
     *
     *
     * @param string ...$keys It will delete all if parameter is none
     * @return int Number of deleted fields
     */
    public function delete(string ...$keys): int
    {
        if (empty($keys)) {
            return $this->adapter->delete($this->name);
        } else {
            array_unshift($keys, $this->name);
            return call_user_func_array([$this->adapter, 'hDel'], $keys); # Number of deleted fields
        }
    }


    /**
     * Returns the length of a hash, in number of items
     * @return  int     the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
     * @throws RedisException
     */
    public function length(): int
    {
        $length = $this->adapter->hLen($this->name);
        if (false === $length) throw new RedisException("Item '$this->name' not exist or is not a hash");
        return $length;
    }

    /**
     * Returns the keys in a hash, as an array of strings.
     *
     * @return  array   An array of elements, the keys of the hash. This works like PHP's array_keys().
     * @link    http://redis.io/commands/hkeys
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     *
     * $redis->hKeys('h') ==> [
     *  'a','b','c','d',
     * ]
     * # The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     * @return array
     */
    public function keys(): array
    {
        return $this->adapter->hKeys($this->name);
    }

    /**
     * Returns the values in a hash, as an array of strings.
     *
     * @return  array   An array of elements, the values of the hash. This works like PHP's array_values().
     * @link    http://redis.io/commands/hvals
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * $redis->hVals('h') ==> [
     *  'x','y','z','t',
     * ]
     * # The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     * @return array
     */
    public function values(): array
    {
        return $this->adapter->hVals($this->name);
    }
}