#!/usr/bin/env bash

############################ WEB服务优化 ############################

# 修改每个进程可打开的文件数(数据库连接,文件等默认为1024),处于高并发的状态下可能有多个子进程同时需要占用某些资源
# 默认的1024会严重影响网络服务的并发性能
# 注意: 下面的命令对于当前的shell有效,可以写到/etc/profile里面
ulimit -n 4096


# 增加虚拟内存
# 对于大内存的服务器增加虚拟内存只会占用磁盘空间,但对于小内存的服务器是必要的
dd if=/dev/zero of=/home/swap bs=1M count=1024
chmod 0600 /home/swap
mkswap /home/swap
swapon /home/swap