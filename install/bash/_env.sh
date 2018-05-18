#!/usr/bin/env bash

echo -e "               ┏━┓    ┏━┓  "
echo -e "              ┏┛ ┻━━━━┛ ┻━┓"
echo -e "              ┃           ┃"
echo -e " ┏┓      ┏┓   ┃           ┃"
echo -e "┏┛┻━━━━━━┛┻━┓ ┃ ==    ==  ┃"
echo -e "┃           ┃ ┃           ┃   ┏┓      ┏┓ "
echo -e "┃ ==    ==  ┃ ┃     ^     ┃  ┏┛┻━━━━━━┛┻━┓"
echo -e "┃           ┃ ┃           ┃  ┃ ==    ==  ┃"
echo -e "┃     ^     ┃ ┗━━┓      ┏━┛  ┃     ^     ┃"
echo -e "┗━━┓      ┏━┛    ┃      ┃    ┗━━┓      ┏━┛"


########################## CONSTANT ##########################
SRV_HOME=/srv
PHP_HOME=${SRV_HOME}/php
INSTALL_HOME=${SRV_HOME}/install

########################## FUNCTION ##########################
function println(){
    echo -e "-|DRIP|-: ${1}"
}
function str2upper(){
    echo `tr '[a-z]' '[A-Z]'  <<<"$1"`
}
function str2lower(){
    echo `tr '[A-Z]' '[a-z]' <<<"$1"`
}
function sha1() {
  sha1sum $1 | awk '{print $1}'
}

# @param string save_path
# @param string download_url
# @param string sha1
function download(){
    save_path=${1}
    download_url=${2}
    sha1=${3}
    if [ ! -f ${save_path} ]; then
        mkdir `dirname ${save_path}` -p
        echo -e "file '${save_path}' begin download";
        #wget $2 -O $1
        curl -o ${save_path} -L --connect-timeout 100000 --max-time 100000 --retry 100 ${download_url}
    else
        if [ -z ${sha1} ]; then
            sha1file=`sha1 ${save_path}`
            if [ ${sha1} != ${sha1file} ]; then
                println "file '${save_path}' sha1 error, remove and download";
                rm -f ${save_path}
                download ${save_path} ${download_url} ${sha1}
            else
                println "file '${save_path}' exist, pass sha1 checking";
            fi
        else
            println "file '${save_path}' exist, no sha1 checking";
        fi

    fi
}
# copy [from] [to]
function copy(){
    pdir=`dirname ${1}`
    mkdir ${pdir} -p
    cp -R ${1} ${2}
}
function save2env(){
    echo "export PATH=\${PATH}:${1}" >> /etc/profile
}

function backup(){
    target=${1}
    destination="${target}.bak"
    if [ -f ${target} ]; then
    # backup file
        if [ -f ${destination} ] ; then
            unlink ${destination}
        fi
        cp ${target} ${destination}
    elif [ -d ${target} ]; then
    # backup directory
        if [ -d ${destination} ] ; then
            rm -rf ${destination}
        fi
        cp -R ${target} ${destination}
    else
    # target file not exist
        println "${target} not exist"
    fi
}

# @param int $line_number
# @param string $text
# @param string $file
function insert2file(){
    if [ ! -f ${3} ]; then
        touch ${3}
    else
        line_number=`expr ${1} - 1`
        sed -i "${line_number}a ${2}" ${3}
    fi
}
# @param string $str2replace
# @param string $str4replace
# @param string $file
function replace2file(){
    if [ ! -f ${3} ]; then
        touch ${3}
    else
        sed -i "s/${1}/${2}" ${3}
    fi
}
########################## VARIABLE ##########################

# !important
if [ -z ${HOME_DIR} ]; then
    HOME_DIR="$( cd "$( dirname "$0"  )" && pwd  )"
fi

println "Current working directory is '${HOME_DIR}'"
OS=`uname`
# 截取字符串前5位
OS="${OS:0:5}"
OS=`str2upper ${OS}`
println "Current Operate System is '${OS}'"

if [ ${OS} = "MINGW" ]; then
    IS_WINDOWS=1
    IS_LINUX=0
    IS_MAC=0
elif  [ ${OS} = "DARWIN" ]; then
    IS_WINDOWS=0
    IS_LINUX=0
    IS_MAC=1
elif  [ ${OS} = "LINUX" ]; then
    IS_WINDOWS=1
    IS_LINUX=0
    IS_MAC=0
else
    IS_WINDOWS=0
    IS_LINUX=0
    IS_MAC=0
fi

