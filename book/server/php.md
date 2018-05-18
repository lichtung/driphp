#!/usr/bin/env bash
```html
Installing PHP SAPI module:       apache2handler
/usr/lib64/httpd/build/instdso.sh SH_LIBTOOL='/usr/lib64/apr-1/build/libtool' libphp7.la /usr/lib64/httpd/modules
/usr/lib64/apr-1/build/libtool --mode=install install libphp7.la /usr/lib64/httpd/modules/
libtool: install: install .libs/libphp7.so /usr/lib64/httpd/modules/libphp7.so
libtool: install: install .libs/libphp7.lai /usr/lib64/httpd/modules/libphp7.la
libtool: install: warning: remember to run `libtool --finish /root/php-7.1.15/libs'
chmod 755 /usr/lib64/httpd/modules/libphp7.so
[activating module `php7' in /etc/httpd/conf/httpd.conf]
Installing shared extensions:     /home/php71/lib/php/extensions/no-debug-non-zts-20160303/
Installing PHP CLI binary:        /home/php71/bin/
Installing PHP CLI man page:      /home/php71/php/man/man1/
Installing PHP FPM binary:        /home/php71/sbin/
Installing PHP FPM defconfig:     /home/php71/etc/
Installing PHP FPM man page:      /home/php71/php/man/man8/
Installing PHP FPM status page:   /home/php71/php/php/fpm/
Installing phpdbg binary:         /home/php71/bin/
Installing phpdbg man page:       /home/php71/php/man/man1/
Installing PHP CGI binary:        /home/php71/bin/
Installing PHP CGI man page:      /home/php71/php/man/man1/
Installing build environment:     /home/php71/lib/php/build/
Installing header files:          /home/php71/include/php/
Installing helper programs:       /home/php71/bin/
  program: phpize
  program: php-config
Installing man pages:             /home/php71/php/man/man1/
  page: phpize.1
  page: php-config.1
Installing PEAR environment:      /home/php71/lib/php/
[PEAR] Archive_Tar    - installed: 1.4.3
[PEAR] Console_Getopt - installed: 1.4.1
[PEAR] Structures_Graph- installed: 1.1.1
[PEAR] XML_Util       - installed: 1.4.2
[PEAR] PEAR           - installed: 1.10.5
Wrote PEAR system config file at: /home/php71/etc/pear.conf
You may want to add: /home/php71/lib/php to your php.ini include_path
/root/php-7.1.15/build/shtool install -c ext/phar/phar.phar /home/php71/bin
ln -s -f phar.phar /home/php71/bin/phar
Installing PDO headers:           /home/php71/include/php/ext/pdo/
```