# RabbitMQ

# Install Erlang 
## Installation using repository
###  Adding repository entry
To add Erlang Solutions repository (including our public key for verifying signed package) to your system, call the following commands:
```bash
wget https://packages.erlang-solutions.com/erlang-solutions-1.0-1.noarch.rpm
rpm -Uvh erlang-solutions-1.0-1.noarch.rpm
```
### Alternatively: adding the repository entry manually
RPM packages are signed. To add Erlang Solutions key, execute command:
```bash
rpm --import https://packages.erlang-solutions.com/rpm/erlang_solutions.asc
```
Add the following lines to some file in "/etc/yum.repos.d/":
[erlang-solutions]
name=CentOS $releasever - $basearch - Erlang Solutions
baseurl=https://packages.erlang-solutions.com/rpm/centos/$releasever/$basearch
gpgcheck=1
gpgkey=https://packages.erlang-solutions.com/rpm/erlang_solutions.asc
enabled=1
2. Adding repository with dependencies
Packages requires some packages that are not present in standard repository. Please ensure that EPEL respository is enabled.

3. Installing Erlang
Call the following command to install the "erlang" package:

sudo yum install erlang
or this command to install the "esl-erlang" package:

sudo yum install esl-erlang
Please refer to the FAQ for the difference between those versions. Your erlang will be kept up to date either way.

FAQ — Frequently Asked Questions
1. What does this "yum install erlang" do?
Erlang/OTP Platform is a complex system composed of many smaller applications (modules). Installing the "erlang" package automatically installs the entire OTP suite. Since some of the more advanced users might want to download only a specific selection of modules, Erlang/OTP has been divided into smaller packages (all with the prefix "erlang-") that can be installed without launching the "erlang" package.

2. What is "esl-erlang", how is it different from "erlang"? Have you removed it from repositories?
The "esl-erlang" package is a file containg the complete installation: it includes the Erlang/OTP platform and all of its applications. The "erlang" package is a frontend to a number of smaller packages. Currently we support both "erlang" and "esl-erlang".

Note that the split packages have multiple advantages:

seamless replacement of the available packages,
other packages have dependencies on "erlang", not "esl-erlang",
if your disk-space is low, you can get rid of some unused parts; "erlang-base" needs only ~13MB of space.
3. My operating system already provides erlang. Why should I choose yours?
Our packages contain the latest stable Erlang/OTP distribution. Other repositories usually lag behind. For example: when we started providing R16B02, Ubuntu 12.04 LTS Precise Pangolin still provided R14B02. Our packages are complete, easy to install and have been thoroughly tested.

4. How to prevent packages from the Erlang Solutions repository being replaced by other repositories?
It is very improbable that this would happen due to the fact that we provide the latest Erlang/OTP and the distributions are unlikely to change the provided Erlang/OTP version. The auto–update tools on Debian/Ubuntu download the newest version.

5. Does the "erlang" package install everything I need for Erlang programming?
No, there are three additional packages:

erlang-doc — HTML/PDF documentation,
erlang-manpages — manpages,
erlang-mode — major editing mode for Emacs.
6. I have heard about HiPE. What is it? How to get it?
HiPE stands for High-Performance Erlang Project. It is a native code compiler for Erlang. In most cases, it positively affects performance. If you want to download it, call the following:

sudo yum install erlang-hipe
This will replace the Erlang/OTP runtime with a HiPE supported version. Other Erlang applications do not need to be reinstalled. To return to the standard runtime, call:

sudo yum install erlang-base

erlang安装：https://www.erlang-solutions.com/resources/download.html

下载地址:http://www.rabbitmq.com/install-rpm.html


