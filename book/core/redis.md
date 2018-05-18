# Redis

Redis 是当今最流行的 nosql 解决方案, 与Memcache类似，它将数据存储在内存中，因此与Memcache一样具有较高的性能
可以使用下面的命令测试单机综合性能
```bash
redis-benchmark
>====== PING_INLINE ======
   100000 requests completed in 1.56 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.21% <= 1 milliseconds
 99.96% <= 2 milliseconds
 100.00% <= 2 milliseconds
 64184.86 requests per second

 ====== PING_BULK ======
   100000 requests completed in 1.60 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.32% <= 1 milliseconds
 99.96% <= 2 milliseconds
 100.00% <= 2 milliseconds
 62578.22 requests per second

 ====== SET ======
   100000 requests completed in 1.61 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 98.47% <= 1 milliseconds
 99.95% <= 2 milliseconds
 100.00% <= 2 milliseconds
 62150.41 requests per second

 ====== GET ======
   100000 requests completed in 1.60 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.57% <= 1 milliseconds
 100.00% <= 2 milliseconds
 100.00% <= 2 milliseconds
 62656.64 requests per second

 ====== INCR ======
   100000 requests completed in 1.57 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.55% <= 1 milliseconds
 100.00% <= 1 milliseconds
 63734.86 requests per second

 ====== LPUSH ======
   100000 requests completed in 1.58 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.11% <= 1 milliseconds
 100.00% <= 2 milliseconds
 100.00% <= 2 milliseconds
 63451.78 requests per second

 ====== RPUSH ======
   100000 requests completed in 1.57 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.46% <= 1 milliseconds
 99.97% <= 2 milliseconds
 100.00% <= 2 milliseconds
 63532.40 requests per second

 ====== LPOP ======
   100000 requests completed in 1.59 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.16% <= 1 milliseconds
 99.99% <= 2 milliseconds
 100.00% <= 2 milliseconds
 62774.64 requests per second

 ====== RPOP ======
   100000 requests completed in 1.59 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.19% <= 1 milliseconds
 99.98% <= 2 milliseconds
 100.00% <= 2 milliseconds
 62853.55 requests per second

 ====== SADD ======
   100000 requests completed in 1.59 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 98.60% <= 1 milliseconds
 99.95% <= 2 milliseconds
 100.00% <= 2 milliseconds
 63011.97 requests per second

 ====== HSET ======
   100000 requests completed in 1.57 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.32% <= 1 milliseconds
 99.97% <= 2 milliseconds
 100.00% <= 2 milliseconds
 63613.23 requests per second

 ====== SPOP ======
   100000 requests completed in 1.58 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.41% <= 1 milliseconds
 99.99% <= 2 milliseconds
 100.00% <= 2 milliseconds
 63492.06 requests per second

 ====== LPUSH (needed to benchmark LRANGE) ======
   100000 requests completed in 1.58 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 99.43% <= 1 milliseconds
 99.98% <= 2 milliseconds
 100.00% <= 2 milliseconds
 63411.54 requests per second

 ====== LRANGE_100 (first 100 elements) ======
   100000 requests completed in 6.98 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 0.05% <= 1 milliseconds
 93.41% <= 2 milliseconds
 99.91% <= 3 milliseconds
 100.00% <= 4 milliseconds
 100.00% <= 4 milliseconds
 14328.70 requests per second

 ====== LRANGE_300 (first 300 elements) ======
   100000 requests completed in 14.58 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 0.01% <= 1 milliseconds
 0.07% <= 2 milliseconds
 1.15% <= 3 milliseconds
 94.84% <= 4 milliseconds
 99.81% <= 5 milliseconds
 99.98% <= 6 milliseconds
 100.00% <= 7 milliseconds
 100.00% <= 7 milliseconds
 6860.59 requests per second

 ====== LRANGE_500 (first 450 elements) ======
   100000 requests completed in 20.29 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 0.00% <= 1 milliseconds
 0.02% <= 2 milliseconds
 0.14% <= 3 milliseconds
 1.18% <= 4 milliseconds
 50.35% <= 5 milliseconds
 98.69% <= 6 milliseconds
 99.77% <= 7 milliseconds
 99.93% <= 8 milliseconds
 100.00% <= 9 milliseconds
 100.00% <= 9 milliseconds
 4928.78 requests per second

 ====== LRANGE_600 (first 600 elements) ======
   100000 requests completed in 25.25 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 0.00% <= 1 milliseconds
 0.00% <= 2 milliseconds
 0.01% <= 3 milliseconds
 0.08% <= 4 milliseconds
 0.45% <= 5 milliseconds
 31.71% <= 6 milliseconds
 97.33% <= 7 milliseconds
 99.71% <= 8 milliseconds
 99.91% <= 9 milliseconds
 99.97% <= 10 milliseconds
 99.99% <= 11 milliseconds
 99.99% <= 12 milliseconds
 100.00% <= 13 milliseconds
 100.00% <= 13 milliseconds
 3961.18 requests per second

 ====== MSET (10 keys) ======
   100000 requests completed in 2.38 seconds
   50 parallel clients
   3 bytes payload
   keep alive: 1

 48.16% <= 1 milliseconds
 99.82% <= 2 milliseconds
 100.00% <= 2 milliseconds
 42052.14 requests per second

```

### 获取实例

