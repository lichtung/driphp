#!/usr/bin/bash

yum install git -y
useradd git
chown -R git.git /home/srv/webroot


su git
cd /home/git

ssh-keygen -t rsa -C 784855684@qq.com

git config --global user.name lich4ung
git config --global user.email 784855684@qq.com
git config --global core.autocrlf false
touch /home/git/.ssh/authorized_keys
cat /home/git/.ssh/id_rsa.pub > /home/git/.ssh/authorized_keys
chmod 0600 /home/git/.ssh/authorized_keys

passwd git
