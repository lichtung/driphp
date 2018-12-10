#!/usr/bin/env bash

yum update -y
yum install -y curl make gcc autoconf perl-devel.x86_64 libcurl-devel.x86_64 freetype-devel.x86_64  libpng-devel.x86_64
yum install -y pcre pcre-devel openssl-devel openssl-libs.x86_64 openssl.x86_64 openssl-devel libxml2-devel.x86_64 libxml2.x86_64 libxml2-static.x86_64
yum install -y expat-devel
# xml/apr_xml.c:35:19: fatal error: expat.h: No such file or directory
# sudo apt install libxml++2.6-dev
# Makefile:48: recipe for target 'htpasswd' failed
# sudo apt install  libapache-htpasswd-perl
#######################################  公共区域 ##########################################################################
source ./include.sh

CURRENT_DIR=`pwd`

########################################################################################################################

# 版本
httpd=httpd-2.4.29
apr=apr-1.6.3
apr_util=apr-util-1.6.1
# 镜像列表：http://www.apache.org/mirrors/
MIRROR=http://mirrors.tuna.tsinghua.edu.cn/apache/
httpd_source=${MIRROR}/httpd/${httpd}.tar.gz
apr_source=${MIRROR}/apr/${apr}.tar.gz
apr_util_source=${MIRROR}/apr/${apr_util}.tar.gz

# 服务器目录
SRV_HOME=~/srv
HTTPD_HOME=${SRV_HOME}/apache24
INSTALL_HOME=${SRV_HOME}/install
WEBROOT=${SRV_HOME}/webroot
SERVER_ADMIN=784855684@qq.com
# 监听端口
PORT=80
# 手机进程用户组
USER=linzh
GROUP=linzh
LOG_LEVEL=error
# 创建相关目录
buildir ${SRV_HOME}
buildir ${HTTPD_HOME}
buildir ${INSTALL_HOME}
buildir ${WEBROOT}

# download httpd
httpd_file_path=${INSTALL_HOME}/${httpd}.tar.gz
download ${httpd_file_path} ${httpd_source}
httpd_folder_path=${INSTALL_HOME}/${httpd}
if [ ! -d ${httpd_folder_path} ]; then
    tar -zxf ${httpd_file_path} -C ${INSTALL_HOME}
else
    println "${httpd} source exist"
fi

# srclib文件夹可能不存在
buildir ${httpd_folder_path}/srclib

# download apr
apr_file_path=${INSTALL_HOME}/apr.tar.gz
download ${apr_file_path} ${apr_source}
apr_folder_path=${httpd_folder_path}/srclib/apr
if [ ! -d ${apr_folder_path} ]; then
    tar -zxf ${apr_file_path} -C ${INSTALL_HOME}
    mv ${INSTALL_HOME}/${apr} ${apr_folder_path}
else
    println "${apr} source exist"
fi

# download apr-util
apr_util_file_path=${INSTALL_HOME}/${apr_util}.tar.gz
download ${apr_util_file_path} ${MIRROR}/apr/${apr_util}.tar.gz
apr_util_folder_path=${httpd_folder_path}/srclib/apr-util
if [ ! -d ${apr_util_folder_path} ]; then
    tar -zxf ${apr_util_file_path} -C ${INSTALL_HOME}
    mv ${INSTALL_HOME}/${apr_util} ${apr_util_folder_path}
else
    println "${apr_util} source exist"
fi

# install
if [ ! -f ${HTTPD_HOME}/path.lock ]; then
    cd ${INSTALL_HOME}/${httpd}
    # install
    ./configure --prefix=${HTTPD_HOME} --enable-static-htpasswd --with-pcre --with-mpm=event --with-included-apr --enable-ssl --enable-so
    make && make install

    addpath ${HTTPD_HOME}/bin
    touch ${HTTPD_HOME}/path.lock
fi


# httpd 加入 systemctl
if [ ! -f /etc/init.d/httpd ]; then
    cp  ${HTTPD_HOME}/bin/apachectl /etc/init.d/httpd
    ln -s /etc/init.d/httpd /etc/rc.d/rc5.d/S85httpd

    iinsert 1 "# chkconfig: 345 85 15" /etc/init.d/httpd
    iinsert 2 "# description: Apache Web Server" /etc/init.d/httpd
    chkconfig --add httpd
    systemctl start httpd
    systemctl enable httpd
fi

function backup(){
    if [ ! -d $1 ];then
        cp -R $1 "${1}.bak"
    else
        cp $1 "${1}.bak"
    fi
}

# backup apache
backup ${HTTPD_HOME}/conf/httpd.conf
backup ${HTTPD_HOME}/conf/extra/

function apache_line_config(){
    sed -i "${1}c ${2}" ${HTTPD_HOME}/conf/httpd.conf
}

apache_line_config "32"     "Define WEBROOT \"${WEBROOT}\""
apache_line_config "52"     "Listen ${PORT}"
apache_line_config "144"    "LoadModule vhost_alias_module modules/mod_vhost_alias.so"
apache_line_config "151"    "LoadModule rewrite_module modules/mod_rewrite.so"
apache_line_config "162"    "User ${USER}"
apache_line_config "163"    "Group ${GROUP}"
apache_line_config "184"    "ServerAdmin ${SERVER_ADMIN}"
apache_line_config "193"    "ServerName 127.0.0.1:8080"
apache_line_config "217"    'DocumentRoot "${WEBROOT}"'
apache_line_config "218"    '<Directory "${WEBROOT}">'
apache_line_config "231"    "    Options None"
apache_line_config "238"    "    AllowOverride All"
apache_line_config "251"    "    DirectoryIndex index.html index.php"
apache_line_config "261"    '<FilesMatch "\\.php$">'
apache_line_config "262"    '    SetHandler application/x-httpd-php'
apache_line_config "263"    '</FilesMatch>'
apache_line_config "276"    "LogLevel ${LOG_LEVEL}"