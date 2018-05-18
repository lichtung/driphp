#!/usr/bin/env bash
yum update -y
# install mysql
mysql_dir="/home/srv/mariadb"
mysql_installdir="/home/srv/install/mariadb"
mysql_datadir="/home/srv/mariadb/data"
mysql_logdir="/home/srv/mariadb/data/log"
mysql_passwd="lich4tung"

source ../../recycle/srv/_include.sh

yum groupinstall -y Development Tools
# ubuntu : libncurses5-dev
yum install -y cmake ncurses-devel openssl-devel openssl
cd /root/
# 客户可能已经存在
useradd -M -s /sbin/nologin mysql
mkdir -p ${mysql_dir}
chown mysql.mysql -R ${mysql_dir}
mkdir -p ${mysql_datadir}
chown mysql.mysql -R ${mysql_datadir}
echo "[client]
port            = 3306
socket          = /tmp/mysql.sock
[mysqld]
port            = 3306
socket          = /tmp/mysql.sock
skip-external-locking
key_buffer_size = 12M
max_allowed_packet = 1M
table_open_cache = 32M
sort_buffer_size = 1M
read_buffer_size = 1M
read_rnd_buffer_size = 2M
myisam_sort_buffer_size = 32M
thread_cache_size = 4M
query_cache_size= 8M
thread_concurrency = 2
datadir = /mydata/data
innodb_file_per_table = on
skip_name_resolve = on
[mysqldump]
quick
max_allowed_packet = 8M
[mysql]
no-auto-rehash
[myisamchk]
key_buffer_size = 32M
sort_buffer_size = 32M
read_buffer = 1M
write_buffer = 1M
[mysqlhotcopy]
interactive-timeout" > ${mysql_dir}/my.cnf
download ${mysql_installdir}/mariadb-10.1.21.tar.gz  "https://downloads.mariadb.org/f/mariadb-10.1.21/source/mariadb-10.1.21.tar.gz/from/http%3A//mirrors.tuna.tsinghua.edu.cn/mariadb/?serve"

tar zxf mariadb-10.1.21.tar.gz
cd mariadb-10.1.21
cmake . -DCMAKE_INSTALL_PREFIX=${mysql_dir}/  -DMYSQL_DATADIR=${mysql_datadir} \
-DWITH_INNOBASE_STORAGE_ENGINE=1 -DWITH_ARCHIVE_STORAGE_ENGINE=1 -DWITH_BLACKHOLE_STORAGE_ENGINE=1 -DENABLED_LOCAL_INFILE=1 -DMYSQL_TCP_PORT=3306 -DWITH_SSL=system -DWITH_ZLIB=system -DWITH_LIBWRAP=0 -DCMAKE_THREAD_PREFER_PTHREAD=1 -DEXTRA_CHARSETS=all -DDEFAULT_CHARSET=utf8 -DDEFAULT_COLLATION=utf8_general_ci -DMYSQL_UNIX_ADDR=/tmp/mysql.sock -DWITH_DEBUG=0


# How to Fix PHP Configure “CC Internal error Killed (program cc1)” Error
# http://linux.101hacks.com/unix/fix-php-cc-internal-errror-killed/
# 增加虚拟内存

make && make install
rm -rf /etc/my.cnf
rm -rf /etc/init.d/mysqld

cp /root/my.cnf /etc/my.cnf
cp support-files/mysql.server /etc/init.d/mysqld
chmod a+x /etc/init.d/mysqld
chkconfig --add mysqld
chkconfig mysqld on
chown mysql.mysql -R ${mysql_logdir}
chown mysql.mysql -R ${mysql_datadir}
${mysql_dir}/scripts/mysql_install_db --user=mysql --basedir=${mysql_dir} --datadir=${mysql_datadir}
systemctl start mysqld
echo 'export PATH=$PATH:'${mysql_dir}'/bin' >> /etc/profile
source "/etc/profile"
${mysql_dir}/bin/mysql -e "grant all privileges on *.* to root@'%' identified by '$mysql_passwd' with grant option;"
${mysql_dir}/bin/mysql -e "flush privileges;"
${mysql_dir}/bin/mysql -e "delete from mysql.user where password='';"
systemctl mysqld restart
echo "mysql install success!"


############################################################################################
############################################################################################
############################################################################################
#
#yum update -y
#yum groupinstall -y Development Tools
#yum install -y cmake ncurses-devel openssl-devel openssl
#
#CURRENT_DIR=`pwd`
#function addpath(){
#    echo "export PATH=\${PATH}:${1}" >> /etc/profile
#    source /etc/profile
#}
#function buildir(){
#    # check parent dir
#    parentdir=`dirname $1`
#    if [ ! -d ${parentdir} ]; then
#        println "to make parentdir ${parentdir}"
#        buildir ${parentdir}
#    fi
#    # make dir
#    if [ ! -d $1 ]; then
#        println "to make dir ${1}"
#        mkdir $1
#    else
#        println "to make dir ${1}:exist"
#    fi
#}
## replace text in certain line
## @param int $line_number
## @param string $text2replace
## @param string $textfilepath
#function line_replace(){
#    sed -i "${1}c ${2}" ${3}
#}
#
## insert text into text file
## @param int $line_number
## @param string $text2insert
## @param string $textfilepath
#function iinsert(){
#    if [ ! -f ${3} ]; then
#        touch ${3}
#    fi
#    sed -i "${1}a ${2}" ${3}
#}
#function println(){
#    echo -e "|---- ${1}"
#}
#function copy(){
#    if [ ! -f $2 ]; then
#        println "file '${1}' begin copy";
#        cp -R $1 $2
#    else
#        println "file '${1}' exist, stop copy";
#    fi
#}
#function download(){
#    buildir `dirname $1`
#    if [ ! -f $1 ]; then
#        println "file '${1}' begin download";
#        wget $2 -O $1
##        curl -o $1 -L --connect-timeout 100 -m 200 $2
#    else
#        println "file '${1}' exist, stop download";
#    fi
#}
#
##install mysql
#SRV_HOME=/home/srv
#MARIADB_PORT=3306
#ROOT_PWD=PgYXWD147852
#MARIADB_HOME="${SRV_HOME}/mariadb"
#MARIADB_DATA_HOME="${MARIADB_HOME}/data"
#MARIADB_LOG_HOME="${MARIADB_HOME}/log/"
#INSTALL_HOME=${SRV_HOME}/install
#
#useradd -M -s /sbin/nologin mysql
#buildir ${INSTALL_HOME}
#buildir ${MARIADB_DATA_HOME}
#buildir ${MARIADB_LOG_HOME}
#chown -R mysql.mysql ${MARIADB_HOME}
#cd ${INSTALL_HOME}
#echo "[client]
#port            = 3306
#socket          = /tmp/mysql.sock
#[mysqld]
#port            = 3306
#socket          = /tmp/mysql.sock
#skip-external-locking
#key_buffer_size = 12M
#max_allowed_packet = 1M
#table_open_cache = 32M
#sort_buffer_size = 1M
#read_buffer_size = 1M
#read_rnd_buffer_size = 2M
#myisam_sort_buffer_size = 32M
#thread_cache_size = 4M
#query_cache_size= 8M
#thread_concurrency = 2
#datadir = /mydata/data
#innodb_file_per_table = on
#skip_name_resolve = on
#[mysqldump]
#quick
#max_allowed_packet = 8M
#[mysql]
#no-auto-rehash
#[myisamchk]
#key_buffer_size = 32M
#sort_buffer_size = 32M
#read_buffer = 1M
#write_buffer = 1M
#[mysqlhotcopy]
#interactive-timeout" > /etc/my.cnf
#download ${INSTALL_HOME}/mariadb-10.1.21.tar.gz  "https://downloads.mariadb.org/f/mariadb-10.1.21/source/mariadb-10.1.21.tar.gz/from/http%3A//mirrors.tuna.tsinghua.edu.cn/mariadb/?serve"
#
#tar zxf mariadb-10.1.21.tar.gz
#cd mariadb-10.1.21
#cmake . -DCMAKE_INSTALL_PREFIX=${MARIADB_HOME}/ \
#-DMYSQL_DATADIR=${MARIADB_DATA_HOME} \
#-DWITH_INNOBASE_STORAGE_ENGINE=1 \
#-DWITH_ARCHIVE_STORAGE_ENGINE=1 \
#-DWITH_BLACKHOLE_STORAGE_ENGINE=1 \
#-DENABLED_LOCAL_INFILE=1 \
#-DMYSQL_TCP_PORT=${MARIADB_PORT} \
#-DWITH_SSL=system \
#-DWITH_ZLIB=system \
#-DWITH_LIBWRAP=0 \
#-DCMAKE_THREAD_PREFER_PTHREAD=1 \
#-DEXTRA_CHARSETS=all \
#-DDEFAULT_CHARSET=utf8 \
#-DDEFAULT_COLLATION=utf8_general_ci \
#-DMYSQL_UNIX_ADDR=/tmp/mysql.sock \
#-DWITH_DEBUG=0 \
#-with-low-memory
#
#
## How to Fix PHP Configure “CC Internal error Killed (program cc1)” Error
## http://linux.101hacks.com/unix/fix-php-cc-internal-errror-killed/
#make && make install
#
#rm -rf /etc/my.cnf
#rm -rf /etc/init.d/mysqld
#
#cp support-files/mysql.server /etc/init.d/mysqld
#chmod a+x /etc/init.d/mysqld
#chkconfig --add mysqld
#chkconfig mysqld on
#chown mysql.mysql -R ${MARIADB_LOG_HOME}
#chown mysql.mysql -R ${MARIADB_DATA_HOME}
#${MARIADB_HOME}/scripts/mysql_install_db --user=mysql --basedir=${MARIADB_HOME} --datadir=${MARIADB_DATA_HOME}
#systemctl mysqld start
#echo 'export PATH=$PATH:'${MARIADB_HOME}'/bin' >> /etc/profile
## source "/etc/profile"
#${MARIADB_HOME}/bin/mysql -e "grant all privileges on *.* to root@'%' identified by '${ROOT_PWD}' with grant option;"
#${MARIADB_HOME}/bin/mysql -e "flush privileges;"
#${MARIADB_HOME}/bin/mysql -e "delete from mysql.user where password='';"
#systemctl mysqld restart
#println "mysql install success!"