#!/usr/bin/env bash

function install_sqlite(){
    # TODO: composer install

    # Precompiled sqlite Binaries for Windows and Linux
    sqlite_host_url=https://www.sqlite.org
    sqlite_year=2018
    sqlite_suffix=".zip"
    # linux
    sqlite_linux_sha1=2364ae04fc5a82ce0921e734e14fa540dec62746
    sqlite_linux_name=sqlite-tools-linux-x86-3220000
    sqlite_linux_download_url="${sqlite_host_url}/${sqlite_year}/${sqlite_linux_name}${sqlite_suffix}"
    # osx
    sqlite_osx_sha1=468e278de914ee22bab547beb67aca6c1e916e9e
    sqlite_osx_name=sqlite-tools-osx-x86-3220000
    sqlite_osx_download_url="${sqlite_host_url}/${sqlite_year}/${sqlite_osx_name}${sqlite_suffix}"
    # windows
    sqlite_windows_sha1=9b0e0a6dc63601f2ddb2028f44547d65b2da7d27
    sqlite_tools_windows_name=sqlite-tools-win32-x86-3220000
    sqlite_windows_download_url="${sqlite_host_url}/${sqlite_year}/${sqlite_tools_windows_name}${sqlite_suffix}"
    sqlite_dll_windows_sha1=94402e914b0caaacc7b5f9d8f41c6f6adb0fc0d7
    sqlite_dll_windows_name=sqlite-dll-win64-x64-3220000
    sqlite_dll_windows_download_url="${sqlite_host_url}/${sqlite_year}/${sqlite_dll_windows_name}${sqlite_suffix}"


    mkdir ${RUNTIME} -p
    if [ ${IS_WINDOWS} = 1 ];then
        download "${RUNTIME}${sqlite_tools_windows_name}${sqlite_suffix}" ${sqlite_windows_download_url} ${sqlite_windows_sha1}
        download "${RUNTIME}${sqlite_dll_windows_name}${sqlite_suffix}" ${sqlite_dll_windows_download_url} ${sqlite_dll_windows_sha1}
        unzip "${RUNTIME}${sqlite_tools_windows_name}${sqlite_suffix}" -d ${RUNTIME}
        unzip "${RUNTIME}${sqlite_dll_windows_name}${sqlite_suffix}" -d ${RUNTIME}
        mv ${RUNTIME}${sqlite_tools_windows_name}/* ${BIN}
        mv ${RUNTIME}sqlite3.def ${BIN}
        mv ${RUNTIME}sqlite3.dll ${BIN}
    elif [ ${IS_LINUX} = 1 ];then
        download "${RUNTIME}${sqlite_linux_name}${sqlite_suffix}" ${sqlite_linux_download_url} ${sqlite_linux_sha1}
        unzip "${RUNTIME}${sqlite_linux_name}${sqlite_suffix}" -d ${RUNTIME}
        mv ${RUNTIME}${sqlite_linux_name}/* ${BIN}
    elif [ ${IS_MAC} = 1 ];then
        download "${RUNTIME}${sqlite_osx_name}${sqlite_suffix}" ${sqlite_osx_download_url} ${sqlite_osx_sha1}
        unzip "${RUNTIME}${sqlite_osx_name}${sqlite_suffix}" -d ${RUNTIME}
        mv ${RUNTIME}${sqlite_osx_name}/* ${BIN}
    fi
    chmod a+x ${BIN}*
}