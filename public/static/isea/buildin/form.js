/**
 * Writen by Lin Zhonghuang
 * @type {{fill: isea.form.fill, clean: isea.form.clean, data: isea.form.data, create: isea.form.create}}
 */
isea.form = {
    fill: function (form, data) {
        var target;
        form = $(form);
        isea.each(data, function (val, key) {
            target = form.find("[name=" + key + "]");
            if (target.length) {/*表单中存在这个name的输入元素*/
                if (target.length > 1) {/* 出现了radio或者checkbox的清空 */
                    isea.each(target, function (item) {
                        item = $(item);
                        if (('radio' === item.attr("type")) && item.val() == val) {
                            item.attr("checked", true);
                        }
                    });
                } else {
                    target.val(val);
                }
            } else {
                form.append($(isea.dom.create("input", {
                    name: key,
                    value: val,
                    type: "hidden"
                })));
            }
        });
    },
    clean: function (form) {
        var target, dftval, i, j, options, option, flag;
        form = $(form);
        //------------------- input ----------------------
        var inputs = form.find("input");
        for (i = 0; i < inputs.length; i++) {
            target = inputs.eq(i);
            dftval = target.attr("data-default");
            target.val(dftval ? dftval : "");
        }

        //------------------- select ----------------------
        var select, selects = form.find("select");
        for (i = 0; i < selects.length; i++) {
            select = selects.eq(i);

            options = select.find("option");
            flag = false;
            for (j = 0; j < options.length; j++) {
                option = options.eq(j);
                if (option.attr("data-default") !== undefined) {
                    flag = true;
                    option.attr("selected", true);
                }
                /* === "" 時表示有屬性但是爲空 */

                flag || options.eq(0).attr("selected", true);
            }
        }
        //.....
        return form;
    },
    data:function (selector) {
        return $(selector).serialize();
    },
    create: function () {
        //TODO:自动创建表单
    }
};
