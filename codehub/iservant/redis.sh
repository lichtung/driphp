#!/usr/bin/bash


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
redis=redis-3.2.7
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

# http://pecl.php.net/get/redis-3.1.2.tgz