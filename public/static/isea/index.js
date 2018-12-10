/**
 * Created by linzhv on 11/18/16.
 *
 *
 * Note:
 *  querySelector() only return the first match
 *  querySelectorAll return all pattern matches
 */
/**
 * datetimepicker "Y-MM-DD HH:mm:ss"
 * @type {string}
 */

//fake-constant define
var BREAK = '[break]';
var CONTINUE = '[continue]';
var BASE_DIR = null;
var BROWSER = {};//{type: "Chrome", version: "50.0.2661.94"}

// if(typeof PUBLIC_URL == 'undefined'){
//     PUBLIC_URL = '/';//script parent url
// }
//style

var isea = (function (callback_while_all_ready_done) {
    "use strict";
    var LIB = {
        jquery: 'http://cdn.bootcss.com/jquery/2.2.4/jquery.min.js'
    };
    var _ht = null;
    var dazzling = 'a,button,code,div,img,input,label,li,p,pre,select,span,svg,table,td,textarea,th,ul' +
        '{cursor:default;-webkit-border-radius:0!important;-moz-border-radius:0!important;border-radius:0!important}' +
        '.img-circle{border-radius:50%!important}' +
        '::-webkit-scrollbar{width:12px;height:12px}' +
        '::-webkit-scrollbar-track{background-color:#d3d3d3;-webkit-border-radius:5px}' +
        '::-webkit-scrollbar-thumb{background-color:rgba(0,0,0,.5);-webkit-border-radius:5px}::-webkit-scrollbar-thumb:hover{background-color:rgba(0,0,0,.8)}';
    var URL = window["URL"] || window["webkitURL"] || window["mozURL"] || window["msURL"];
    navigator.saveBlob = navigator.saveBlob || navigator["msSaveBlob"] || navigator["mozSaveBlob"] || navigator["webkitSaveBlob"];
    window.saveAs = window.saveAs || window["webkitSaveAs"] || window["mozSaveAs"] || window["msSaveAs"];

    function each(obj, call, meta) {
        var result;
        for (var key in obj) {
            if (!obj.hasOwnProperty(key)) continue;
            result = call(obj[key], key, meta);
            if (result === BREAK) break;
            if (result === CONTINUE) continue;
            if (result !== undefined) return result;
        }
    }

    var util = {
        repeat: function (target, n) {
            var s = target, total = "";
            n = parseInt(n);
            while (n > 0) {
                if (n % 2 == 1) {
                    total += s;
                }
                if (n == 1) {
                    break;
                }
                s += s;
                n = n >> 1;//相当于将n除以2取其商，或者说是开2次方
            }
            return total;
        },
        /** check if key exist and the value is not empty */
        notempty: function (optname, obj, dft) {
            return obj ? (obj.hasOwnProperty(optname) && obj[optname]) : (dft || false);
        },
        /* php in_array */
        inArray: function (value, array, strict) {
            var flag = false;
            each(array, function (item) {
                if (strict ? (value === item) : (value == item)) {
                    flag = true;
                    return BREAK;
                }
            });
            return flag;
        },
        /**
         * get the type of variable
         * @returns string :"number" "string" "boolean" "object" "function" 和 "undefined"
         */
        gettype: function (o) {
            if (o === null) return "null";//object
            if (o === undefined) return "undefined";
            return Object.prototype.toString.call(o).slice(8, -1).toLowerCase();
        },
        isFunc: function (obj) {
            return this.gettype(obj) === "function";
        },
        isObj: function (obj) {
            return this.gettype(obj) === "object";
        },
        toObj: function (json) {
            return this.isObj(json) ? json : eval("(" + json + ")");
        },
        isStr: function (el) {
            return this.gettype(el) === "string";
        },
        parsestr: function (str) {
            if (str) {
                var data = decodeURI(str).split("&");
                var temp = {};
                for (var x in data) {
                    var kv = data[x].split("=");
                    temp[kv[0]] = kv[1];
                }
                return temp;
            } else {
                return {};
            }
        },
        /**
         * check if attributes is in the object
         * @return int 1-all,0-none,-1-exist_of_part
         */
        prop: function (obj, properties) {
            var count = 0;
            if (!Array.isArray(properties)) properties = [properties];
            for (var i = 0; i < properties.length; i++)if (obj.hasOwnProperty(properties[i])) count++;
            return count === properties.length ? 1 : (count === 0 ? 0 : -1);
        }
    };

    function init(config, target, cover) {
        each(config, function (item, key) {
            if (cover || (cover === undefined)) {
                target[key] = item;
            }
        });
        return this;
    }

    function guid() {
        var s = [];
        var hexDigits = "0123456789abcdef";
        for (var i = 0; i < 36; i++) s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
        s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
        s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
        s[8] = s[13] = s[18] = s[23] = "-";
        return s.join("");
    }

    function getResourceType(path) {
        var type = path.substring(path.length - 3);
        switch (type) {
            case 'css':
                type = 'css';
                break;
            case '.js':
                type = 'js';
                break;
            case 'ico':
                type = 'ico';
                break;
            default:
                throw path;
        }
        return type;
    }

    //compatability
    (function () {
        window.console || (window.console = (function () {
            var c = {};
            c.log = c.warn = c.debug = c.info = c.error = c.time = c.dir = c.profile = c.clear = c.exception = c.trace = c.assert = function () {
            };
            return c;
        })());

        if (!Array.isArray) Array.isArray = function (el) {
            return util.gettype(el) === "array";
        };


        Array.prototype.indexOf = function (elt) {
            var len = this.length >>> 0;
            var from = Number(arguments[1]) || 0;
            from = (from < 0) ? Math.ceil(from) : Math.floor(from);
            if (from < 0) from += len;
            for (; from < len; from++) {
                if (from in this && this[from] === elt) return from;
            }
            return -1;
        };
        // Array.prototype.max = function () {
        //     return Math.max.apply({}, this);
        // };
        // Array.prototype.min = function () {
        //     return Math.min.apply({}, this);
        // };

        each({
            trim: function () {
                return this.replace(/(^\s*)|(\s*$)/g, '');
            },
            beginWith: function (chars) {
                return this.indexOf(chars) === 0;
            },
            endWith: function (chars) {
                return this.length === (chars.length + this.indexOf(chars));
            }
        }, function (v, i) {
            if (!String.prototype[i]) String.prototype[i] = v;
        });
    })();

    var datetime = (function () {

        //获取当前时间戳
        var timestamp = function (datetime) {
            var milli = datetime ? Date.parse(datetime) : new Date().getTime();
            return parseInt(milli / 1000);
        };
        //时间戳转日期
        var datetime = function (timestamp) {
            var date = timestamp ? new Date(parseInt(timestamp) * 1000) : new Date();
            var Y = date.getFullYear();
            var _m = date.getMonth();
            var M = (_m + 1 < 10 ) ? '0' + (_m + 1) : _m + 1;
            var D = date.getDate();
            var H = date.getHours();
            H = H < 10 ? "0" + H : H;
            var m = date.getMinutes();
            m = m < 10 ? "0" + m : m;
            var s = date.getSeconds();
            s = s < 10 ? "0" + s : s;
            return Y + '-' + M + '-' + D + ' ' + H + ':' + m + ':' + s;
        };
        return {
            timestamp2datetime: function (timestamp) {
                var date = new Date(parseInt(timestamp) * 1000);
                var Y = date.getFullYear();
                var _m = date.getMonth();
                var M = (_m + 1 < 10 ) ? '0' + (_m + 1) : _m + 1;
                var D = date.getDate();
                var H = date.getHours();
                H = H < 10 ? "0" + H : H;
                var m = date.getMinutes();
                m = m < 10 ? "0" + m : m;
                var s = date.getSeconds();
                s = s < 10 ? "0" + s : s;
                return Y + '-' + M + '-' + D + ' ' + H + ':' + m + ':' + s;
            },
            datetime2timestamp: function (date) {
                // 日期转时间戳(北京时间)
                return Date.parse(date) / 1000;
            },
            date2unix: function (string) {
                var f = string.split(' ', 2);
                var d = (f[0] ? f[0] : '').split('-', 3);
                var t = (f[1] ? f[1] : '').split(':', 3);
                return (new Date(
                        parseInt(d[0], 10) || null,
                        (parseInt(d[1], 10) || 1) - 1,
                        parseInt(d[2], 10) || null,
                        parseInt(t[0], 10) || null,
                        parseInt(t[1], 10) || null,
                        parseInt(t[2], 10) || null
                    )).getTime() / 1000;
            },
            //@precated
            time: function () {
                return (new Date()).valueOf();
            },
            // 获取本地时间戳(如果了参数,则获取这个时间的北京时间戳)
            timestamp: timestamp,
            datetime: datetime
        };
    })();

    //get the position of this file
    (function () {
        // console.log(location, dirname(location.pathname));
        // BASE_DIR = location.pathname.replace(/\\/g, '/').replace(/\/[^\/]*$/, '') + "/";
        var scripts = document.getElementsByTagName("script");
        each(scripts, function (script) {
            if (script.src && script.src.endWith("/isea/index.js")) {
                BASE_DIR = script.src.replace("/isea/index.js", "/");
                return BREAK;
            }
        });
    })();

    (function () {
        var v, tp = {};
        var ua = navigator.userAgent.toLowerCase();
        (v = ua.match(/msie ([\d.]+)/)) ? tp.ie = v[1] :
            (v = ua.match(/firefox\/([\d.]+)/)) ? tp.firefox = v[1] :
                (v = ua.match(/chrome\/([\d.]+)/)) ? tp.chrome = v[1] :
                    (v = ua.match(/opera.([\d.]+)/)) ? tp.opera = v[1] :
                        (v = ua.match(/version\/([\d.]+).*safari/)) ? tp.safari = v[1] : 0;
        if (tp.ie) {
            BROWSER.type = "ie";
            BROWSER.version = parseInt(tp.ie);
        } else if (tp.firefox) {
            BROWSER.type = "firefox";
            BROWSER.version = parseInt(tp.firefox);
        } else if (tp.chrome) {
            BROWSER.type = "chrome";
            BROWSER.version = parseInt(tp.chrome);
        } else if (tp.opera) {
            BROWSER.type = "opera";
            BROWSER.version = parseInt(tp.opera);
        } else if (tp.safari) {
            BROWSER.type = "safari";
            BROWSER.version = parseInt(tp.safari);
        } else {
            BROWSER.type = "unknown";
            BROWSER.version = 0;
        }
    })();

    var client = {
        viewport: function () {
            var win = window;
            var type = 'inner';
            if (!('innerWidth' in win)) {
                type = 'client';
                win = document.documentElement ? document.documentElement : document.body;
            }
            return {
                width: win[type + 'Width'],
                height: win[type + 'Height']
            };
        },
        redirect: function (url) {
            location.href = url;
        },

        /**
         * get the hash of uri
         */
        hash: function () {
            if (!location.hash) return "";
            var hash = location.hash;
            var index = hash.indexOf('#');
            if (index >= 0) hash = hash.substring(index + 1);
            return "" + decodeURI(hash);
        },

        /**
         * get script path
         * there are some diffrence between domain access(virtual machine) and ip access of href
         * domian   :http://192.168.1.29:8085/edu/Public/admin.php/Admin/System/Menu/PageManagement#dsds
         * ip       :http://edu.kbylin.com:8085/admin.php/Admin/System/Menu/PageManagement#dsds
         * what we should do is SPLIT '.php' from href
         * ps:location.hash
         */
        base: function () {
            var href = location.href;
            var index = href.indexOf('.php');
            if (index > 0) {//exist
                return href.substring(0, index + 4);
            } else {
                if (location.origin) {
                    return location.origin;
                } else {
                    return location.protocol + "//" + location.host;//default 80 port
                }
            }
        },
        parse: function (queryString) {
            var o = {};
            if (queryString) {
                queryString = decodeURI(queryString);
                var arr = queryString.split("&");
                for (var i = 0; i < arr.length; i++) {
                    var d = arr[i].split("=");
                    o[d[0]] = d[1] ? d[1] : '';
                }
            }
            return o;
        }
    };

    var dom = {
        /**
         * 按照“元素名”“id”“类名”的顺序解析
         * @param elementName string
         * @param attributes object
         * @param innerHtml string
         * @returns {Element}
         */
        create: function (elementName, attributes, innerHtml) {
            var clses, id;
            if (elementName.indexOf('.') > 0) {
                clses = elementName.split(".");
                elementName = clses.shift();
            }
            if (elementName.indexOf("#") > 0) {
                var tempid = elementName.split("#");
                elementName = tempid[0];
                id = tempid[1];
            }

            var el = document.createElement(elementName);
            id && el.setAttribute('id', id);
            if (clses) {
                var ct = '';
                each(clses, function (v) {
                    ct += v + " ";
                });
                el.setAttribute('class', ct);
            }

            util.isObj(attributes) && each(attributes, function (v, k) {
                el.setAttribute(k, v);
            });
            if (innerHtml) el.innerHTML = innerHtml;
            return el;
        },
        /**
         * 检查dom对象是否存在指定的类名称
         * @param obj
         * @param cls
         * @returns {Array|{index: number, input: string}}
         */
        hasClass: function (obj, cls) {
            return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
        },
        /**
         * 添加类
         * @param obj
         * @param cls
         */
        addClass: function (obj, cls) {
            if (!this.hasClass(obj, cls)) obj.className += " " + cls;
        },
        /**
         * 删除类
         * @param obj
         * @param cls
         */
        removeClass: function (obj, cls) {
            if (this.hasClass(obj, cls)) {
                var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
                obj.className = obj.className.replace(reg, ' ');
            }
        },
        /**
         * 逆转类
         * @param obj
         * @param cls
         */
        toggleClass: function (obj, cls) {
            if (this.hasClass(obj, cls)) {
                this.removeClass(obj, cls);
            } else {
                this.addClass(obj, cls);
            }
        },
        remove: function (dom) {
            dom.parentNode.removeChild(dom);
        }
    };
    var cookie = {
        //注意，存入布尔值时将取出string类型的true或者false，需要继续进行转换
        set: function (name, value, expire, path) {
            var cookie;
            if (undefined === expire || false === expire) {
                //set or modified the cookie, and it will be remove while leave from browser
                cookie = name + "=" + value;
            } else if (!isNaN(expire)) {// is numeric
                var _date = new Date();//current time
                if (expire > 0) {
                    //大于0时设置缓存时间，按毫秒计算
                    _date.setTime(_date.getTime() + expire);//count as millisecond
                } else if (expire === 0) {
                    //等于0时缓存100年
                    _date.setDate(_date.getDate() + 36500);
                } else {
                    //小于0时删除cookie
                    _date.setDate(_date.getDate() - 1);//expire after an year
                }
                cookie = name + "=" + value + ";expires=" + _date.toUTCString();
            } else {
                return;
            }
            document.cookie = cookie + ";path=" + (path ? path : '/');
        },
        //get a cookie with a name
        get: function (name, dft) {
            if (document.cookie.length > 0) {
                var cstart = document.cookie.indexOf(name + "=");
                if (cstart >= 0) {
                    cstart = cstart + name.length + 1;
                    var cend = document.cookie.indexOf(';', cstart);//begin from the index of param 2
                    (-1 === cend) && (cend = document.cookie.length);
                    return document.cookie.substring(cstart, cend);
                }
            }
            return dft || "";
        }
    };

    var loader = {
        library: {
            _: {},
            parse: function (name) {
                if (name.indexOf('/') >= 0) {
                    name = name.split('/');
                    name = name[name.length - 1];
                }
                return name;
            },
            has: function (name) {
                return this.parse(name) in this._;
            },
            add: function (name) {
                this._[this.parse(name)] = true;
                return this;
            }
        },
        stack: [],
        shift: function () {
            if (this.stack.length) {
                return this.stack.shift();
            } else {
                return false;
            }
        },
        push: function (path) {
            var env = this;
            env.stack.push(path);
            return env;
        },
        pathful: function (path) {
            if (!path.beginWith("http") && !path.beginWith("/")) {
                path = BASE_DIR + path;
            }
            return path;
        },
        /**
         * 加载资源
         * 内部实现了堆栈记录加载的js等资源的情况
         * PS:在大型应用里面可能会因内存的大量占据而导致js执行过慢
         * @param path string 加载路径
         * @param call callable 加载完毕后的回调机制
         * @param islastone boolean 标记是否是最后一个js文件，用于多个js同时加载完成后再执行回调的机制
         * @returns {boolean}
         * @private
         */
        _load: function (path, call, islastone) {
            var type = getResourceType(path);
            var env = this, isjs = type === "js";
            //Note: using "document.write('<link .....>')" may cause load out of order
            if (!env.library.has(path)) {
                switch (type) {
                    /* ico和css资源直接进行回调，不论加载结果如何 */
                    case 'css':
                        env.append2Header(dom.create("link", {
                            href: path,
                            rel: "stylesheet",
                            type: "text/css"
                        }));
                        call && call(islastone);
                        break;
                    case 'ico':
                        env.append2Header(dom.create("link", {
                            href: path,
                            rel: "shortcut icon"
                        }));
                        call && call(islastone);
                        break;
                    case 'js':
                        isjs = true;
                        env.waitLoadone(env.append2Header(dom.create("script", {
                            src: path
                        })), function () {
                            //js资源未夹杂完毕的情况下等待加载完毕后再回调
                            call && call(islastone);
                        });
                        break;
                    default:
                        throw "undefined type";
                }
                /* mark this path has pushed */
                env.library.add(path);
            } else {
                //已经加载完毕的直接回调，不论是js或者非js
                call && call(islastone);
            }
            return isjs;
        },
        // run autoload in order and continue if another one to load exist
        // parameter 2 means if it wait current load done and go next
        run: function () {
            var env = this;
            if (env.stack.length) {
                var pack = env.stack.shift();
                if (!pack) {
                    return;
                }
                var path = pack[0];
                var call = pack[1];

                if (Array.isArray(path)) {
                    var len = path.length;
                    var loadItem = function (index, callback) {
                        var p = env.pathful(path[index]);
                        if (index == len - 1) {
                            // console.log("go last one", p, index);
                            //last one
                            env._load(p, callback, true);
                        } else {
                            // console.log("go next one ", p, index);
                            env._load(p, function () {
                                //load next
                                loadItem(1 + index, callback);
                            }, false);
                        }
                    };
                    return loadItem(0, call);
                } else {
                    this._load(this.pathful(path), call, true);
                }
                env.stack.length && env.run(call);
            }
        },
        append2Header: function (ele) {
            _ht || (_ht = document.getElementsByTagName("head")[0]);
            _ht.appendChild(ele);
            return ele;
        },
        waitLoadone: function (ele, call) {
            if (ele.readyState) { //IE
                ele.onreadystatechange = function () {
                    if (ele.readyState == "loaded" || ele.readyState == "complete") {
                        ele.onreadystatechange = null;
                        call && call();
                    }
                };
            } else { //Others
                call && (ele.onload = call)
            }
        },
        use: function (buildinName, callback) {
            var env = this, toload = [];
            if (buildinName.indexOf(",") !== -1) {
                buildinName = buildinName.split(",");
                each(buildinName, function (val) {
                    toload.push(env._topath(val));
                });
            } else {
                toload.push(env._topath(buildinName));
            }
            return env.load(toload, callback);
        },
        _topath: function (buildinName) {
            if (buildinName in LIB) {
                return LIB[buildinName];
            } else {
                var src = BASE_DIR + "isea/buildin";
                if (!buildinName.beginWith("/")) src += "/";
                return src + buildinName + ".js";
            }
        },
        _loadStack: [],
        /**
         * load resource for page
         * multiple load will go the diffirent process
         */
        load: function (path, call) {
            this.push([path, call]).run();
            return this;
        }
    };

    var readyStack = {
        heap: [], /*fifo*/
        stack: []/*folo*/
    };
    var flag_page_load_done = false;
    // parameters for loadone
    var parameters_for_ready_done_callback = {
        plugins: [] /* plugin load order */
    };
    document.onreadystatechange = function () {
        if (util.inArray(document.readyState, ["complete", "loaded"])) {
            document.onreadystatechange = null;
            var i;
            for (i = 0; i < readyStack.heap.length; i++) (readyStack.heap[i])();
            for (i = readyStack.stack.length - 1; i >= 0; i--) (readyStack.stack[i])();
            flag_page_load_done = true;
            callback_while_all_ready_done && callback_while_all_ready_done(parameters_for_ready_done_callback);
        }
    };

    // http_build_query
    function buildQuery(parameters) {
        if (util.isObj(parameters)) {
            var qs = "";
            each(parameters, function (value, key) {
                qs += encodeURIComponent(key) + "=" + encodeURIComponent(value) + "&";
            });
            qs = qs.substring(0, qs.length - 1); //chop off last "&"
            return qs;
        } else {
            return parameters;
        }
    }

    // for ie7+
    var ajax = (function () {

        function get(url, callback, async) {
            var xmlhttp = XMLHttpRequest();
            url += (url.indexOf("?") === -1 ? "?" : "&") + "__t=" + Math.random();//避免缓存
            xmlhttp.onreadystatechange = function () {
                if (xmlhttp.readyState == 4) {
                    callback(xmlhttp.responseText, xmlhttp.status);
                }
            };
            xmlhttp.open("GET", url, async || true);
            xmlhttp.send();

        }

        function post(url, data, callback, async) {
            var xmlhttp = XMLHttpRequest();
            url += (url.indexOf("?") === -1 ? "?" : "&") + "__t=" + Math.random();//避免缓存
            xmlhttp.open("GET", url, async || true);
            if (data) {
                xmlhttp.send(buildQuery(data));
            } else {
                xmlhttp.send();
            }
        }

    })();

    /**
     * _params函数解析发送的data数据，对其进行URL编码并返回
     * @param data
     * @param key
     * @returns {string}
     */
    function parseAjaxData(data, key) {
        var params = '';
        key = key || '';
        var type = {'string': true, 'number': true, 'boolean': true};
        if (type[typeof(data)])
            params = data;
        else
            for (var i in data) {
                if (type[typeof(data[i])])
                    params += "&" + key + (!key ? i : ('[' + i + ']')) + "=" + data[i];
                else
                    params += parseAjaxData(data[i], key + (!key ? i : ('[' + i + ']')));
            }
        return !key ? encodeURI(params).replace(/%5B/g, '[').replace(/%5D/g, ']') : params;
    }

    function ajax(obj) {
        if (!obj.url)
            return;
        var xmlhttp = new XMLHttpRequest() || new ActiveXObject('Microsoft.XMLHTTP');    //这里扩展兼容性
        var type = (obj.type || 'POST').toUpperCase();
        xmlhttp.onreadystatechange = function () {    //这里扩展ajax回调事件
            if (xmlhttp.readyState == 4) {
                console.log(xmlhttp.responseText);


                var isSuccess = xmlhttp.status == 200;
                if (isSuccess && !!obj.success) {
                    obj.success(xmlhttp.responseText);
                }
                if (!isSuccess && !!obj.error) {
                    obj.error();
                }
                obj.complete && obj.complete(isSuccess);
            }
        };
        if (type == 'POST') {
            xmlhttp.open(type, obj.url, obj.async || true);
            xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xmlhttp.send(parseAjaxData(obj.data || null));
        }
        else if (type == 'GET') {
            xmlhttp.open(type, obj.url + '?' + parseAjaxData(obj.data || null), obj.async || true);
            xmlhttp.send(null);
        }
    }


    return {
        /**
         * var pretags = document.getElementsByTagName('pre') //each => eval(el.innerHTML)
         */
        notify: {
            solve: function (call) {
                var src = "http://cdn.bootcss.com/humane-js/3.2.2/", env = this;
                isea.loader.load([src + 'themes/flatty.min.css', src + 'themes/libnotify.min.css', src + 'humane.min.js'],
                    call ? function () {
                        call(env);
                    } : null);
            },
            show: function (msg, intop) {
                this.solve(function () {
                    humane.baseCls = 'humane-' + (intop ? "flatty" : "libnotify");
                    humane.log(msg);
                });
            }
        },
        loading: {
            ele: null,
            init: function () {
                var viewport = isea.client.viewport();
                if (!this.ele) {
                    this.ele = document.body.appendChild(isea.dom.create("div#loadingPage", {
                        "style": 'position:fixed;left:0;top:0;width:100%;height:' + viewport.height + 'px;background:#fff;opacity:1;filter:alpha(opacity=100);z-index:999;'
                    }, '<div id="loadingTips" style="position: absolute; cursor: wait; width: auto;border:#bbb 1px solid ; height:80px; line-height:80px; padding-left:40px; padding-right:40px;border-radius:10px;background:#fff ; color:#666;font-size:20px;;z-index:998"> Loading ... </div></div>'));
                }
                var loadingTips = document.getElementById("loadingTips");

                //获取loading提示框宽高
                var _LoadingTipsH = loadingTips.clientHeight,
                    _LoadingTipsW = loadingTips.clientWidth;

                //计算距离，让loading提示框保持在屏幕上下左右居中

                loadingTips.style.top = (viewport.height > _LoadingTipsH ? (viewport.height - _LoadingTipsH) / 2 : 0) + "px";
                loadingTips.style.left = (viewport.width > _LoadingTipsW ? (viewport.width - _LoadingTipsW) / 2 : 0) + "px";
            },
            show: function () {
                var env = this;
                var dspl = function () {
                    env.init();
                    env.ele.style.display = "";
                };
                if (window.jQuery) dspl();
                else isea.loader.use("jquery", dspl);
            },
            hide: function () {
                this.ele.style.display = "none";
            }
        },
        bootstrap: {
            navtab: function (list, activeIndex, output) {
                var navtab = '<ul class="nav nav-tabs">';
                each(list, function (value, index) {
                    navtab += (index == activeIndex) ? '<li class="active">' : "<li>";
                    navtab += '<a href="' + value.href + '">' + value.title + '</a></li>';
                });
                navtab += "</ul>";
                if (output) {
                    document.write(navtab);
                } else {
                    return navtab;
                }
            }
        },
        init: init,
        guid: guid,
        each: each,
        dom: dom,
        datetime: datetime,
        util: util,
        encrypt: {},
        // 本地存储
        storage: {
            set: function (key, value) {
                value = {_content_: value};
                localStorage.setItem(key, JSON.stringify(value));
            },
            get: function (key) {
                var value;
                if (value = localStorage.getItem(key)) {
                    value = JSON.parse(value);
                    return value._content_;
                } else {
                    return null;
                }
            }
        },
        client: client,
        cookie: cookie,
        loader: loader,
        ready: function (c, prepend) {
            prepend ? readyStack.stack.push(c) : readyStack.heap.push(c);
        },
        bundle: {
            init: function (name, options) {
                loader.load(BASE_DIR + "/isea/bundle/" + name + ".js", function () {
                    (isea.bundle[name])(options);
                });
                return this;
            }
        },
        post: function (url, data, call) {
            ajax({
                url: url,
                type: "POST",
                data: data,
                complete: call
            });
        },
        notificate: function (title, body, img, meta, onclick) {
            if (window.Notification) {
                switch (Notification.permission) {
                    case "granted":
                        //（状态值：0）表示用户同意消息提醒
                        var notification = new Notification(title, {
                            body: body,
                            icon: img,
                            // 不替换之前的
                            renotify: false
                        });
                        notification.onclick = function () {
                            onclick && onclick(notification, meta);
                            notification.close();
                        };
                        setTimeout(function () {
                            notification && notification.close();
                        }, 2000);
                        break;
                    case "default":
                        // 默认状态,用户既未拒绝，也未同意
                        Notification.requestPermission(function () {
                            isea.notificate(title, body, img, meta, onclick);
                        });
                        break;
                    case "denied":
                        //拒绝状态
                        console.log("notification permission deny");
                        break;
                    default:
                        console.log("undelared permission type", Notification.permission);
                }
            } else {
                console.log("浏览器不支持Notification");
            }
        },
        ajax: ajax,
        clone: function (context) {
            var instance = {};
            context && each(context, function (val, key) {
                instance[key] = val;
            });
            return instance;
        },
        save: function (content, filename) {
            var blob = new Blob([content], {type: 'text/plain'});
            if (window.saveAs) {
                window.saveAs(blob, filename);
            } else if (navigator.saveBlob) {
                navigator.saveBlob(blob, filename);
            } else {
                var link = document.createElement("a");
                link.setAttribute("href", URL.createObjectURL(blob));
                link.setAttribute("download", filename);
                var event = document.createEvent('MouseEvents');
                event.initMouseEvent('click', true, true, window, 1, 0, 0, 0, 0, false, false, false, false, 0, null);
                link.dispatchEvent(event);
            }
        },
        style: function () {
            loader.append2Header(dom.create("style", {}, dazzling));
        }
    };
})(function (pps) {
    //plugin load on sequence
    var lq = function (i) {
        if (i < pps.plugins.length) {
            isea.loader.load(pps.plugins[i][0], null, function () {
                var call = pps.plugins[i][1];
                call && call();
                lq(++i);
            });
        }
    };
    lq(0);
});
