/*!
 * Bootstrap Context Menu
 * Author: @sydcanem
 * https://github.com/sydcanem/bootstrap-contextmenu
 *
 * Inspired by Bootstrap's dropdown plugin.
 * Bootstrap (http://getbootstrap.com).
 *
 * Licensed under MIT
 * ========================================================= */

;(function ($) {
    'use strict';

    /* CONTEXTMENU CLASS DEFINITION
     * ============================ */
    var toggle = '[data-toggle="context"]';

    var ContextMenu = function (element, options) {
        this.$element = $(element);

        this.before = options.before || this.before;
        this.onItem = options.onItem || this.onItem;
        this.scopes = options.scopes || null;

        if (options.target) {
            this.$element.data('target', options.target);
        }

        this.listen();
    };

    ContextMenu.prototype = {

        constructor: ContextMenu
        , show: function (e) {

            var $menu
                , tp
                , items
                , relatedTarget = {relatedTarget: this, target: e.currentTarget};

            if (this.isDisabled()) return;

            this.closemenu();

            if (this.before.call(this, e, $(e.currentTarget)) === false) return;

            $menu = this.getMenu();
            $menu.trigger($.Event('show.bs.context', relatedTarget));

            tp = this.getPosition(e, $menu);
            items = 'li:not(.divider)';
            $menu.attr('style', '')
                .css(tp)
                .addClass('open')
                .on('click.context.data-api', items, $.proxy(this.onItem, this, $(e.currentTarget)))
                .trigger('shown.bs.context', relatedTarget);

            // Delegating the `closemenu` only on the currently opened menu.
            // This prevents other opened menus from closing.
            $('html').on('click.context.data-api', $menu.selector, $.proxy(this.closemenu, this));

            return false;
        }

        , closemenu: function (e) {
            var $menu
                , items
                , relatedTarget;

            $menu = this.getMenu();

            if (!$menu.hasClass('open')) return;

            relatedTarget = {relatedTarget: this};
            $menu.trigger($.Event('hide.bs.context', relatedTarget));

            items = 'li:not(.divider)';
            $menu.removeClass('open')
                .off('click.context.data-api', items)
                .trigger('hidden.bs.context', relatedTarget);

            $('html')
                .off('click.context.data-api', $menu.selector);
            // Don't propagate click event so other currently
            // opened menus won't close.
            if (e) {
                e.stopPropagation();
            }
        }

        , keydown: function (e) {
            if (e.which == 27) this.closemenu(e);
        }

        , before: function () {
            return true;
        }

        , onItem: function () {
            return true;
        }

        , listen: function () {
            this.$element.on('contextmenu.context.data-api', this.scopes, $.proxy(this.show, this));
            $('html').on('click.context.data-api', $.proxy(this.closemenu, this))
                .on('keydown.context.data-api', $.proxy(this.keydown, this));
        }

        , destroy: function () {
            this.$element.off('.context.data-api').removeData('context');
            $('html').off('.context.data-api');
        }

        , isDisabled: function () {
            return this.$element.hasClass('disabled') ||
                this.$element.attr('disabled');
        }

        , getMenu: function () {
            var selector = this.$element.data('target')
                , $menu;

            if (!selector) {
                selector = this.$element.attr('href');
                selector = selector && selector.replace(/.*(?=#[^\s]*$)/, ''); //strip for ie7
            }

            $menu = $(selector);

            return $menu && $menu.length ? $menu : this.$element.find(selector);
        }

        , getPosition: function (e, $menu) {
            var thiswin = $(window);
            var droppdownMenu = $menu.find('.dropdown-menu')
                , mouseX = e.clientX
                , mouseY = e.clientY
                , boundsX = thiswin.width()
                , boundsY = thiswin.height()
                , menuWidth = droppdownMenu.outerWidth
                , menuHeight = droppdownMenu.outerHeight
                , tp = {"position": "absolute", "z-index": 9999}
                , Y, X, parentOffset;

            if (mouseY + menuHeight > boundsY) {
                Y = {"top": mouseY - menuHeight + thiswin.scrollTop};
            } else {
                Y = {"top": mouseY + thiswin.scrollTop};
            }

            if ((mouseX + menuWidth > boundsX) && ((mouseX - menuWidth) > 0)) {
                X = {"left": mouseX - menuWidth + thiswin.scrollLeft};
            } else {
                X = {"left": mouseX + thiswin.scrollLeft};
            }

            // If context-menu's parent is positioned using absolute or relative positioning,
            // the calculated mouse position will be incorrect.
            // Adjust the position of the menu by its offset parent position.
            parentOffset = $menu.offsetParent().offset();
            X.left = X.left - parentOffset.left;
            Y.top = Y.top - parentOffset.top;

            return $.extend(tp, Y, X);
        }

    };

    /* CONTEXT MENU PLUGIN DEFINITION
     * ========================== */

    $.fn.contextmenu = function (option, e) {
        return $.each(function () {
            var $this = $(this)
                , data = $this.data('context')
                , options = (typeof option == 'object') && option;

            if (!data) $this.data('context', (data = new ContextMenu($this, options)));
            if (typeof option == 'string') data[option].call(data, e);
        });
    };

    $.fn.contextmenu.Constructor = ContextMenu;

    /* APPLY TO STANDARD CONTEXT MENU ELEMENTS
     * =================================== */

    $(document)
        .on('contextmenu.context.data-api', function () {
            $(toggle).each(function () {
                var data = $(this).data('context');
                if (!data) return;
                data.closemenu();
            });
        })
        .on('contextmenu.context.data-api', toggle, function (e) {
            $(this).contextmenu('show', e);

            e.preventDefault();
            e.stopPropagation();
        });

}(jQuery));

isea.contextmenu = (function () {
    if (!("contextmenu" in $)) throw "require contextmenu and jquery";
    return {
        /**
         * create a menu-handler object
         * @param menus format like "[{'index':'edit','title':'Edit'}]"
         * @param handler callback while click the context menu item
         * @param onItem
         * @param before
         */
        create: function (menus, handler, onItem, before) {
            var ul, id = 'cm_' + isea.guid(), cm = $("<div id='" + id + "'></div>"), flag = false, ns = L.NS(this);
            $("body").prepend(cm.append(ul = $(isea.dom.create("ul.dropdown-menu", {"role": "menu"}))));
            //菜单项
            L.U.each(menus, function (group) {
                flag && ul.append(isea.dom.create("li.divider"));//对象之间划割
                L.U.each(group, function (value, key) {
                    ul.append(isea.dom.create("li", {}, '<a tabindex="' + key + '">' + value + '</a>'));
                });
                flag = true;
            });

            before || (before = function (e, c) {
            });
            onItem || (onItem = function (c, e) {
            });
            handler || (handler = function (element, tabindex, text) {
            });

            //这里的target的上下文意思是 公共配置组
            ns.target = {
                target: '#' + id,
                // execute on menu item selection
                onItem: function (ele, event) {
                    onItem(ele, event);
                    var target = event.target;
                    handler(target, target.getAttribute('tabindex'), target.innerText);
                },
                // execute code before context menu if shown
                before: before
            };
            return ns;
        },
        bind: function (jq) {
            L.jq(jq).contextmenu(this.target);
        }
    };
})();