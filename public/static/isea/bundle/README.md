# table的使用示例
``` javascript


        var page = (function () {

            return {
                selector: {
                    /** 选择器 */
                    modal: "#mlModal",
                    table: "#menulist",
                    form: "#mlForm",
                    /** 事件触发器 */
                    trigger: {
                        /** 表格外部*/
                        add: "#addmenu",
                        refresh: "#refreshmenu",
                        /** 表格内部：元素直接相关 */
                        update: ".act-update",
                        remove: ".act-delete"
                    }
                },
                tableOptions: [
                    {
                        title: 'ID',
                        data: 'id',
                        width: "2%"
                    },
                    {
                        title: '名称',
                        data: function (row) {
                            if (!row.level) row.level = 0;
                            var level = parseInt(row.level), name = row.name;
                            if (level === 0) {
                            } else {
                                name = isea.util.repeat("&emsp;&emsp;", level) + "┣━&nbsp;" + name;
                                /* 全角空格 */
                            }
                            return name;
                        },
                        width: "24%"
                    },
                    {
                        title: '父菜单ID',
                        data: 'pid',
                        width: "12%"
                    },
                    {
                        title: '菜单值',
                        data: 'value',
                        width: "4%"
                    },
                    {
                        title: '排序',
                        data: 'orderNo',
                        width: "4%"
                    },
                    {
                        title: '分类',
                        data: 'category',
                        width: "6%"
                    }, {
                        title: '操作',
                        data: function () {
                            return "<button class='btn btn-default btn-xs act-update'>修改</button> " +
                                "<button class='btn btn-default btn-xs act-delete'>删除</button>";
                        },
                        width: "16%"
                    }
                ],
                url: {
                    /** 增删改查URL地址 */
                    update: "__PUBLIC__/admin/menu/update",
                    remove: "__PUBLIC__/admin/menu/delete",
                    refresh: "__PUBLIC__/admin/menu/getlist",
                    add: "__PUBLIC__/admin/menu/add"
                }
            };
        })();

        isea.bundle.init("table", page);
```