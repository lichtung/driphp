# XDebug
 XDebug是一个协助进行调试和开发的PHP扩展，它能让你检查数据类型，中断调试你的代码。它使用名为**DBGp**的调试协议，用于代替老旧的类GDB协议。
### 下载安装
Download the [Xdebug](https://xdebug.org/download.php) extension compatible with your version of PHP and save it in the php/ folder.

- The location of the php/ folder is defined during the installation of the PHP engine.
- If you are using an AMP package, the Xdebug extension may be already installed. Follow the instructions in the xdebug.txt.



### 将xdebug整合到php解释器中(Enabling Xdebug integration with the PHP interpreter)
- Open the active php.ini file in the editor:(使用编辑器打开php.ini，下面的步骤帮你寻找)
  - Open the Settings / Preferences Dialog by pressing Ctrl+Alt+S or by choosing File | Settings for Windows and Linux or PhpStorm | Preferences for macOS, and click PHP under Languages & Frameworks.
  - On the PHP page that opens, click browseButton.png next to the CLI Interpreter field.
  - In the CLI Interpreters dialog box that opens, the Configuration File read-only field shows the path to the active php.ini file. Click Open in Editor.
- To disable the Zend Debugger and Zend Optimizer tools, that blocks Xdebug, remove or comment the following lines in the php.ini file:
````
      zend_extension=<path_to_zend_debugger>
      zend_extension=<path_to_zend_optimizer>
````
zend_debugger和zend_optimizer会阻碍xdebug的调试，需要删除或者注释这些配置
- To enable Xdebug, locate the [Xdebug] section in the php.ini file and update it as follows:
````
[Xdebug]
zend_extension="D:\web\php70\ext\php_xdebug-2.5.5-7.0-vc14-x86_64.dll"
xdebug.remote_enable=1
xdebug.remote_port="9000"
xdebug.profiler_enable=1
xdebug.profiler_output_dir="D:\web\tmp"
xdebug.idekey="phpstorm"
````
修改Xdebug段使xdebug可用
- To enable multiuser debugging via Xdebug proxies, locate the xdebug.idekey setting and assign it a value of your choice. This value will be used to register your IDE on Xdebug proxy servers.
？？？
- Save and close the php.ini file.
### Configuring Xdebug in PhpStorm
- Open the Settings / Preferences Dialog by pressing Ctrl+Alt+S or by choosing File | Settings for Windows and Linux or PhpStorm | Preferences for macOS, and click PHP under Languages & Frameworks.
- Check the Xdebug installation associated with the selected PHP interpreter:
  - On the PHP page, choose the relevant PHP installation from the CLI Interpreter drop-down list and click the Browse button browseButton next to the field. The list shows all the PHP installations available in PhpStorm, see Configuring Local PHP Interpreters and Configuring Remote PHP Interpreters.
  - The CLI Interpreters dialog box that opens shows the following:
    - The version of the selected PHP installation.
    - The name and version of the debugging engine associated with the selected PHP installation (Xdebug or Zend Debugger). If no debugger is configured, PhpStorm shows Debugger: Not installed.

  Alternatively, open the Xdebug checker, paste the output of the phpinfo(), and click Analyze my phpinfo() output. Learn more about checking the Xdebug installation in Validating the Configuration of a Debugging Engine.
- Define the Xdebug behaviour. Click Debug under the PHP node. On the Debug page that opens, specify the following settings in the Xdebug area:
    - In the Debug Port text box, appoint the port through which the tool will communicate with PhpStorm. This must be exactly the same port number as specified in the php.ini file:

    ````
      xdebug.remote_port = <port_number>
    ````

    By default, Xdebug listens on port 9000.
    - To have PhpStorm accept any incoming connections from Xdebug engines through the port specified in the Debug port text box, select the Can accept external connections checkbox.
    - Select the Force break at the first line when no path mapping is specified checkbox to have the debugger stop as soon as it reaches and opens a file that is not mapped to any file in the project on the Servers page. The debugger stops at the first line of this file and Debug Tool Window. Variables shows the following error message: Cannot find a local copy of the file on server <path to the file on the server> and a link Click to set up mappings. Click the link to open the Resolve Path Mappings Problem dialog box and map the problem file to its local copy.
  When this checkbox cleared, the debugger does not stop upon reaching and opening an unmapped file, the file is just processed, and no error messages are displayed.

    - Select the Force break at the first line when the script is outside the project checkbox to have the debugger stop at the first line as soon as it reaches and opens a file outside the current project. With this checkbox cleared, the debugger continues upon opening a file outside the current project.
- In the External Connections area, specify how you want PhpStorm to treat connections received from hosts and through ports that are not registered as deployment server configurations.
  - Ignore external connections through unregistered server configurations: Select this checkbox to have PhpStorm ignore connections received from hosts and through ports that are not registered as deployment server configurations. When this checkbox is selected, PhpStorm does not attempt to create a deployment server configuration automatically.
  - Break at first line in PHP scripts: Select this checkbox to have the debugger stop as soon as connection between it and PhpStorm is established (instead of running automatically until the first breakpoint is reached). Alternatively turn on the Run | Break at first line in PHP scripts option on the main menu.
  - Max. simultaneous connections: Use this spin box to limit the number of external connections that can be processed simultaneously.
