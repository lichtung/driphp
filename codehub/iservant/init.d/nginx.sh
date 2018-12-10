#!/usr/bin/env bash
# chkconfig: 2342 85 15
# 2342 is order number of startup,2345 is redis's order number
#
# Startup script for the nginx Web Server
#
# description: nginx is a World Wide Web server.
# processname: nginx
# pidfile: /home/srv/nginx/logs/nginx.pid
# config: /home/srv/nginx/conf/nginx.conf

NGINX_HOME=/home/srv/nginx

nginxd=${NGINX_HOME}/sbin/nginx
nginx_config=${NGINX_HOME}/conf/nginx.conf
nginx_pid=${NGINX_HOME}/logs/nginx.pid



if [ -f ${nginx_pid} ]; then
    pid=`cat ${nginx_pid}`
else
    pid=0
fi


function start ()
{
    ${nginxd} -c ${nginx_config}
}
function stop() {
    if [ -f ${nginx_pid} ]; then
        kill pid
        rm -f  ${nginx_pid}
    fi
}
# reload nginx service functions.
function reload() {
    stop
    start
}
# See how we were called.
case "$1" in
    start)
        start
            ;;
    stop)
         stop
            ;;
    reload)
         reload
            ;;
    restart)
        reload
            ;;
    status)
         cat "PID ${nginxd} running"
            ;;

    *)
            echo $"Usage: nginx {start|stop|restart|reload|status|help}"
            exit 1
esac
exit
