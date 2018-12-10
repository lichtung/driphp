isea.modal = {
    elements: {},
    config: {},
    solve: function (call) {
        isea.loader.load("http://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js", call);
    },
    /**
     * 创建一个Modal对象,会将HTML中指定的内容作为自己的一部分拐走
     * angular-route下会出现特殊的问题，就是模板的再次加载会重新导致modal的创建，
     * 这是后需要将这个要拐卖的元素及其子元素删除即可
     * @param selector 要把哪些东西添加到modal中的选择器
     * @param opt modal配置
     * @returns object
     */
    create: function (selector, opt) {
        if (selector in this.elements) {
            $(selector).remove();//angularjs环境下由路由会重新产生模板中的DOM，需要将他移出，否则会有多个一样的modal产生==>于是就决定了有选择器决定modal;他们会常驻内存直到页面被刷新
            // $(selector).attr("id","").attr("class","").hide()
            return this.elements[selector];
        }
        selector = $(selector);
        selector.css("display", "");
        var config = {
            /* default config */
            //text
            title: "...",
            showConfirm: true,
            confirmText: '确定',
            showCancel: true,
            cancelText: '取消',
            //behaviour
            confirm: null,
            cancel: null,
            show: null,//going to show
            shown: null,//show done
            hide: null,//going to hide
            hidden: null,//hide done
            //others
            backdrop: "static",
            keyboard: false,
            //宽度设置,0表示使用默认的宽度
            width: 0
        };
        isea.init(opt || {}, config);
        this.config = config;

        var instance = isea.clone(this),
            id = 'modal_' + Math.random(),
            modal = $(isea.dom.create("div.modal.fade", {
                id: id,
                "aria-hidden": "true",
                role: "dialog"
            })),
            dialog = $(isea.dom.create("div.modal-dialog")),
            header, content, body;
        var ic = instance.config;
        if (ic.width) dialog.css("width", ic.width + "px");

        if (isea.util.gettype(ic.backdrop) === 'boolean') ic.backdrop = ic.backdrop ? 'true' : 'false';
        $("body").append(modal.attr('data-backdrop', ic.backdrop).attr('data-keyboard', ic.keyboard ? 'true' : 'false'));

        modal.append(dialog.append(content = $(isea.dom.create("div.modal-content"))));

        //set header and body
        //需要注意的地方是：append进来被消除了ID
        content.append(header = $(isea.dom.create("div.modal-header", {}, '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>')))
            .append(body = $(isea.dom.create("div.modal-body")).append(selector));//suggest selector has class 'hidden'

        //设置足部
        if (ic.showCancel || ic.showConfirm) {
            var footer = $(isea.dom.create("div.modal-footer"));
            content.append(footer);
            ic.showCancel && footer.append(
                $(isea.dom.create("button.btn.btn-sm.btn-default._cancel", {
                    "type": "button",
                    "data-dismiss": "modal"
                }, ic.cancelText)).click(ic.cancel)
            );
            ic.showConfirm && footer.append(
                $(isea.dom.create("button.btn.btn-sm.btn-primary._confirm", {"type": "button"}, ic.confirmText)).click(ic.confirm)
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
        return this.elements[selector] = instance;
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
            var h = isea.dom.create('h4.modal-title');
            h.innerHTML = newtitle;
            this.target.find(".modal-header").append(h);
        }
        title.text(newtitle);
        return this;
    },
    isshown: false,
    show: function () {
        this.target.modal('show');
        this.isshown = true;
        return this;
    },
    hide: function () {
        this.target.modal('hide');
        this.isshown = false;
        return this;
    },
    /* 是否显示 */
    isShow: function () {
        return this.isshown;
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