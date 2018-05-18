#!/usr/bin/env bash

repoName=$1
branchName=$2
if [ "" = "${branchName}" ]; then
    branchName="master"
fi
cd ~
mkdir ${repoName}
cd ${repoName}
git --bare init

cd hooks

echo "#!/usr/bin/env bash
# 项目所在目录
BASEDIR=/srv/webroot/
# 项目名称
NAME=${repoName}
# post-receive 分支
BRANCH_NAME=${branchName}
WORKSPACE=\${BASEDIR}/\${NAME}/
GIT_DIR=\${WORKSPACE}/.git
GIT_WORK_TREE=\${WORKSPACE}
if [ ! -d \${WORKSPACE} ]; then
    cd \${BASEDIR}
    git clone git@127.0.0.1:~/\${NAME}
    cd \${NAME}
    git checkout \${BRANCH_NAME}
else
    cd \${WORKSPACE}
fi
git pull origin \${BRANCH_NAME}
" >>  post-receive
chmod a+x post-receive