	! function() {
    ! function(t) {
        "use strict";
        "function" == typeof define && define.amd ? define(["jquery"], t) : "object" == typeof module && module.exports ? module.exports = function(i, s) {
            return void 0 === s && (s = "undefined" != typeof window ? require("jquery") : require("jquery")(i)), t(s), s
        } : t(jQuery)
    }(function(t) {
        "use strict";
        var i = "transition" in document.documentElement.style || "WebkitTransition" in document.documentElement.style,
            s = function(i, s) {
                var o = {
                    useMouse: !0,
                    axis: "x",
                    page: 0,
                    speed: 100,
                    side: 0,
                    snap: !1,
                    scrollbar: !1,
                    scrollbarAutoHide: !0,
                    initComplete: null,
                    stopped: null,
                    resizeend: null
                };
                this.opts = t.extend({}, o, s), this.target = i, this.wrap = t(i), this.list = this.wrap.children(), this.wrapw = null, this.wraph = null, this.listw = null, this.listh = null, this.tmp = null, this.ticker = null, this.duration = this.opts.speed, this.side = this.opts.side, this.delay = 0, this.posx = 0, this.posy = 0, this.startx = 0, this.starty = 0, this.speedx = 0, this.speedy = 0, this.prevx = 0, this.prevy = 0, this.tempx = 0, this.tempy = 0, this.right = 0, this.bottom = 0, this.state = !1, this.link = !0, this.lastmove = null, this.scroll = {}, this.init()
            };
        s.prototype = {
            init: function() {
                return this.list = this.wrap.children(), this.state = !1, "string" == typeof this.opts.page && (this.opts.page = t(this.target).find(this.opts.page).eq(0).index()), this.set_limit(), this.go_page(this.opts.page), this.list.off("touchstart", this, this.touchstart).off("touchmove", this, this.touchmove).off("touchend touchcancel", this.touchend).off("transitionend", this, this.transitionend).off("wheel", this, this.wheel).off("dragstart").on("dragstart", function(t) {
                    t.preventDefault()
                }).on("touchstart", this, this.touchstart).on("touchmove", this, this.touchmove).on("touchend touchcancel", this, this.touchend).on("transitionend", this, this.transitionend).on("wheel", this, this.wheel), this.opts.useMouse && this.list.off("mousedown", this, this.touchstart).on("mousedown", this, this.touchstart), t(window).off("resize", this, this.resize).on("resize", this, this.resize), this.list.find("a").on("click", this, function(t) {
                    return t.data.link ? void 0 : !1
                }), this.opts.scrollbar && this.scrollbar_init(), "function" == typeof this.opts.initComplete && this.opts.initComplete.call(this, this.get_event_data()), this
            },
            scrollbar_init: function() {
                void 0 === this.scroll.wrap && (this.scroll.wrap = t('<div class="scroll_menu-scrollbar"><div></div></div>'), this.scroll.bar = this.scroll.wrap.find(" > div"), this.wrap.append(this.scroll.wrap), this.scrollbar_pos())
            },
            scrollbar_pos: function(t) {
                if (this.opts.scrollbar) {
                    var i, s, o = this.scroll.wrap,
                        e = this.scroll.bar,
                        h = t ? "300ms" : "0ms";
                    "x" === this.opts.axis ? (i = this.wrapw / this.listw * 100, s = -this.posX() / this.listw * 100, o.css({
                        width: "100%",
                        height: "5px",
                        opacity: 1
                    }), e.css({
                        width: i + "%",
                        height: "5px",
                        left: s + "%",
                        transition: h
                    })) : "y" === this.opts.axis && (i = this.wraph / this.listh * 100, s = -this.posY() / this.listh * 100, o.css({
                        width: "5px",
                        height: "100%",
                        opacity: 1
                    }), e.css({
                        width: "5px",
                        height: i + "%",
                        top: s + "%",
                        transition: h
                    })), this.opts.scrollbarAutoHide && setTimeout(function() {
                        t && o.css({
                            opacity: 0
                        })
                    }, 0)
                }
            },
            touchstart: function(i) {
                var s = i.data,
                    o = t(this).position();
                s.startx = i.originalEvent.touches ? i.originalEvent.touches[0].pageX : i.pageX, s.starty = i.originalEvent.touches ? i.originalEvent.touches[0].pageY : i.pageY, s.posx = s.startx - o.left, s.posy = s.starty - o.top, "function" == typeof s.opts.stopped && 0 !== s.speedx && s.opts.stopped.call(s, s.get_event_data()), s.speedx = 0, s.speedy = 0, s.prevx = 0, s.prevy = 0, s.tempx = 0, s.tempy = 0, s.stop_animation(), s.start_animation(), s.state = !0, s.lastmove = i, i.stopPropagation(), "mousedown" == i.type && t(document).on("mousemove", s, s.touchmove).on("mouseup", s, s.touchend)
            },
            touchmove: function(t) {
                var i = t.data,
                    s = t.originalEvent.touches ? t.originalEvent.touches[0].pageX : t.pageX,
                    o = t.originalEvent.touches ? t.originalEvent.touches[0].pageY : t.pageY,
                    e = s - i.startx,
                    h = o - i.starty,
                    n = 0 > e ? -1 * e : e,
                    a = 0 > h ? -1 * h : h,
                    r = s - i.posx,
                    p = o - i.posy,
                    l = i.get_limit({
                        x: r
                    }),
                    u = i.get_limit({
                        y: p
                    }),
                    c = r - l,
                    d = p - u;
                "x" == i.opts.axis && n > a ? (i.link = !1, t.preventDefault(), Math.abs(c) > 0 && (r -= c / 2), i.set_pos({
                    x: r
                })) : "y" == i.opts.axis && a > n && (i.link = !1, t.preventDefault(), Math.abs(d) > 0 && (p -= d / 2), i.set_pos({
                    y: p
                })), i.lastmove = t, t.stopPropagation()
            },
            touchend: function(i) {
                var s = i.data,
                    o = s.lastmove.originalEvent.touches ? s.lastmove.originalEvent.touches[0].pageX : s.lastmove.pageX,
                    e = s.lastmove.originalEvent.touches ? s.lastmove.originalEvent.touches[0].pageY : s.lastmove.pageY,
                    h = o - s.startx,
                    n = e - s.starty,
                    a = t(s.list).position(),
                    r = a.left,
                    p = a.top,
                    l = 0;
                s.state = !1, s.speedx = s.tempx, s.speedy = s.tempy, "x" != s.opts.axis || s.limit_chk({
                    x: r
                }) ? "y" != s.opts.axis || s.limit_chk({
                    y: p
                }) || (l = 0 > n ? s.bottom : 0, s.stop_animation(), s.set_pos({
                    y: l
                }, s.duration)) : (l = 0 > h ? s.right : 0, s.stop_animation(), s.set_pos({
                    x: l
                }, s.duration)), setTimeout(function() {
                    s.link = !0
                }, 50), i.stopPropagation(), s.opts.useMouse && t(document).off("mousemove", s.touchmove).off("mouseup", s.touchend)
            },
            transitionend: function(t) {
                var i = t.data;
                "function" == typeof i.opts.stopped && i.opts.stopped.call(i, i.get_event_data()), i.scrollbar_pos(!0)
            },
            resize: function(t) {
                var i = t.data;
                clearTimeout(i.tmp), i.tmp = setTimeout(function() {
                    i.set_limit(), i.pos_control(), "function" == typeof i.opts.resizeend && i.opts.resizeend.call(i, i.get_event_data()), i.scrollbar_pos(!0)
                }, 200)
            },
            wheel: function(t) {
                var i = t.data,
                    s = 0,
                    o = {},
                    e = 100;
                if (t.originalEvent.deltaY > 0) "x" === i.opts.axis ? (s = i.posX(), o = {
                    x: s - 1
                }, i.posX(s - e)) : (s = i.posY(), o = {
                    y: s - 1
                }, i.posY(s - e));
                else {
                    if (!(t.originalEvent.deltaY < 0)) return !0;
                    "x" === i.opts.axis ? (s = i.posX(), o = {
                        x: s + 1
                    }, i.posX(s + e)) : (s = i.posY(), o = {
                        y: s + 1
                    }, i.posY(s + e))
                }
                i.limit_chk(o) === !0 && t.preventDefault(), i.scrollbar_pos(!0)
            },
            get_nearby_page: function(t) {
                var i = "number" == typeof t ? Math.abs(t) : null,
                    s = [],
                    o = this.list.offset(),
                    e = this.list.children(),
                    h = e.length,
                    n = "";
                "x" === this.opts.axis ? (n = "left", null === i && (i = Math.abs(o.left))) : (n = "top", null === i && (i = Math.abs(o.top)));
                for (var a = 0; h > a; ++a) s[a] = Math.abs(e.eq(a).offset()[n] - o[n] - i);
                return s.indexOf(Math.min.apply(null, s))
            },
            start_animation: function() {
                window.requestAnimationFrame ? this.ticker = requestAnimationFrame(this.move.bind(this)) : this.ticker = setTimeout(this.move.bind(this), this.delay)
            },
            stop_animation: function() {
                window.requestAnimationFrame ? cancelAnimationFrame(this.ticker) : clearTimeout(this.ticker)
            },
            move: function() {
                var t = this.list.position(),
                    i = t.left,
                    s = t.top;
                if (this.state === !1) {
                    var o = Math.abs(this.speedx),
                        e = Math.abs(this.speedy);
                    if (this.opts.snap && "x" === this.opts.axis && 10 > o) return this.stop_animation(), void this.go_page(this.get_nearby_page(i));
                    if (this.opts.snap && "y" === this.opts.axis && 10 > e) return this.stop_animation(), void this.go_page(this.get_nearby_page(s));
                    if (1 > o && 1 > e) return this.stop_animation(), "function" == typeof this.opts.stopped && 0 !== this.speedx && this.opts.stopped.call(this, this.get_event_data()), void this.scrollbar_pos(!0);
                    this.speedx = .95 * this.speedx, this.speedy = .95 * this.speedy, i += this.speedx, s += this.speedy;
                    var h = this.limit_chk({
                            x: i
                        }),
                        n = this.get_limit({
                            x: i
                        }),
                        a = this.limit_chk({
                            y: s
                        }),
                        r = this.get_limit({
                            y: s
                        });
                    if (h && a) this.set_pos({
                        x: n,
                        y: r
                    });
                    else {
                        if (this.set_pos({
                                x: i,
                                y: s
                            }), Math.abs(i - n) > 70) return this.stop_animation(), void this.set_pos({
                            x: n
                        }, this.duration);
                        if (Math.abs(s - r) > 70) return this.stop_animation(), void this.set_pos({
                            y: r
                        }, this.duration)
                    }
                } else this.tempx = i - this.prevx, this.tempy = s - this.prevy, this.prevx = i, this.prevy = s;
                this.scrollbar_pos(), this.start_animation()
            },
            limit_chk: function(t) {
                return void 0 !== t.x ? t.x > 0 == !1 && t.x < this.right == !1 : void 0 !== t.y ? t.y > 0 == !1 && t.y < this.bottom == !1 : void 0
            },
            set_limit: function() {
                var i = 0,
                    s = 0;
                this.wrapw = this.wrap.width(), this.wraph = this.wrap.height(), t(this.list.children()).each(function() {
                    i += t(this).outerWidth(), s += t(this).outerHeight()
                }), this.listw = i, this.listh = s, this.wrapw < this.listw ? this.right = -(this.listw - this.wrapw) : this.wrapw > this.listw ? this.right = 0 : this.right = -this.listw, this.wraph < this.listh ? this.bottom = -(this.listh - this.wraph) : this.wraph > this.listh ? this.bottom = 0 : this.bottom = -this.listh
            },
            get_limit: function(t) {
                return void 0 !== t.x ? (t.x > 0 && (t.x = 0), t.x < this.right && (t.x = this.right), t.x) : void 0 !== t.y ? (t.y > 0 && (t.y = 0), t.y < this.bottom && (t.y = this.bottom), t.y) : void 0
            },
            go_page: function(i) {
                var s = this.list.offset(),
                    o = this.list.children().map(function() {
                        return t(this).offset()
                    });
                if (this.opts.page = i, "x" == this.opts.axis) {
                    var e = -(o[i].left - s.left - (o[0].left - s.left));
                    this.posX(e)
                } else if ("y" == this.opts.axis) {
                    var h = -(o[i].top - s.top - (o[0].top - s.top));
                    this.posY(h)
                }
                return this
            },
            view_page: function(t) {
                this.opts.page = t;
                var i = this.list.children().eq(t);
                if ("x" == this.opts.axis) {
                    var s = i.position().left,
                        o = i.width(),
                        e = this.wrapw * this.side,
                        h = this.posX();
                    0 > s + h && (s = -(s - e), this.posX(s)), s + o + h > this.wrapw && (s = -(s + o - this.wrapw + e), this.posX(s))
                } else if ("y" == this.opts.axis) {
                    var n = i.position().top,
                        a = i.height(),
                        r = this.wraph * this.side,
                        p = this.posY();
                    0 > n + p && (n = -(n - r), this.posY(n)), n + a + p > this.wraph && (n = -(n + a - this.wraph + r), this.posY(n))
                }
                return this
            },
            pos_control: function() {
                var t = this.posX(),
                    i = this.posY(),
                    s = this.limit_chk({
                        x: t
                    }),
                    o = this.get_limit({
                        x: t
                    }),
                    e = this.limit_chk({
                        y: i
                    }),
                    h = this.get_limit({
                        y: i
                    });
                s && e || (this.set_pos({
                    x: t,
                    y: i
                }), Math.abs(t - o) > 70 && this.set_pos({
                    x: o
                }, this.duration), Math.abs(i - h) > 70 && this.set_pos({
                    y: h
                }, this.duration))
            },
            set_pos: function(s, o) {
                s = t.extend({}, {
                    x: 0,
                    y: 0
                }, s);
                var e = i ? {
                    transition: o ? o + "ms linear" : "0ms",
                    transform: "translate3d(" + s.x + "px, " + s.y + "px)"
                } : {
                    left: s.x + "px",
                    top: s.y + "px"
                };
                this.list.css(e)
            },
            posX: function(t) {
                if (void 0 !== t) {
                    var i = this.get_limit({
                        x: t
                    });
                    return "first" == t ? i = 0 : "last" == t && (i = this.right), this.stop_animation(), this.set_pos({
                        x: i
                    }, this.duration), this
                }
                return this.list.position().left
            },
            posY: function(t) {
                if (void 0 !== t) {
                    var i = this.get_limit({
                        y: t
                    });
                    return "first" == t ? i = 0 : "last" == t && (i = this.bottom), this.stop_animation(), this.set_pos({
                        y: i
                    }, this.duration), this
                }
                return this.list.position().top
            },
            get_event_data: function() {
                return {
                    obj: this,
                    target: this.target,
                    posX: this.posX(),
                    posY: this.posY()
                }
            }
        }, t.fn.scrollMenuCategory = function(i) {
            return this.each(function() {
                t.data(this, "scrollMenuCategory") || t.data(this, "scrollMenuCategory", new s(this, i))
            })
        }
    })
}(jQuery)