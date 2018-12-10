/**
 * Created by Lin on 2017/1/7.
 */
"use strict";

isea.bundle.table = function (page) {
    isea.loader.use("form,modal,datatables", function () {
        isea.datatables.solve(function () {

            var form = $(page.selector.form);
            var modal = isea.modal.create(page.selector.modal,page["modalOptions"] ||{});

            var dtables = isea.datatables.create(page.selector.table, page.tableOptions).onDraw(function () {
                page.selector.trigger.update && $(page.selector.trigger.update).click(function () {
                    var tr = $(this).closest("tr");
                    var data = dtables.data(tr);
//                        console.log(tr,data);
                    isea.form.fill(form, data);
                    modal.onConfirm(function () {
                        var data = form.serialize();
                        $.get(page.url.update, data, function (data) {
                            isea.notify.show(data.message);
                            if (data.status) {
                                modal.hide();
                                dtables.update(data.data, tr);
                            }
                        });
                    }).show();
                });
                page.selector.trigger.remove && $(page.selector.trigger.remove).click(function () {
                    var tr = $(this).closest("tr");
                    var data = dtables.data(tr);
                    $.get(page.url.remove, data, function (data) {
                        isea.notify.show(data.message);
                        if (data.status) {
                            dtables.remove(tr);
                        }
                    });
                });

                if (page.onclick) {
                    isea.each(page.onclick, function (call, selector) {
                        dtables.ele.find(selector).unbind("click").click(function () {
                            call($(this), dtables, modal, form);
                        });
                    });
                }

            });

            function reloadData() {
                $.get(page.url.refresh, function (data) {
                    dtables.load(data.data);
                });
            }

            page.selector.trigger.add && $(page.selector.trigger.add).click(function () {
                isea.form.clean(form);
                modal.onConfirm(function () {
                    var data = form.serialize();
                    $.get(page.url.add, data, function (data) {
                        isea.notify.show(data.message);
                        if (data.status) {
                            modal.hide();
                            dtables.load(data.data, false);
                        }
                    });
                }).show();
            });
            page.selector.trigger.refresh && $(page.selector.trigger.refresh).click(reloadData);

            reloadData();
        })
    });
};
