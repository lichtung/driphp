#!/usr/bin/bash

respoName=$1
branchName=$2
if [ "" = "${branchName}" ]; then
    branchName="master"
fi
cd ~
mkdir ${respoName}
cd ${respoName}
git --bare init

cd hooks

echo "#!/usr/bin/env bash

# 项目所在目录
BASEDIR=/home/srv/webroot/
# 项目名称
NAME=${respoName}
# post-receive 分支
PRBRANCH=${branchName}

WORKSPACE=\${BASEDIR}/\${NAME}/
GIT_DIR=\${WORKSPACE}/.git
GIT_WORK_TREE=\${WORKSPACE}

if [ ! -d \${WORKSPACE} ]; then
    cd \${BASEDIR}
    git clone git@127.0.0.1:~/\${NAME}
    cd \${NAME}
    git checkout \${PRBRANCH}
else
    cd \${WORKSPACE}
fi

git pull origin \${PRBRANCH}
" >>  post-receive
chmod a+x post-receive

