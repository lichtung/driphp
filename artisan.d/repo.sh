#!/usr/bin/env bash

REPOSITORY_NAME=$1
BRANCH_NAME=$2
if [ "" = "${BRANCH_NAME}" ]; then
    BRANCH_NAME="master"
fi
cd ~
mkdir ${REPOSITORY_NAME}
cd ${REPOSITORY_NAME}
git --bare init

cd hooks

echo "#!/usr/bin/env bash
# 项目所在目录
BASEDIR=/srv/webroot/
# 项目名称
NAME=${REPOSITORY_NAME}
# post-receive 分支
BRANCH_NAME=${BRANCH_NAME}
WORKSPACE=\${BASEDIR}/\${NAME}/
GIT_DIR=\${WORKSPACE}/.git
GIT_WORK_TREE=\${WORKSPACE}
if [ ! -d \${WORKSPACE} ]; then
    cd \${BASEDIR}
    git clone git@127.0.0.1:\${NAME}
    cd \${NAME}
    git checkout \${BRANCH_NAME}
else
    cd \${WORKSPACE}
fi
git pull origin \${BRANCH_NAME}
" >>  post-receive
chmod a+x post-receive
