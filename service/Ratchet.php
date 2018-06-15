<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 14:40
 */
declare(strict_types=1);


namespace driphp\service;


use driphp\core\Service;

/**
 * Class Ratchet
 * TODO：
 *
 * Everything You Know (about the web) is Wrong
 *
 * HTTP请求是无状态的 (Stateless)
 * You make a request to http://socketo.me/docs/hello-world, what happens? Your browser opens up a socket port to 80 on socketo.me,
 * sends an HTTP header request to the server (Apache/Nginx), it buffers that message and sends it to the server application.
 * The server application decides what to do with the request, fetches data, generates HTML and sends it back to the server
 * (Apache/Nginx). It then adds the appropriate(合适的) HTTP headers to the body, sends it back to the browser and closes the connection.
 *
 * Websites maintain a knowledge of who you are by cookies. Cookies are passed back and fourth for every request made to keep reminding
 * the server "hey, I'm me, the same guy as last time". This, among other things, carries overhead and is open to security vulnerabilities (if not properly secured).
 * 网站通过cookie知道你的身份，每次请求cookie在服务器和客户端之间来回传递，这可能会带来过载和安全漏洞（如果没有合适的安全措施）
 *
 * All communication is client initiated and each stateless request/response is isolated.
 * 所有的通信都是客户端发起并且每个请求都是独立的
 *
 * WebSockets：
 * WebSockets are a bi-directional（双向的）, full-duplex 全双工的, persistent connection from a web browser to a server.
 * Once a WebSocket connection is established the connection stays open until the client or server decides to close this connection.
 * With this open connection, the client or server can send a message at any given time to the other.
 * This makes web programming entirely event driven 事件驱动, not (just) user initiated 用户发起. It is stateful 有状态的.
 * As well, at this time, a single running server application is aware of all connections, allowing you to communicate
 * with any number of open connections at any given time.
 *
 * On the client end they're already natively in Chrome, Firefox, Opera and Safari* (including mobile Safari)*.
 * On the Internet Explorer front they're available in IE10 as a plugin, while it's still considered a prototype .
 * IE10上的Websocket还处于起步阶段
 * In addition, any browser that does not support WebSockets can use a Flash polyfill.
 *
 * @package driphp\service
 */
class Ratchet extends Service
{
}