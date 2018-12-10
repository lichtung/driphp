/**
 * Created by asus on 5/31/17.
 */
window.lext = {
    //plugin autoload start
    JsMap:{},
    jsMap:function (option) {
        if(O.isObj(option)) L.init(option,this.JsMap,true);
        else return option?(O.notempty(option,this.JsMap)?this.JsMap[option]:null):this.JsMap;
    },
    _jq:null,
    loadLib:function(plugin,callback){
        L.load(this.JsMap[plugin],null,callback);
    },
    /**
     *
     * @param selector
     * @param options
     * @param functionName
     * @param pluginName
     * @param callback callback while on loaded
     */
    initlize:function(selector,options,functionName,pluginName,callback){
        options || (options = {});
        pluginName = pluginName?pluginName:functionName;
        var jq = this._jq?this._jq:(this._jq = $());
        // console.log(this.JsMap,pluginName,this.JsMap[pluginName])
        L.load(this.JsMap[pluginName],null,function () {
            // console.log(L.O.isObj(selector),selector instanceof jQuery);
            if(!L.O.isObj(selector) || (selector instanceof jQuery)){
                //for single:it must be called when last is finished
                selector = $(selector);
                (functionName in jq) && (jq[functionName]).apply(selector,O.isArr(options)?options:[options]);
                callback && callback(selector);
            }else{
                //for batch setting,key as selector,value as max
                var list = [];
                L.U.each(selector,function (params,k) {
                    list.push( k = $(k));
                    (functionName in jq) && (jq[functionName]).apply(k,O.isArr(params)?params:[params]);
                });
                callback && callback(list);
            }
        });
    },
    //plugin autoload end
    sticky:function (note, options, callback) {
        // Default settings
        var position = 'top-right'; // top-left, top-right, bottom-left, or bottom-right

        var settings = {
            'speed': 'fast',	 // animations: fast, slow, or integer
            'duplicates': true,  // true or false
            'autoclose': 5000  // integer or false
        };

        // Passing in the object instead of specifying a note
        if (!note) {
            note = this.html();
        }

        if (options) {
            $.extend(settings, options);
        }

        // Variables
        var display = true;
        var duplicate = 'no';

        // Somewhat of a unique ID
        var uniqID = guid();

        // Handling duplicate notes and IDs
        $('.sticky-note').each(function () {
            if ($(this).html() == note && $(this).is(':visible')) {
                duplicate = 'yes';
                if (!settings['duplicates']) {
                    display = false;
                }
            }
            if ($(this).attr('id') == uniqID) {
                uniqID = Math.floor(Math.random() * 9999999);
            }
        });

        var body = $("body");
        // Make sure the sticky queue exists
        if (!body.find('.sticky-queue').html()) {
            body.append('<div class="sticky-queue ' + position + '"></div>');
        }

        // Can it be displayed?
        if (display) {
            // Building and inserting sticky note
            $('.sticky-queue').prepend('<div class="sticky border-' + position + '" id="' + uniqID + '"></div>');
            var _element = $('#' + uniqID);
            _element.append('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAAA1klEQVQoz6WSOw6CQBCG90gWXsjKxph4HZAEsgUSHlsAAa6ilzDGgopxP5Ix2K7FJH/+x+wMjBERoxXH8d5aey2K4l6W5ZMCw6FtvV+Qpumlrut313UyDIOM47gWGA4Nz08QomkaadtW+r5fA9M0rQWGQ8OjYRNF0c53mxH8aLc8z8/OuYWXKDAcGh68ZAzzMwpdveFEtyzLDt6AScBwaHjwkjF++cem+6zGJEmOlDZCUx8ZU1XVS3eC9K8sGtAGcGi6M5nwYPCowR8n+HcEH8BfJxdy5B8L5i9vzgm5WAAAAABJRU5ErkJggg==" class="sticky-close" rel="' + uniqID + '" title="Close" />');
            _element.append('<div class="sticky-note" rel="' + uniqID + '">' + note + '</div>');

            // Smoother animation
            var height = _element.height();
            _element.css('height', height).slideDown(settings['speed']);
            display = true;
        }

        // Listeners
        $('.sticky').ready(function () {
            // If 'autoclose' is enabled, set a timer to close the sticky
            if (settings['autoclose']) {
                $('#' + uniqID).delay(settings['autoclose']).fadeOut(settings['speed']);
            }
        });
        // Closing a sticky
        $('.sticky-close').click(function () {
            $('#' + $(this).attr('rel')).dequeue().fadeOut(settings['speed']);
        });


        // Callback data
        var response =
            {
                'id': uniqID,
                'duplicate': duplicate,
                'displayed': display,
                'position': position
            };

        // Callback function?
        if (callback) {
            callback(response);
        }
        else {
            return (response);
        }

    },
    _eles:{
        Spinner:null,
        _spinner:null
    },
    spinner:function (options) {
        var env = this;
        if(!this._eles.Spinner){
            this._eles.Spinner = (function () {

                "use strict";

                var prefixes = ['webkit', 'Moz', 'ms', 'O'] /* Vendor prefixes */
                    , animations = {} /* Animation rules keyed by their name */
                    , useCssAnimations /* Whether to use CSS animations or setTimeout */
                    , sheet;
                /* A stylesheet to hold the @keyframe or VML rules. */

                /**
                 * Utility function to create elements. If no tag name is given,
                 * a DIV is created. Optionally properties can be passed.
                 */
                function createEl(tag, prop) {
                    var el = document.createElement(tag || 'div'), n;

                    for (n in prop) el[n] = prop[n];
                    return el;
                }

                /**
                 * Appends children and returns the parent.
                 */
                function ins(parent /* child1, child2, ...*/) {
                    for (var i = 1, n = arguments.length; i < n; i++) {
                        parent.appendChild(arguments[i])
                    }

                    return parent
                }

                /**
                 * Creates an opacity keyframe animation rule and returns its name.
                 * Since most mobile Webkits have timing issues with animation-delay,
                 * we create separate rules for each line/segment.
                 */
                function addAnimation(alpha, trail, i, lines) {
                    var name = ['opacity', trail, ~~(alpha * 100), i, lines].join('-')
                        , start = 0.01 + i / lines * 100
                        , z = Math.max(1 - (1 - alpha) / trail * (100 - start), alpha)
                        , prefix = useCssAnimations.substring(0, useCssAnimations.indexOf('Animation')).toLowerCase()
                        , pre = prefix && '-' + prefix + '-' || '';

                    if (!animations[name]) {
                        sheet.insertRule(
                            '@' + pre + 'keyframes ' + name + '{' +
                            '0%{opacity:' + z + '}' +
                            start + '%{opacity:' + alpha + '}' +
                            (start + 0.01) + '%{opacity:1}' +
                            (start + trail) % 100 + '%{opacity:' + alpha + '}' +
                            '100%{opacity:' + z + '}' +
                            '}', sheet.cssRules.length);
                        animations[name] = 1
                    }

                    return name
                }

                /**
                 * Tries various vendor prefixes and returns the first supported property.
                 */
                function vendor(el, prop) {
                    var s = el.style
                        , pp
                        , i;

                    prop = prop.charAt(0).toUpperCase() + prop.slice(1);
                    if (s[prop] !== undefined) return prop;
                    for (i = 0; i < prefixes.length; i++) {
                        pp = prefixes[i] + prop;
                        if (s[pp] !== undefined) return pp
                    }
                }

                /**
                 * Sets multiple style properties at once.
                 */
                function css(el, prop) {
                    for (var n in prop) {
                        el.style[vendor(el, n) || n] = prop[n];
                    }

                    return el;
                }

                /**
                 * Fills in default values.
                 * @param obj argument[0]
                 * @returns {*}
                 */
                function merge(obj) {
                    for (var i = 1; i < arguments.length; i++) {
                        var def = arguments[i];
                        for (var n in def) {
                            if (obj[n] === undefined) obj[n] = def[n];
                        }
                    }
                    return obj;
                }

                /**
                 * Returns the line color from the given string or array.
                 */
                function getColor(color, idx) {
                    return typeof color == 'string' ? color : color[idx % color.length];
                }

                // Built-in defaults

                var defaults = {
                    lines: 12             // The number of lines to draw
                    , length: 7             // The length of each line
                    , width: 5              // The line thickness
                    , radius: 10            // The radius of the inner circle
                    , scale: 1.0            // Scales overall size of the spinner
                    , corners: 1            // Roundness (0..1)
                    , color: '#000'         // #rgb or #rrggbb
                    , opacity: 1 / 4          // Opacity of the lines
                    , rotate: 0             // Rotation offset
                    , direction: 1          // 1: clockwise, -1: counterclockwise
                    , speed: 1              // Rounds per second
                    , trail: 100            // Afterglow percentage
                    , fps: 20               // Frames per second when using setTimeout()
                    , zIndex: 2e9           // Use a high z-index by default
                    , className: 'spinner'  // CSS class to assign to the element
                    , top: '50%'            // center vertically
                    , left: '50%'           // center horizontally
                    , shadow: false         // Whether to render a shadow
                    , hwaccel: false        // Whether to use hardware acceleration (might be buggy)
                    , position: 'absolute'  // Element positioning
                };

                /** The constructor */
                function Spinner(o) {
                    this.opts = merge(o || {}, Spinner.defaults, defaults);
                }

                // Global defaults that override the built-ins:
                Spinner.defaults = {};

                merge(Spinner.prototype, {
                    /**
                     * Adds the spinner to the given target element. If this instance is already
                     * spinning, it is automatically removed from its previous target b calling
                     * stop() internally.
                     */
                    spin: function (target) {
                        if(!target){
                            if(document.body){
                                target =document.body;
                            }else{
                                console.log('No target to spin!');
                                return false;
                            }
                        }
                        this.stop();

                        var self = this
                            , o = self.opts
                            , el = self.el = createEl(null, {className: o.className});

                        css(el, {
                            position: o.position
                            , width: 0
                            , zIndex: o.zIndex
                            , left: o.left
                            , top: o.top
                        });

                        if (target) {
                            target.insertBefore(el, target.firstChild || null);
                        }

                        el.setAttribute('role', 'progressbar');
                        self.lines(el, self.opts);

                        if (!useCssAnimations) {
                            // No CSS animation support, use setTimeout() instead
                            var i = 0
                                , start = (o.lines - 1) * (1 - o.direction) / 2
                                , alpha
                                , fps = o.fps
                                , f = fps / o.speed
                                , ostep = (1 - o.opacity) / (f * o.trail / 100)
                                , astep = f / o.lines

                            ;
                            (function anim() {
                                i++;
                                for (var j = 0; j < o.lines; j++) {
                                    alpha = Math.max(1 - (i + (o.lines - j) * astep) % f * ostep, o.opacity);

                                    self.opacity(el, j * o.direction + start, alpha, o);
                                }
                                self.timeout = self.el && setTimeout(anim, ~~(1000 / fps));
                            })();
                        }
                        return self
                    }

                    /**
                     * Stops and removes the Spinner.
                     */
                    , stop: function () {
                        var el = this.el;
                        if (el) {
                            clearTimeout(this.timeout);
                            if (el.parentNode) el.parentNode.removeChild(el);
                            this.el = undefined;
                        }
                        return this
                    }

                    /**
                     * Internal method that draws the individual lines. Will be overwritten
                     * in VML fallback mode below.
                     */
                    , lines: function (el, o) {
                        var i = 0
                            , start = (o.lines - 1) * (1 - o.direction) / 2
                            , seg;

                        function fill(color, shadow) {
                            return css(createEl(), {
                                position: 'absolute'
                                ,
                                width: o.scale * (o.length + o.width) + 'px'
                                ,
                                height: o.scale * o.width + 'px'
                                ,
                                background: color
                                ,
                                boxShadow: shadow
                                ,
                                transformOrigin: 'left'
                                ,
                                transform: 'rotate(' + ~~(360 / o.lines * i + o.rotate) + 'deg) translate(' + o.scale * o.radius + 'px' + ',0)'
                                ,
                                borderRadius: (o.corners * o.scale * o.width >> 1) + 'px'
                            })
                        }

                        for (; i < o.lines; i++) {
                            seg = css(createEl(), {
                                position: 'absolute'
                                ,
                                top: 1 + ~(o.scale * o.width / 2) + 'px'
                                ,
                                transform: o.hwaccel ? 'translate3d(0,0,0)' : ''
                                ,
                                opacity: o.opacity
                                ,
                                animation: useCssAnimations && addAnimation(o.opacity, o.trail, start + i * o.direction, o.lines) + ' ' + 1 / o.speed + 's linear infinite'
                            });

                            if (o.shadow) ins(seg, css(fill('#000', '0 0 4px #000'), {top: '2px'}));
                            ins(el, ins(seg, fill(getColor(o.color, i), '0 0 1px rgba(0,0,0,.1)')));
                        }
                        return el
                    }

                    /**
                     * Internal method that adjusts the opacity of a single line.
                     * Will be overwritten in VML fallback mode below.
                     */
                    , opacity: function (el, i, val) {
                        if (i < el.childNodes.length) el.childNodes[i].style.opacity = val
                    }

                });

                function initVML() {

                    /* Utility function to create a VML tag */
                    function vml(tag, attr) {
                        return createEl('<' + tag + ' xmlns="urn:schemas-microsoft.com:vml" class="spin-vml">', attr)
                    }

                    // No CSS transforms but VML support, add a CSS rule for VML elements:
                    sheet.addRule('.spin-vml', 'behavior:url(#default#VML)');

                    Spinner.prototype.lines = function (el, o) {
                        var r = o.scale * (o.length + o.width)
                            , s = o.scale * 2 * r;

                        function grp() {
                            return css(
                                vml('group', {
                                    coordsize: s + ' ' + s
                                    , coordorigin: -r + ' ' + -r
                                })
                                , {width: s, height: s}
                            )
                        }

                        var margin = -(o.width + o.length) * o.scale * 2 + 'px'
                            , g = css(grp(), {position: 'absolute', top: margin, left: margin})
                            , i;

                        function seg(i, dx, filter) {
                            ins(
                                g
                                , ins(
                                    css(grp(), {rotation: 360 / o.lines * i + 'deg', left: ~~dx})
                                    , ins(
                                        css(
                                            vml('roundrect', {arcsize: o.corners})
                                            , {
                                                width: r
                                                , height: o.scale * o.width
                                                , left: o.scale * o.radius
                                                , top: -o.scale * o.width >> 1
                                                , filter: filter
                                            }
                                        )
                                        , vml('fill', {color: getColor(o.color, i), opacity: o.opacity})
                                        , vml('stroke', {opacity: 0}) // transparent stroke to fix color bleeding upon opacity change
                                    )
                                )
                            )
                        }

                        if (o.shadow)
                            for (i = 1; i <= o.lines; i++) {
                                seg(i, -2, 'progid:DXImageTransform.Microsoft.Blur(pixelradius=2,makeshadow=1,shadowopacity=.3)');
                            }

                        for (i = 1; i <= o.lines; i++) seg(i);
                        return ins(el, g);
                    };

                    Spinner.prototype.opacity = function (el, i, val, o) {
                        var c = el.firstChild;
                        o = o.shadow && o.lines || 0;
                        if (c && i + o < c.childNodes.length) {
                            c = c.childNodes[i + o];
                            c = c && c.firstChild;
                            c = c && c.firstChild;
                            if (c) c.opacity = val;
                        }
                    }
                }

                if (typeof document !== 'undefined') {
                    sheet = (function () {
                        var el = createEl('style', {type: 'text/css'});
                        ins(document.getElementsByTagName('head')[0], el);
                        return el['sheet'] || el.styleSheet;
                    }());

                    var probe = css(createEl('group'), {behavior: 'url(#default#VML)'});

                    if (!vendor(probe, 'transform') && probe['adj']) initVML();
                    else useCssAnimations = vendor(probe, 'animation');
                }

                return Spinner;

            })();
        }
        if(!this._eles._spinner || options){/* it will recreate an spinner if options is set */
            /**
             * Copyright (c) 2011-2014 Felix Gnass
             * Licensed under the MIT license
             * http://spin.js.org/
             *
             * Example:
             var opts = {
      lines: 12             // The number of lines to draw
    , length: 7             // The length of each line
    , width: 5              // The line thickness
    , radius: 10            // The radius of the inner circle
    , scale: 1.0            // Scales overall size of the spinner
    , corners: 1            // Roundness (0..1)
    , color: '#000'         // #rgb or #rrggbb
    , opacity: 1/4          // Opacity of the lines
    , rotate: 0             // Rotation offset
    , direction: 1          // 1: clockwise, -1: counterclockwise
    , speed: 1              // Rounds per second
    , trail: 100            // Afterglow percentage
    , fps: 20               // Frames per second when using setTimeout()
    , zIndex: 2e9           // Use a high z-index by default
    , className: 'spinner'  // CSS class to assign to the element
    , top: '50%'            // center vertically
    , left: '50%'           // center horizontally
    , shadow: false         // Whether to render a shadow
    , hwaccel: false        // Whether to use hardware acceleration (might be buggy)
    , position: 'absolute'  // Element positioning
    }
             //use ; to avoid be called by others
             ;(function(root,fact))(this,function(){});
             var spinner = new Spinner().spin(document.body);
             var target = document.getElementById('foo')
             var spinner = new Spinner(opts).spin(target)
             */
            this._eles._spinner = new (this._eles.Spinner)(options || {});
        }
        return {
            show:function () {
                env._spinner.spin();
            },
            hide:function(){
                env._spinner.stop();
            }
        };
    }
};