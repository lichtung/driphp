#!/usr/bin/env bash

yum install git -y
#apt-get install git -y
useradd git
mkdir -p /srv/webroot
chown -R git.git /srv/webroot

EMAIL=784855684@qq.com
NAME=linzh

su git

cd /home/git

ssh-keygen -t rsa -C ${EMAIL}
git config --global user.name ${NAME}
git config --global user.email ${EMAIL}
touch /home/git/.ssh/authorized_keys
cat /home/git/.ssh/id_rsa.pub > /home/git/.ssh/authorized_keys
chmod 0600 /home/git/.ssh/authorized_keys

#    git config --global core.autocrlf false

passwd git