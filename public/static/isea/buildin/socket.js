/**
 * Created by linzh on 2017-02-18.
 */

/**
 *
 * var io = new isea.socket({
 *        server: 'http://127.0.0.1:1992',
 *       id: "lin"
 *   });
 * document.getElementById("hel").onclick = function () {
 *        io.emit("hello world");
 *   };
 * @param options
 * @returns {{emit: emit}}
 */
isea.socket = function (options) {
    var option = {
        /* 服务端地址 */
        server: "http://127.0.0.1:1992",
        /* 链接id和密码（链接凭证） */
        id: "",
        pwd: "",
        /* 回调事件 */
        error: function () {
            console.log("connect error", arguments);
        },
        message: function (data) {
            console.log("receive data from server", data);
        },
        connect: function () {
            console.log("connect success");
        },
        disconnect: function () {
            console.log("connection lost");
        }
    };
    for (var x in options) {
        option[x] = options[x];
    }

    // 如果服务端不在本机，请把127.0.0.1改成服务端ip
    var socket = io(option.server);
    socket.on("error", option.error);
    socket.on('message', option.message);
    // 当连接服务端成功时
    socket.on('connect', function () {
        if (!option.id) {
            throw "socket require id";
        }
        socket.emit("register", {"id": option.id, "pwd": option.pwd});
        option.connect();
    });
    /* 服务端断开连接时调用 */
    socket.on('disconnect', option.disconnect);

    return {
        emit: function (content, type) {
            socket.emit("message", {
                /* type === 0 时表示普通的消息 */
                type: type || 0,
                content: content
            });
        },
        close:function () {
            socket.close();
        }
    };
};