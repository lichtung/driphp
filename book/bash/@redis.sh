#!/usr/bin/env bash

# @see https://linode.com/docs/databases/redis/install-and-configure-redis-on-centos-7/
#Add the EPEL repository, and update YUM to confirm your change:
#
#sudo yum install epel-release
#sudo yum update
#Install Redis:
#
#sudo yum install redis
#Start Redis:
#
#sudo systemctl start redis
#Optional: To automatically start Redis on boot:
#
#sudo systemctl enable redis
#Verify the Installation
#Verify that Redis is running with redis-cli:
#
#redis-cli ping
#If Redis is running, it will return:
#
#PONG

########################################################################################################
########################################################################################################
########################################################################################################
########################################################################################################

yum install gcc gcc-c++ -y

function buildir(){
    # check parent dir
    parentdir=`dirname $1`
    if [ ! -d ${parentdir} ]; then
        println "to make parentdir ${parentdir}"
        buildir ${parentdir}
    fi
    # make dir
    if [ ! -d $1 ]; then
        println "to make dir ${1}"
        mkdir $1
    else
        println "to make dir ${1}:exist"
    fi
}


function println(){
    echo -e "|---- ${1}"
}
# insert text into text file
# @param int $line_number
# @param string $text2insert
# @param string $textfilepath
function iinsert(){
    if [ ! -f ${3} ]; then
        touch ${3}
    fi
    sed -i "${1}a ${2}" ${3}
}
server_dir=/home/srv
redis=redis-4.0.7
install_dir=${server_dir}/install

function download(){
    if [ ! -f $1 ]; then
        println "file '${1}' begin download";
        curl -o $1 -L --connect-timeout 100 -m 200 $2
    else
        println "file '${1}' exist, stop download";
    fi
}


# download redis
redis_file_path=${install_dir}/${redis}.tgz
download ${redis_file_path} http://download.redis.io/releases/${redis}.tar.gz
redis_folder_path=${install_dir}/${redis}
if [ ! -d ${redis_folder_path} ]; then
    tar -zxvf ${redis_file_path} -C ${install_dir}
else
    println "${redis} source exist"
fi


# install redis
redis_server=/usr/local/bin/redis-server
if [ ! -f ${redis_server} ]; then
    cd ${install_dir}/${redis}
    make clean && make && make install
fi
redis_conf=/etc/redis/6379.conf
if [ ! -f ${redis_conf} ]; then
    buildir `dirname ${redis_conf}`
    cp ${install_dir}/${redis}/redis.conf ${redis_conf}
else
    println "${redis} has installed!"
fi

redis_init_script=/etc/init.d/redis
if [ ! -f ${redis_init_script} ]; then
    # copy config file
    cp ${install_dir}/${redis}/utils/redis_init_script ${redis_init_script}

    # run as system service
    iinsert 1 "# chkconfig: 2345 80 90" /etc/init.d/redis
    chkconfig --add redis
    systemctl start redis
    systemctl enable redis
fi
# nano /etc/rc.local
# echo never > /sys/kernel/mm/transparent_hugepage/enabled
# sysctl vm.overcommit_memory=1
# sysctl -w net.core.somaxconn=65535
# echo 511 > /proc/sys/net/core/somaxconn


# http://pecl.php.net/get/redis-3.1.2.tgz


# ● redis.service - (null)
#   Loaded: loaded (/etc/rc.d/init.d/redis; bad; vendor preset: disabled)
#   Active: activating (start) since Tue 2017-09-19 08:03:07 EDT; 3min 34s ago
#     Docs: man:systemd-sysv-generator(8)
#  Control: 11080 (redis)
#   CGroup: /system.slice/redis.service
#           ├─11080 /bin/sh /etc/rc.d/init.d/redis start
#           └─11081 /usr/local/bin/redis-server 127.0.0.1:6379
#
#Sep 19 08:03:07 wshore.win redis[11080]: |`-._`-._    `-.__.-'    _.-'_.-'|
#Sep 19 08:03:07 wshore.win redis[11080]: |    `-._`-._        _.-'_.-'    |
#Sep 19 08:03:07 wshore.win redis[11080]: `-._    `-._`-.__.-'_.-'    _.-'
#Sep 19 08:03:07 wshore.win redis[11080]: `-._    `-.__.-'    _.-'
#Sep 19 08:03:07 wshore.win redis[11080]: `-._        _.-'
#Sep 19 08:03:07 wshore.win redis[11080]: `-.__.-'
#Sep 19 08:03:07 wshore.win redis[11080]: 11081:M 19 Sep 08:03:07.940 # WARNING: The TCP backlog setting of 511 cannot be enforced because /proc/sys/net/core/somaxconn is set to the lower value of 128.
#Sep 19 08:03:07 wshore.win redis[11080]: 11081:M 19 Sep 08:03:07.940 # Server started, Redis version 3.2.7
#Sep 19 08:03:07 wshore.win redis[11080]: 11081:M 19 Sep 08:03:07.940 # WARNING overcommit_memory is set to 0! Background save may fail under low memory condition. To fix this issue add 'vm.overcommit_memory = 1' to /etc/sysctl.conf and then reboot or run the command 'sysctl vm.overcommit_memory=1' for this to take effect.
#Sep 19 08:03:07 wshore.win redis[11080]: 11081:M 19 Sep 08:03:07.940 * The server is now ready to accept connections on port 6379
# --------------------------------------------------------------------------------------
# solve: 1 (重启后失效)
# echo 511 > /proc/sys/net/core/somaxconn
# echo  1 > /proc/sys/vm/overcommit_memory
# solve: 2
# sysctl settings are defined through files in
# /usr/lib/sysctl.d/, /run/sysctl.d/, and /etc/sysctl.d/.
#
# Vendors settings live in /usr/lib/sysctl.d/.
# To override a whole file, create a new file with the same in
# /etc/sysctl.d/ and put new settings there. To override
# only specific settings, add a file with a lexically later
# name in /etc/sysctl.d/ and put new settings there.
#
# For more information, see sysctl.conf(5) and sysctl.d(5).
