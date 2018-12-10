#!/usr/bin/env bash

# 项目所在目录
BASEDIR=/home/srv/webroot/litera/
# 项目名称
NAME=lite
# post-receive 分支
PRBRANCH=test

WORKSPACE=${BASEDIR}/${NAME}/
GIT_DIR=${WORKSPACE}/.git
GIT_WORK_TREE=${WORKSPACE}

if [ ! -d ${WORKSPACE} ]; then
    cd ${BASEDIR}
    git clone git@127.0.0.1:~/${NAME}
    cd ${NAME}
    git checkout ${PRBRANCH}
else
    cd ${WORKSPACE}
fi

git pull origin ${PRBRANCH}
