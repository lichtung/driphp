#!/usr/bin/env bash

    plugins=(
        cordova-plugin-console
        cordova-plugin-contacts
        cordova-plugin-http
        cordova-plugin-camera
        cordova-plugin-battery-status
        cordova-plugin-device
        cordova-plugin-file
        cordova-plugin-file-transfer
        cordova-plugin-geolocation
        "git+https://github.com/phonegap/phonegap-plugin-barcodescanner.git"
        cordova-plugin-dialogs
        cordova-plugin-whitelist
    )


# IOS真机调试
# - 打开Xcode > Preferences ,选择Account页，Add Apple ID ，之后选择添加的账户 ，点击 Manage Certifivates...点击添加证书
# 添加完成右键export到文件系统中，之后可以在文件系统中找到XXX.p12,这就是用于测试的证书
# - 进入项目执行cordova build ios，之后在项目的platforms／ios目录下中可以看到YYY.xcodeproj文件，双击在xcode中打开
# - xcode中选中项目文件夹，在General页中设置Display Name，Bundle Identifier，并勾选Signing下的Automatically manage signing，选择
# 自己的证书对应的Team（如果出现错误，可能是Bundle Identifier命名不正确导致的）
# - 顶部一栏选择自己的设备，第一次运行时可能会出现"xcode is busy: Processing symbol files"，稍等一下就好

# ps： 20170702上真机时出现这样的问题"Command /usr/bin/codesign failed with exit code 1"，原因未知，总之第二天重新开机时好了

# create a cordova project within android and ios platform
function create_cordova(){
    PACKAGE=${1}
    cd ${HOME_DIR}/../
    cordova create ${PACKAGE} ${PACKAGE}
    cd ${PACKAGE}
    # add android platform
    cordova platform add android
    cordova platform add ios
    # check platform support
    # Android target: not installed
    # ~/${PACKAGE}/platforms/android/project.properties 中 target 指定的API ("target=android-26")版本未安装时提示
    cordova requirements

    # 需要插件 @see http://cordova.apache.org/docs/en/latest/guide/appdev/whitelist/index.html
    cordova plugin add cordova-plugin-whitelist

}

function run_cordova(){
    PACKAGE=${1}
    PLATFORM=${2}
    SERIALS=${3}
    cd ${HOME_DIR}/../${PACKAGE}
    cd ../${PACKAGE}
    if [ -z ${PLATFORM} ]; then
        PLATFORM=android
    fi
    # select device using serial number
    if [ ! -z ${SERIALS} ]; then
        DEV=" -s ${SERIALS} "
    else
        DEV=""
    fi
#    You have not accepted the license agreements of the following SDK components:
#  [Android SDK Build-Tools 26.0.2].
#  Before building your project, you need to accept the license agreements and complete the installation of the missing components using the Android Studio SDK Manager.
#  Alternatively, to learn how to transfer the license agreements from one workstation to another, go to http://d.android.com/r/studio-ui/export-licenses.html
    yes | sudo sdkmanager --licenses
    cordova ${DEV} run ${PLATFORM}
}

function fullish_plugins_cordova(){
        cd ${PACKAGE}
        i=0
        while(( ${i} < ${#plugins[*]} ))
        do
            echo "to install cordova plugins add ${plugins[${i}]}"
            cordova plugins add ${plugins[${i}]}
            let "i++"
        done
        cd ${RUNTIME}
}

function release_cordova(){

        cd ${2}
        if [ -n ${3} ]; then
            PLATFORM=android
        else
            PLATFORM=${3}
        fi
        build_path=${RUNTIME}/${PACKAGE}/platforms/android/build/outputs/apk/android-release.apk
        release_path=${RUNTIME}/${PACKAGE}.apk
        if [ -f ${build_path} ]; then
            unlink ${build_path}
        fi
        if [ -f ${release_path} ]; then
            unlink ${release_path}
        fi
        cordova build ${PLATFORM} --release
        cd ${RUNTIME}
        echo -e "------------------- ${RUNTIME} ------------------------------"
        if [ -f ${build_path} ]; then
            cp ${build_path} ${release_path}
            println "success to build release "
        else
            println "failed to build release "
        fi
}