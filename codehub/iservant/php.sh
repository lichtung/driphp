#!/usr/bin/bash

yum update -y
yum install -y curl  wget make gcc autoconf perl-devel.x86_64 libcurl-devel.x86_64
yum install -y freetype-devel.x86_64  libpng-devel.x86_64 pcre pcre-devel openssl-devel openssl-libs.x86_64
yum install -y openssl.x86_64 openssl-devel libxml2-devel
yum install -y libmemcached-devel.x86_64 libmemcached.x86_64
# libperl-dev libfreetype6-dev libpng12-dev libpcre2-dev libssh-dev libcurl4-openssl-dev libxml2-dev
# apt install libcurl4-openssl-dev libperl-dev libfreetype6-dev libpcre3-dev libxml2-dev openssl libssl-dev
########################################################################################################################
source ./include.sh

SRV_HOME=/home/srv
PHP7_HOME=${SRV_HOME}/php7
INSTALL_HOME=${SRV_HOME}/install
# var
php=php-7.0.17
if [ $1 = 'am' ]; then
    MIRROR=http://am1.php.net
else
    MIRROR=http://cn2.php.net
fi
#


# php 源码包下载解压
php_file_path=${INSTALL_HOME}/${php}.tar.gz
download ${php_file_path} ${MIRROR}/distributions/${php}.tar.gz
php_folder_path=${INSTALL_HOME}/${php}
if [ ! -d ${php_folder_path} ]; then
    tar -zxf ${php_file_path} -C ${INSTALL_HOME}
    println "${php} source unzip done"
else
    println "${php} source exist"
fi

# gd2 libjpeg库依赖
jpegsrc=jpeg-9b
jpegsrc_file_path=${INSTALL_HOME}/${jpegsrc}.tgz
if [ ! -f ${jpegsrc_file_path} ]; then
    println "Download ${jpegsrc_file_path}"
    # this address has been redirected
    download ${jpegsrc_file_path} http://www.ijg.org/files/jpegsrc.v9b.tar.gz
    tar -zxvf ${jpegsrc_file_path} -C ${INSTALL_HOME}
    cd ${INSTALL_HOME}/${jpegsrc}
    ./configure
    make && make install
fi
# php主程序安装
if [ ! -f ${PHP7_HOME}/path.lock ]; then
    cd ${php_folder_path}
    # --with-apxs2=/home/linzh/soft/apache24/bin/apxs
    ./configure --prefix=${PHP7_HOME}  --with-libdir=lib64 --enable-bcmath  --enable-fpm --enable-sockets --with-openssl  --with-libxml-dir --with-pcre-regex --enable-mbstring   --with-pdo-mysql    --with-openssl-dir --with-gd  --with-freetype-dir  --with-curl  --enable-pcntl --enable-zip --with-apxs2=/home/linzh/soft/apache24/bin/apxs
    make && make install

    if [ -d ${PHP7_HOME}/lib/ ] ; then
        cp ${php_folder_path}/php.ini-development ${PHP7_HOME}/lib/php.ini

        addpath ${PHP7_HOME}/bin
        touch ${PHP7_HOME}/path.lock
    fi
fi

# fpm 安装
if [ ! -f /etc/init.d/php-fpm ];then
    # 拷贝可执行文件
    cp ${php_folder_path}/sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm
    # 拷贝安装包目录下的配置文件
    cp ${php_folder_path}/sapi/fpm/php-fpm.conf ${PHP7_HOME}/etc/php-fpm.conf
    mv ${php_folder_path}/sapi/fpm/www.conf  ${PHP7_HOME}/etc/php-fpm.d/www.conf

    chmod a+x /etc/init.d/php-fpm
    # ubuntu下使用　sysv-rc-conf　代替 chkconfig
    chkconfig php-fpm on
    systemctl enable php-fpm
    systemctl start php-fpm
fi