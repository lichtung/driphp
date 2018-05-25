#!/usr/bin/env expect
set timeout -1
spawn ssh user@host
expect {
    "passw" { send "123456\n"}
}
interact