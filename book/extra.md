# ssh自动添加hostkey到know_hosts
当我们用ssh连接到其他linux平台时，会遇到以下提示：
>The authenticity of host ‘git.sws.com (10.42.1.88)’ can’t be established. 
ECDSA key fingerprint is 53:b9:f9:30:67:ec:34:88:e8:bc:2a:a4:6f:3e:97:95. 
Are you sure you want to continue connecting (yes/no)? yes 

而此时必须输入yes，连接才能建立。

但其实我们可以在ssh_config配置文件中配置此项，

打开/etc/ssh/ssh_config文件：

找到： 
```cnf
# StrictHostKeyChecking ask 
# 修改为 
StrictHostKeyChecking no
```
这个选项会自动的把 想要登录的机器的SSH pub key 添加到 ~/.ssh/know_hosts 中。
