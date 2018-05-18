#!/usr/bin/env bash

source _env.sh


# 镜像列表 @see http://www.apache.org/mirrors/
APACHE_MIRROR=http://mirror.bit.edu.cn/apache/
# 版本
HTTPD_VERSION=httpd-2.4.29
APR_VERSION=apr-1.6.3
APR_UTIL_VERSION=apr-util-1.6.1

CURRENT_DIR=`pwd`
SRV_HOME=/srv
HTTPD_HOME=${SRV_HOME}/apache
INSTALL_HOME=${SRV_HOME}/install
WEBROOT=${SRV_HOME}/webroot
SERVER_ADMIN=784855684@qq.com
# 监听端口
LISTEN_PORT=80
# 手机进程用户组
PROCESS_USER=nobody
PROCESS_GROUP=nobody
LOG_LEVEL=error

function apache_install_dependence(){
    yum update -y
    yum install -y curl make gcc autoconf perl-devel.x86_64 libcurl-devel.x86_64 freetype-devel.x86_64  libpng-devel.x86_64 \
                pcre pcre-devel openssl-devel openssl-libs.x86_64 openssl.x86_64 openssl-devel libxml2-devel expat-devel.x86_64 openldap-devel.x86_64 \
                openldap-devel.x86_64 apr-util-ldap.x86_64
}

function download_source(){
    # download httpd
    httpd_file_path=${INSTALL_HOME}/${HTTPD_VERSION}.tar.gz
    download ${httpd_file_path} ${APACHE_MIRROR}/httpd/${HTTPD_VERSION}.tar.gz
    httpd_folder_path=${INSTALL_HOME}/${HTTPD_VERSION}
    if [ ! -d ${httpd_folder_path} ]; then
        tar -zxf ${httpd_file_path} -C ${INSTALL_HOME}
    else
        println "${HTTPD_VERSION} source exist"
    fi
    mkdir -p ${httpd_folder_path}/srclib

    # download apr
    apr_file_path=${INSTALL_HOME}/${APR_VERSION}.tar.gz
    download ${apr_file_path} ${APACHE_MIRROR}/apr/${APR_VERSION}.tar.gz
    apr_folder_path=${httpd_folder_path}/srclib/apr
    if [ ! -d ${apr_folder_path} ]; then
        tar -zxf ${apr_file_path} -C ${INSTALL_HOME}
        mv ${INSTALL_HOME}/${APR_VERSION} ${apr_folder_path}
    else
        println "${APR_VERSION} source exist"
    fi

    # download apr-util
    apr_util_file_path=${INSTALL_HOME}/${APR_UTIL_VERSION}.tar.gz
    download ${apr_util_file_path} ${APACHE_MIRROR}/apr/${APR_UTIL_VERSION}.tar.gz
    apr_util_folder_path=${httpd_folder_path}/srclib/apr-util
    if [ ! -d ${apr_util_folder_path} ]; then
        tar -zxf ${apr_util_file_path} -C ${INSTALL_HOME}
        mv ${INSTALL_HOME}/${APR_UTIL_VERSION} ${apr_util_folder_path}
    else
        println "${APR_UTIL_VERSION} source exist"
    fi
}

function apache_install(){
    mkdir -p /srv/webroot
    cd ${INSTALL_HOME}/${HTTPD_VERSION}
    # install
    ./configure --prefix=${HTTPD_HOME} \
    --enable-static-htpasswd --with-pcre --with-mpm=event --with-included-apr --enable-ssl --enable-so --with-ldap
    make && make install
    # backup apache
    backup ${HTTPD_HOME}/conf/httpd.conf
    # warn cp: cannot copy a directory, ‘/srv/apache/conf/extra/’, into itself, ‘/srv/apache/conf/extra/.bak’
    backup ${HTTPD_HOME}/conf/extra
    cp ${CURRENT_DIR}/conf/httpd.conf ${HTTPD_HOME}/conf/httpd.conf
    cp ${CURRENT_DIR}/conf/httpd-vhosts.extra.conf ${HTTPD_HOME}/conf/extra/httpd-vhosts.conf
}

function apache_serve(){
    # httpd 加入 systemd
    if [ ! -f /etc/init.d/httpd ]; then
        cp  ${HTTPD_HOME}/bin/apachectl /etc/init.d/httpd
        ln -s /etc/init.d/httpd /etc/rc.d/rc5.d/S85httpd

        # 分别在第二行和第三行插入下面的备注
        insert2file 2 "# chkconfig: 345 85 15" /etc/init.d/httpd
        insert2file 3 "# description: Apache Web Server" /etc/init.d/httpd
        chkconfig --add httpd
        systemctl start httpd
        systemctl enable httpd
    fi
}
