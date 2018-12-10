/**
 * Created by linzh on 2017/1/3.
 *
 * Usage:
 * keymenu.create({
        title: "hey",
        item: [
            {
                title: "Markdown",
                svg: '<svg height="64" width="64" xmlns="http://www.w3.org/2000/svg"><g transform="scale(0.0625) translate(64,0)"><path d="M608 192l-96 96 224 224L512 736l96 96 288-320L608 192zM288 192L0 512l288 320 96-96L160 512l224-224L288 192z"></g></svg>'
            },
            {
                title: "HTML",
                svg: '<svg height="64" width="64" xmlns="http://www.w3.org/2000/svg"><g transform="scale(0.0625) translate(64,0)"><path d="M608 192l-96 96 224 224L512 736l96 96 288-320L608 192zM288 192L0 512l288 320 96-96L160 512l224-224L288 192z"></g></svg>'
            }
        ]
    });
 *
 */
isea.keymenu = (function () {
    var style = document.createElement('style');
    style.id = "sr-menu-style";
    style.innerText = ".sr-menu{display:none;position:fixed;background-color:#111;border-radius:5px;top:50%;left:50%;height:150px;margin-top:-75px;margin-left:-125px;z-index:99;text-align:center;color:#fff}.sr-menu>span{display:block;font-size:1.5em;line-height:1.3;margin-top:.25em}.sr-menu>div{display:inline-block;width:100px;text-align:center;vertical-align:top;cursor:pointer;opacity:.7}.sr-menu>div:hover{opacity:1}.sr-menu svg{width:64px;height:64px;margin:0 auto;display:block}.sr-menu path{fill:#fff}.sr-menu .close-menu{position:absolute;top:5px;right:9px;color:#fff;cursor:pointer}";
    document.head.appendChild(style);

    var element = document.createElement('div');
    element.className = "sr-menu";
//        element.innerHTML = '...';
    document.body.appendChild(element);

    var menuVisible = false;
    var menu = document.querySelector('.sr-menu');

    var showMenu = function () {
        menuVisible = true;
        menu.style.display = 'block';
    };

    var hideMenu = function () {
        menuVisible = false;
        menu.style.display = 'none';
    };

    return {
        help: function () {
            console.log("http://www.iconsvg.com/");
        },
        options: {
            title: "Menu",
            item: []

        },
        create: function (config) {
            for (var x in config) this.options[x] = config[x];

            var title, subitem, ele, close;
            title = document.createElement("span");
            title.innerHTML = this.options.title;
            element.appendChild(title);

            for (var x in this.options.item) {
                subitem = this.options.item[x];
                ele = document.createElement("div");
                ele.innerHTML = subitem.svg + "<span>" + subitem.title + "</span>";
                ele.onclick = subitem.click;
                element.appendChild(ele);
            }

            close = document.createElement("a");
            close.innerHTML = "&times;";
            close.className = "close-menu";
            close.onclick = hideMenu;
            element.appendChild(close);

            return this;
        },
        onSave: function (callback) {
            document.addEventListener('keydown', function (e) {
                if (e.keyCode == 83 && (e.ctrlKey || e.metaKey)) {
                    e.shiftKey ? showMenu() : callback();
                    e.preventDefault();
                    return false;
                }

                if (e.keyCode === 27 && menuVisible) {
                    hideMenu();
                    e.preventDefault();
                    return false;
                }
            });
        },
        show: showMenu,
        hide: hideMenu
    };
})();