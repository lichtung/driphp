#!/usr/bin/env bash

# python3.7编译安装时报错“ModuleNotFoundError: No module named '_ctypes'”的解决办法
#wget http://mirror.centos.org/centos/7/os/x86_64/Packages/libffi-devel-3.0.13-18.el7.x86_64.rpm
#rpm -ivh libffi-devel-3.0.13-18.el7.x86_64.rpm
yum install libffi-devel -y


# 。。。。。。

# 升级python3下的pip
pip3 install --upgrade pip
pip3 -V
