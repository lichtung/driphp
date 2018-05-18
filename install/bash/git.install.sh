#!/usr/bin/env bash

yum install git -y
useradd git
mkdir -p /srv/webroot
chown -R git.git /srv/webroot


su git

cd /home/git

ssh-keygen -t rsa -C 784855684@qq.com

git config --global user.name lich4ung
git config --global user.email 784855684@qq.com
touch /home/git/.ssh/authorized_keys
cat /home/git/.ssh/id_rsa.pub > /home/git/.ssh/authorized_keys
chmod 0600 /home/git/.ssh/authorized_keys

#    git config --global core.autocrlf false

passwd git