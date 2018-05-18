#!/usr/bin/env bash
source _env.sh


insert2file 2 "# chkconfig: 345 85 15" ./a
insert2file 3 "# description: Apache Web Server" ./a