# 核心
框架在运行不可或缺的部分统一归类为核心部分:

- Cache     缓存 缓存是指将一部分计算结果持久化,下一次执行时可以直接从持久化的数据中取
- Cookie    cookie控制
- Lang      国际化(多语言)
- Logger    日志
- Redis     redis管理 框架的缓存管理以及队列特性主要依赖于redis
- Request   输入管理类 可以获取$_GET,$_POST等数据以及客户端IP,浏览器类型等信息
- Response  输出控制类
- Router    URL路由类
- Servant   服务框架    面向命令行模式
- Session   Session管理
- Storage   文件存储
- Trace     页面trace     开发模式下可以查看请求消耗的资源以及一些变量
- View      视图(模板)类

        









