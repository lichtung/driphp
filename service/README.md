# 基于第三方库的服务


[http://symfony.com/doc/current/service_container.html](http://symfony.com/doc/current/service_container.html)
Your application is full of useful objects: a "Mailer" object might help you send emails while another object might help you save things to the database. Almost everything that your app "does" is actually done by one of these objects. And each time you install a new bundle, you get access to even more!

In Symfony, these useful objects are called services and each service lives inside a very special object called the service container. The container allows you to centralize the way objects are constructed. It makes your life easier, promotes a strong architecture and is super fast!

简单地说，服务就是帮助你完成一件事，而你不需要关心更多细节

core目录下内置了很多快速服务，当然你也可以选择体验更稳定强大的第三方库的功能。