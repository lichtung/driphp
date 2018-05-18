
var gt = function (o) {
    return Object.prototype.toString.call(o).slice(8, -1).toLowerCase();
};
var to = function (s) {
    return eval("(" + s + ")");
};
var ne = function (context) {
    var instance = new (function () {
        return {ele: null};
    });
    if (context) {
        for (var x in context) {
            instance[x] = context[x];
        }
    }
    return instance;
};
var ce = function (name, attrs, ih) {
    var clses, id;
    if (name.indexOf('.') > 0) {
        clses = name.split(".");
        name = clses.shift();
    }
    if (name.indexOf("#") > 0) {
        var tempid = name.split("#");
        name = tempid[0];
        id = tempid[1];
    }
    var el = document.createElement(name);
    id && el.setAttribute('id', id);
    if (clses) {
        var ct = '';
        for (var x in clses) ct += clses[x] + " ";
        el.setAttribute('class', ct);
    }
    if (attrs) for (var k in attrs) {
        el.setAttribute(k, attrs[k]);
    }
    if (ih) el.innerHTML = ih;
    return el;
};
var init = function (config, c) {
    for (var x in config) {
        c[x] = config[x];
    }
};

window.select = (function () {
    var config = {};
    return {
        create: function (opt) {
            init(opt || {}, config);
            var instance = ne(this);
            instance.ele = $(ce("select.form-control"));
            return instance;
        },
        load: function (data, format) {
            data = format ? format(data) : data;
            for (var x in data) {
                this.ele.append(this.createLi(data[x]));
            }
            return this;
        },
        getEle: function () {
            return this.ele;
        },
        createLi: function (opt) {
            var tabindex = -1;
            var title = opt;
            if (gt(opt) === "object") {
                tabindex = "index" in opt ? opt.index : -1;
                title = "title" in opt ? opt.title : "Untitled";
            }
            return ce("option", {"value": tabindex}, title);
        }
    };
})();

window.datatables = (function () {

//background-color: #b0bed9;
//.onDraw(function(){
//     bg.table().find("tr").click(function () {
//         var thistr = $(this);
//         if ( thistr.hasClass('selected') ) {
//             thistr.removeClass('selected');
//         } else {
//             thistr.addClass('selected');
//         }
//     });
// });
    return {
        api: null,
        ele: null,
        cr: null,
        create: function (dt, opt) {
            var ns = ne(this);
            ns.ele = $(dt);
            var conf = {
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            };
            opt && init(opt, conf, true);
            ns.api = ns.ele.DataTable(conf);
            return ns;
        },
        table: function () {
            return this.ele
        },
        //为tableapi对象加载数据,参数二用于清空之前的数据
        load: function (data, clear) {
            if (gt(data) === "string") {
                data = eval("(" + data + ")");
            }
            if (gt(data) === "object") {
                data = [data];
            }
            if (false !== clear) {
                this.api.clear();
            }
            this.api.rows.add(data).draw();
            return this;
        },
        search: function (word, index) {/*column index of integer*/
            (undefined !== index ? this.api.columns(index) : this.api).search(word).draw();
            return this;
        },
        onDraw: function (callback) {
            if (this.ele) {
                this.ele.on('draw.dt', function (event, settings) {
                    callback(event, settings);
                });
            }
            return this;
        },
        remove: function (rows) {/*rows*/
            if (rows.length === 1) {
                this.api.row(rows).remove();
            } else {
                for (var i = 0; i < rows.length; i++) {
                    this.api.row(rows.eq(i)).remove();
                }
            }
            return this.api.draw(false);
        },
        //获取表格指定行的数据或者全部数据
        data: function (e) {
            if ((gt(e) === "boolean") || (!e)) {
                var data = this.api.data();
                if (true !== e) {
                    var tmp = [];
                    for (var i = 0; i < data.length; i++) {
                        tmp.push(data[i]);
                    }
                    data = tmp;
                }
                return data;
            } else {
                return this.api.row(this.cr = e).data();
            }
        },
        update: function (nd, l) {
            if (l === undefined) l = this.cr;
            if (l) {
                if (gt(l) === "array") {
                    for (var i = 0; i < l.length; i++) {
                        this.update(nd, l[i]);
                    }
                } else {
                    this.api.row(l).data(nd).draw(false);
                }
            } else {
                console.log('no line choosed');
            }
        }
    };
})();

window.modal = (function () {
    return {
        config: {},
        /**
         * 创建一个Modal对象,会将HTML中指定的内容作为自己的一部分拐走
         * @param selector 要把哪些东西添加到modal中的选择器
         * @param opt modal配置
         * @returns object
         */
        create: function (selector, opt) {
            config = {
                /* default config */
                //text
                title: "...",
                showConfirm: true,
                confirmText: '',
                showCancel: true,
                cancelText: '',
                //behaviour
                confirm: null,
                cancel: null,
                show: null,//going to show
                shown: null,//show done
                hide: null,//going to hide
                hidden: null,//hide done
                //others
                backdrop: "static",
                keyboard: true
            };
            init(opt || {}, config);

            var instance = ne(this),
                id = 'modal_' + Math.random(),
                modal = $(ce("div.modal.fade", {
                    id: id,
                    "aria-hidden": "true",
                    role: "dialog"
                })),
                dialog = $(ce("div.modal-dialog")),
                header, content, body;
            var ic = instance.config;

            if (typeof ic.backdrop !== "string") ic['backdrop'] = ic['backdrop'] ? 'true' : 'false';
            $("body").append(modal.attr('data-backdrop', ic['backdrop']).attr('data-keyboard', ic.keyboard ? 'true' : 'false'));

            modal.append(dialog.append(content = $(ce("div.modal-content"))));

            //set header and body
            content.append(header =
                $(ce("div.modal-header", {}, '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>')))
                .append(body = $(ce("div.modal-body")).append($(selector).removeClass('hidden')));//suggest selector has class 'hidden'
            //设置足部
            if (ic.showCancel || ic.showConfirm) {
                content.append($(ce("div.modal-footer")));
                ic.showCancel && content.append(
                    $(ce("button.btn.btn-sm._cancel", {
                        "type": "button",
                        "data-dismiss": "modal"
                    }, ic['cancelText'])).click(ic.cancel)
                );
                ic.showConfirm && content.append(
                    $(ce("button.btn.btn-sm._confirm", {"type": "button"}, ic['confirmText'])).click(ic.confirm)
                );
            }

            instance.target = modal.modal('hide');

            ic['title'] && instance.title(ic['title']);
            var arr = ['show', 'shown', 'hide', 'hidden'];
            for (var x in arr) {
                var nm = arr[x];
                ic[nm] && instance.target.on(nm + '.bs.modal', function (e) {
                    ic[e.type]();
                });
            }
            return instance;
        },
        getElement: function (selector) {
            return this.target.find(selector);
        },
        onConfirm: function (callback) {
            this.target.find("._confirm").unbind("click").click(callback);
            return this;
        },
        onCancel: function (callback) {
            this.target.find("._cancel").unbind("click").click(callback);
            return this;
        },
        //update title
        title: function (newtitle) {
            var title = this.target.find(".modal-title");
            if (!title.length) {
                var h = ce('h4.modal-title');
                h.innerHTML = newtitle;
                this.target.find(".modal-header").append(h);
            }
            title.text(newtitle);
            return this;
        },
        show: function () {
            this.target.modal('show');
            return this;
        },
        hide: function () {
            this.target.modal('hide');
            return this;
        },
        alert: {
            _alert: null,
            show: function (msg) {
                var tag = 'Alert';
                var id = 'L-modal-' + tag;
                if (!this._alert) {
                    var div = document.createElement('div');
                    div.id = id;
                    document.body.appendChild(div);
                    this._alert = modal.create("#" + id, {
                        title: tag,
                        showConfirm: false,
                        showCancel: false
                    });
                }
                this._alert.getElement("#" + id).text(msg);
                this._alert.show();
            },
            hide: function () {
                this._alert && this._alert.hide();
            }
        }
    };
})();
