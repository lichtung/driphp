
## Gogs多项目部署
将git的公钥设置为新项目的部署秘钥时，提示部署秘钥已经被使用，那么就是说，一个一个完整只能部署一个项目？

google搜索答案并没有给任何解释，但看到了这么一篇文章[管理部署密钥](http://wiki.jikexueyuan.com/project/github-developer-guides/managing-deploy-keys.html)
里面说：
>部署密钥是一个存放在你的服务器上并且可以授权访问一个 GitHub 存储库的 SSH 密钥。这个密钥是直接附在存储库上的，而不是个人用户账户。
 优点
 任何具有访问存储库权限的人都可以部署工程。
 用户不需要改变他们本地的 SSH 设定。
 缺点
 一个部署密钥只能授权一个存储库。而更复杂的工程可能会在同一个服务器上对许多存储库发出 pull 操作。
 部署密钥总是提供对一个存储库的完整读写访问权限。
 部署密钥通常没有经过密码保护，如果服务器被攻陷这些密钥将会很容易被获取

好吧，谢谢了

找其他解决办法，WebHook依然解决不了实际问题，最多是提醒一下谁谁推送了一次代码

想到之前的解决办法，就是将**~/.ssh/id_rsa.pub**内容放到**~/.ssh/authorized_keys**里面，
设置钩子内容
```bash
#!/usr/bin/env bash
# 项目所在目录
BASEDIR=/srv/webroot/
# 项目名称
NAME=wordpress
# post-receive 分支
BRANCH_NAME=master
WORKSPACE=${BASEDIR}/${NAME}/
GIT_DIR=${WORKSPACE}/.git
GIT_WORK_TREE=${WORKSPACE}
if [ ! -d ${WORKSPACE} ]; then
    cd ${BASEDIR}
    git clone git@127.0.0.1:~/repositories/linzhv/${NAME}.git
    cd ${NAME}
    git checkout ${BRANCH_NAME}
else
    cd ${WORKSPACE}
fi
git pull origin ${BRANCH_NAME}
```
这样git就可以克隆自己的仓库了，执行克隆时出错了
```bash
[git@centos74 webroot]$ git clone git@127.0.0.1:linzhv/wordpress.git
Cloning into 'wordpress'...
fatal: 'linzhv/wordpress.git' does not appear to be a git repository
```
瞬间明白,我是把Gogs的仓库建立在/home/git/repositories文件夹下的
```bash
git clone git@127.0.0.1:~/repositories/linzhv/wordpress.git
```
问题解决了吗？
没有，并没有，每次建立一个仓库，都需要手动设置一下上述操作，-_-#




 如果钩子未执行，可以先拷贝这段文本使用git账号到有权限的目录下执行，一般是接受就是如下的问题
>  The authenticity of host '127.0.0.1 (127.0.0.1)' can't be established.
  ECDSA key fingerprint is SHA256:QYLG+5+R1tMRlgS1s4UFeKsKIadUHg4pDRaYYp7VRpw.
  ECDSA key fingerprint is MD5:4e:be:4a:c8:75:06:90:69:5c:6b:21:18:14:95:b6:4f.
  Are you sure you want to continue connecting (yes/no)? yes
  
```bash
#!/usr/bin/env bash
cd /srv/webroot/
if [ ! -d "/srv/webroot/dripex" ]; then
	git clone git@127.0.0.1:linzhv/dripex.git
fi
cd dripex
git pull origin master

```


### 部署钩子
在将代码推送到gogs的时候希望线上代码也能更新，那么就需要使用钩子(Hook)了

钩子的原理是，当服务器收到pull请求的时候，检查是否设置了WEB钩子或者GIT钩子
如果有则依次执行钩子设定的内容。
这里以GIT钩子为例，常用的钩子如下：
- pre-receive 收到了push请求但是未更新仓库
- update 
- post-receive

钩子的作用详细可以参考 [钩子文档](https://git-scm.com/docs/githooks)

找到仓库设置，在post-receive中，加入钩子文本
```bash
#!/usr/bin/env bash

################################################
#              需要设置下面三个变量
################################################
# 项目所在目录
BASEDIR=/srv/webroot/
# 项目名称
NAME=dripex
# post-receive 分支
BRANCH_NAME=master

################################################
#              这里代码基本不同变
#    注意：第一次需要手动将hostkey加入know_hosts中
################################################
WORKSPACE=${BASEDIR}/${NAME}/
GIT_DIR=${WORKSPACE}/.git
GIT_WORK_TREE=${WORKSPACE}
if [ ! -d ${WORKSPACE} ]; then
    cd ${BASEDIR}
    git clone git@127.0.0.1:linzhv/${NAME}.git
    cd ${NAME}
    git checkout ${BRANCH_NAME}
else
    cd ${WORKSPACE}
fi
git pull origin ${BRANCH_NAME}

```
在 **管理部署密钥** 中加入服务器git账户的公钥，否则无法更新

之后客户机中调用push，看到**remote:  X file changed, Y insertions(+)**之类的就表示部署成功
```text
LZHMBA:dripex myname git push origin2 master
Counting objects: 5, done.
Delta compression using up to 4 threads.
Compressing objects: 100% (5/5), done.
Writing objects: 100% (5/5), 1.51 KiB | 1.51 MiB/s, done.
Total 5 (delta 3), reused 0 (delta 0)
remote: From 127.0.0.1:XXXXX/YYYYY
remote:  * branch            master     -> FETCH_HEAD
remote: Updating 98078e1..722dde2
remote: Fast-forward
remote:  book/extra/gogs.md | 47 +++++++++++++++++++++++++++++++++++++++++++++++
remote:  1 file changed, 47 insertions(+)
To 106.15.183.81:XXXXX/YYYYY.git
   98078e1..722dde2  master -> master
```
