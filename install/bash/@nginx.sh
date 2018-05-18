#!/usr/bin/bash

# @see http://nginx.org/en/linux_packages.html
# use yum instead



yum update -y
yum install -y curl make gcc autoconf perl-devel.x86_64 libcurl-devel.x86_64 freetype-devel.x86_64 libxml2-devel \
                libpng-devel.x86_64 pcre pcre-devel openssl-devel openssl-libs.x86_64 openssl.x86_64 openssl-devel

source ../../recycle/srv/_include.sh

nginx="nginx-1.12.2"
nginx_download="http://nginx.org/download/${nginx}.tar.gz"

SERVER_DIR=/home/srv
INSTALL_DIR=${SERVER_DIR}/install
NGINX_HOME=${SERVER_DIR}/nginx

USER=nobody
GROUP=nobody

# 删除卸载残留
rm -rf  /etc/init.d/nginx /etc/rc.d/rc5.d/S85nginx ${NGINX_HOME}

buildir ${SERVER_DIR}
buildir ${INSTALL_DIR}
buildir ${NGINX_HOME}

httpd_file_path=${INSTALL_DIR}/${nginx}.tar.gz
download ${httpd_file_path} ${nginx_download}

httpd_folder_path=${INSTALL_DIR}/${nginx}
if [ ! -d ${httpd_folder_path} ]; then
    tar -zxf ${httpd_file_path} -C ${INSTALL_DIR}
    println "${httpd_folder_path} source unzip done"
else
    println "${httpd_folder_path} source exist"
fi

if [ ! -f ${NGINX_HOME}/install.lock ]; then
    cd ${httpd_folder_path}
    # default prefix to "/usr/local/nginx/sbin  "
    ./configure  --prefix=${NGINX_HOME} --user=${USER} --group=${GROUP}   --with-http_realip_module    --with-http_sub_module  --with-http_gzip_static_module  --with-http_stub_status_module --with-pcre --with-http_ssl_module
    # make clean &&
    make && make install

# 本地开发环境安装
# ./configure --prefix=/home/asus/soft/nginx --user=asus --group=asus      \
#   --with-http_realip_module --with-http_sub_module  --with-http_gzip_static_module --with-http_stub_status_module
# PCRE缺失错误：
# ./configure: error: the HTTP rewrite module requires the PCRE library.
# You can either disable the module by using --without-http_rewrite_module
# option, or install the PCRE library into the system, or build the PCRE library
# statically from the source with nginx by using --with-pcre=<path> option.
# ```bash
# apt install libpcre3-dev
# ```

    addpath ${NGINX_HOME}/sbin
    touch ${NGINX_HOME}/install.lock
fi

backup ${NGINX_HOME}/conf/nginx.conf

copy ${WORKDIR}/conf/nginx.conf ${NGINX_HOME}/conf/nginx.conf


buildir /home/srv/webroot/public
echo "<?php phpinfo();" > /home/srv/webroot/public/__info.php

chmod -R 0755 /home/srv
chown -R nobody.nobody /home/srv/nginx

if [ ! -f /etc/init.d/nginx ]; then

    println "create /etc/init.d/nginx"
    cp ${WORKDIR}/init.d/nginx.sh /etc/init.d/nginx
    chmod a+x /etc/init.d/nginx

    println "create /etc/rc.d/rc5.d/S85nginx"
    ln -s /etc/init.d/nginx /etc/rc.d/rc5.d/S85nginx


    chkconfig --add nginx
    systemctl enable nginx
    systemctl start nginx
fi

#cat '#!/bin/bash
#source /etc/profile
#nginx
## /home/srv/nginx/sbin/nginx -c /home/srv/nginx/conf/nginx.conf' > /etc/init.d/nginx
#chmod a+x /etc/init.d/nginx