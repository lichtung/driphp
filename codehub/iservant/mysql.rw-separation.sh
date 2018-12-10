#!/usr/bin/env bash

############### mysql读写分离 ###############


# ---------------- 1.安装mariadb ----------------
# 从官网上获取最新的库 https://downloads.mariadb.org/mariadb/repositories/
#       https://downloads.mariadb.org/mariadb/repositories/#mirror=tuna&distro=CentOS&distro_release=centos7-amd64--centos7&version=10.2
echo "
# MariaDB 10.2 CentOS repository list - created 2017-08-20 08:31 UTC
# http://downloads.mariadb.org/mariadb/repositories/
[mariadb]
name = MariaDB
baseurl = http://yum.mariadb.org/10.2/centos7-amd64
gpgkey=https://yum.mariadb.org/RPM-GPG-KEY-MariaDB
gpgcheck=1
" >> /etc/yum.repos.d/MariaDB.repo
yum makecache
yum install MariaDB-server MariaDB-client



# 在主服务器上建立同步帐号
# grant replication client,replication slave on *.* to 'repluser'@'172.16.%.%' identified by 'replpass';
GRANT REPLICATION CLIENT,REPLICATION SLAVE ON *.* TO 'replication'@'192.168.200.%' IDENTIFIED BY '123654';
FLUSH PRIVILEGES;

change master to master_host='192.168.200.100',master_user='replication',master_password='123654',master_log_file='mysql-bin.000003',master_log_pos=326,master_connect_retry=5,master_heartbeat_period=2;
