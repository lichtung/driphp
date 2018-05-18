# Install Docker CE
You can install Docker CE in different ways, depending on your needs:

- **SET UP THE REPOSITORY** Most users set up Docker’s repositories and install from them, for ease of installation and upgrade tasks. This is the recommended approach.
 方便安装和升级
- **Install from a package** Some users download the RPM package and install it manually and manage upgrades completely manually. This is useful in situations such as installing Docker on air-gapped systems with no access to the internet.

- In testing and development environments, some users choose to use automated convenience scripts to install Docker.

# SET UP THE REPOSITORY
```bash
# Install required packages. yum-utils provides the yum-config-manager utility, and device-mapper-persistent-data and 
# lvm2 are required by the devicemapper storage driver.
yum install -y yum-utils device-mapper-persistent-data lvm2
# Use the following command to set up the stable repository. You always need the stable repository, even if you want to 
# install builds from the edge or test repositories as well.
yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# INSTALL DOCKER CE
```
