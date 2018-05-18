#!/usr/bin/env bash

source _env.sh


#PHP_MIRROR=http://am1.php.net
#PHP_MIRROR=http://jp2.php.net
PHP_MIRROR=http://cn2.php.net
PHP_VERSION=php-7.1.15
PHP_SHA256=0669c68a52cbd2f1cfa83354918ed03b0bcaa34ed9bafaee7dfd343461b881d4
PHP_HOME=/srv/php
INSTALL_HOME=/srv/install
PHP_INSTALL_HOME=${INSTALL_HOME}/${PHP_VERSION}

#Installing shared extensions:     /usr/local/lib/php/extensions/no-debug-non-zts-20160303/
#Installing PHP CLI binary:        /usr/local/bin/
#Installing PHP CLI man page:      /usr/local/php/man/man1/
#Installing phpdbg binary:         /usr/local/bin/
#Installing phpdbg man page:       /usr/local/php/man/man1/
#Installing PHP CGI binary:        /usr/local/bin/
#Installing PHP CGI man page:      /usr/local/php/man/man1/
#Installing build environment:     /usr/local/lib/php/build/
#Installing header files:          /usr/local/include/php/
#Installing helper programs:       /usr/local/bin/

# 安装依赖
function php_install_dependence(){
    yum update -y
    yum install -y curl wget make gcc autoconf perl-devel.x86_64 libcurl-devel.x86_64 \
                    freetype-devel.x86_64  libpng-devel.x86_64 pcre pcre-devel openssl-devel openssl-libs.x86_64 \
                    openssl.x86_64 openssl-devel libxml2-devel
}


function php_install_jpeg(){
    # gd2 libjpeg 库依赖
    JPEG_VERSION=jpeg-9b
    JPEG_DOWNLOAD_URL=${INSTALL_HOME}/${JPEG_VERSION}.tgz
    if [ ! -f ${JPEG_DOWNLOAD_URL} ]; then
        println "Download ${JPEG_DOWNLOAD_URL}"
        # this address has been redirected
        download ${JPEG_DOWNLOAD_URL} http://www.ijg.org/files/JPEG_VERSION.v9c.tar.gz
        tar -zxf ${JPEG_DOWNLOAD_URL} -C ${INSTALL_HOME}
        cd ${INSTALL_HOME}/${JPEG_VERSION}
        ./configure && make && make install
    fi
}

function php_check_sha256sum(){
    php_file_path=${1}
    php_file_src=${2}
    download ${php_file_path} ${php_file_src}

    sha256=`sha256sum ${php_file_path}`
    sha256="$(echo ${sha256} | grep ${PHP_SHA256})"
    if [ "${sha256}" = "" ]; then
        rm -f ${php_file_path}
        php_check_sha256sum ${1} ${2}
    fi
}
# php 源码包下载解压
function php_get_source(){
    php_file_path=${INSTALL_HOME}/${PHP_VERSION}.tar.gz

    php_check_sha256sum ${php_file_path} ${PHP_MIRROR}/distributions/${PHP_VERSION}.tar.gz

    if [ ! -d ${PHP_INSTALL_HOME} ]; then
        tar -zxf ${php_file_path} -C ${INSTALL_HOME}
        println "${PHP_VERSION} source unzip done"
    else
        println "${PHP_VERSION} source exist"
    fi
}

function php_install_composer(){
    mkdir /srv/bin -p
    cd /srv/bin
    download /srv/bin/composer https://getcomposer.org/composer.phar
    chmod a+x /srv/bin/composer
}

function php_install(){
    cd ${PHP_INSTALL_HOME}
    #  --with-gd
    ./configure --with-libdir=lib64 --enable-bcmath  --enable-fpm --enable-sockets --with-openssl  --with-libxml-dir --enable-zip \
    --with-pcre-regex --enable-mbstring   --with-pdo-mysql    --with-openssl-dir --with-freetype-dir  --with-curl  --enable-pcntl \
    --prefix=${PHP_HOME}
#    --with-apxs2=/srv/apache24/bin/apxs
#    --with-apxs2=/usr/bin/apxs
    make && make install

    if [ -d ${PHP_HOME}/lib/ ] ; then
        cp ${PHP_INSTALL_HOME}/php.ini-development ${PHP_HOME}/lib/php.ini
    fi
    # 拷贝可执行文件
    cp ${PHP_INSTALL_HOME}/sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm
    # 拷贝安装包目录下的配置文件
    cp ${PHP_INSTALL_HOME}/sapi/fpm/php-fpm.conf ${PHP_HOME}/etc/php-fpm.conf
    mv ${PHP_INSTALL_HOME}/sapi/fpm/www.conf  ${PHP_HOME}/etc/php-fpm.d/www.conf

    chmod a+x /etc/init.d/php-fpm
    # ubuntu下使用　sysv-rc-conf　代替 chkconfig
    chkconfig php-fpm on
    systemctl enable php-fpm
    systemctl start php-fpm
}



function php_ext_redis(){
    REDIS_VERSION=3.1.6
    cd ${PHP_INSTALL_HOME}/ext
    download ${PHP_INSTALL_HOME}/ext/redis-${REDIS_VERSION}.tgz http://pecl.php.net/get/redis-${REDIS_VERSION}.tgz
    tar -zxf redis-${REDIS_VERSION}.tgz
    cd redis-${REDIS_VERSION}
    ${PHP_HOME}/bin/phpize
    ./configure --with-php-config=${PHP_HOME}/bin/php-config
    make && make install
    #mv ${PHP_HOME}/lib/php/extensions/no-debug-non-zts-20160303/redis.so ${PHP_HOME}/lib/php/extensions/redis.so
    cd ..
}

function php_ext_mongodb(){
    MONGODB_VERSION=1.3.4
    cd ${PHP_INSTALL_HOME}/ext
    download ${PHP_INSTALL_HOME}/ext/mongodb-${MONGODB_VERSION}.tgz http://pecl.php.net/get/mongodb-${MONGODB_VERSION}.tgz
    tar -zxf mongodb-${MONGODB_VERSION}.tgz
    cd mongodb-${MONGODB_VERSION}
    ${PHP_HOME}/bin/phpize
    ./configure --with-php-config=${PHP_HOME}/bin/php-config
    make && make install
    #mv ${PHP_HOME}/lib/php/extensions/no-debug-non-zts-20160303/mongodb.so ${PHP_HOME}/lib/php/extensions/mongodb.so
    cd ..
}
function php_ext_swoole(){
    SWOOLE_VERSION=2.1.1
    cd ${PHP_INSTALL_HOME}/ext
    download ${PHP_INSTALL_HOME}/ext/swoole-${SWOOLE_VERSION}.tgz http://pecl.php.net/get/swoole-${SWOOLE_VERSION}.tgz
    tar -zxf swoole-${SWOOLE_VERSION}.tgz
    cd swoole-${SWOOLE_VERSION}
#    if [ ! -f "${PHP_INSTALL_HOME}/ext/swoole-${SWOOLE_VERSION}/" ]; then
#
#    fi
    ${PHP_HOME}/bin/phpize
    ./configure --with-php-config=${PHP_HOME}/bin/php-config
    make && make install
    #mv ${PHP_HOME}/lib/php/extensions/no-debug-non-zts-20160303/xdebug.so ${PHP_HOME}/lib/php/extensions/xdebug.so
    cd ..
}

function php_ext_xdebug(){
    XDEBUG_VERSION=2.6.0
    cd ${PHP_INSTALL_HOME}/ext
    download ${PHP_INSTALL_HOME}/ext/xdebug-${XDEBUG_VERSION}.tgz https://xdebug.org/files/xdebug-${XDEBUG_VERSION}.tgz
    tar -zxf xdebug-${XDEBUG_VERSION}.tgz
    cd xdebug-${XDEBUG_VERSION}
    ${PHP_HOME}/bin/phpize
    ./configure --with-php-config=${PHP_HOME}/bin/php-config
    make && make install
    #mv ${PHP_HOME}/lib/php/extensions/no-debug-non-zts-20160303/xdebug.so ${PHP_HOME}/lib/php/extensions/xdebug.so
    cd ..
}

function php_ext_gd(){
    cd ${PHP_INSTALL_HOME}/ext/gd
    ${PHP_HOME}/bin/phpize
    ./configure --with-php-config=${PHP_HOME}/bin/php-config
    make && make install
}
function php_ext_zlib(){
    cd ${PHP_INSTALL_HOME}/ext/gd
    ${PHP_HOME}/bin/phpize
    ./configure --with-php-config=${PHP_HOME}/bin/php-config
    make && make install
}
#extension=/srv/php/lib/php/extensions/gd.so
#extension=/srv/php/lib/php/extensions/mongodb.so
#extension=/srv/php/lib/php/extensions/redis.so
#extension=/srv/php/lib/php/extensions/swoole.so
#[XDebug]
#zend_extension=/srv/php/lib/php/extensions/xdebug.so
#xdebug.profiler_enable=On
#xdebug.remote_enable=On
#xdebug.remote_port=10000
#xdebug.remote_handler=dbgp
#xdebug.remote_host=sharin.online
#xdebug.idekey=phpstorm



php_install_dependence
php_install_jpeg
php_get_source
php_install
php_ext_mongodb
php_ext_redis
php_ext_swoole
php_ext_xdebug
php_ext_gd

#NODE_HOME=/srv/node
#PHP_HOME=/srv/php
#JAVA_HOME=/srv/jdk
#export PATH=${PATH}:${NODE_HOME}/bin:${PHP_HOME}/bin:${JAVA_HOME}/bin


# line 939
# date.timezone = Asia/Shanghai
