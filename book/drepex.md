# Introduction
**dripex** is a php mvc framework with less limitation and fine structure.
This name is combined with **drip** and **php**, which idea from "**三体**" and aim of small, strong and swift. 



# template engine
use twig

# Database
use doctrine/orm

# Relation to Symfony
Symfony是我见过的




# 问题：res/xml/config.xml 经常被覆盖

# Cordova js调用java代码

步骤：

- 创建CordovaPlugin子类
```java
package com.dripex;

import org.apache.cordova.CordovaPlugin;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

public class Dripin extends CordovaPlugin {
    public void speak(String content){
        Log.e("SpeechOFFSynthesize",content);
    }

    @Override
    public boolean execute(String action, JSONArray args, CallbackContext callbackContext) throws JSONException {
        if("speak".equals(action)){
            //speechSynthesize
            String content = args.getString(0);
            speak(content);
            callbackContext.success("finish");//如果不调用success回调，则js中successCallback不会执行
            return true;
        }
        return false;
    }
}

``` 
- 配置插件 
res/xml/config.xml中加入
```xml
<feature name="dripex">
    <param name="android-package" value="com.dripex.Dripin" />
</feature>
```

- js调用
```javascript
    document.getElementById("button").onclick = function(){
         cordova.exec(success, fail, "SpeechOFFSynthesize", "speak", ["haha"]);
    };
    var success = function(message){
            alert("success = "+message);
         };
    var fail = function(message){
            alert("fail = "+message);
         };
```

# java调用JS
```java
public class MainActivity extends CordovaActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // Set by <content src="index.html" /> in config.xml
        loadUrl(launchUrl);
        new Thread(new Runnable() {
            @Override
            public void run() {
                try {
                    Thread.sleep(10000);
                    runOnUiThread(new Runnable() {
                        @Override
                        public void run() {
                            //不能在子线程中执行webview的方法
                            loadUrl("javascript:showAlert(\"你好\")");
                        }
                    });
                } catch (InterruptedException e) {
                    e.printStackTrace();
                }
            }
        }).start();
    }
}
```