#!/usr/bin/env bash
dd if=/dev/zero of=/home/swap bs=1M count=1024
chmod 0600 /home/swap
mkswap /home/swap
swapon /home/swap