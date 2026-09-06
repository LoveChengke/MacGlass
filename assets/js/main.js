/*!
 * MacGlass —— macOS 窗口风格 · 毛玻璃 Typecho 主题
 * 交互脚本（纯 ES5 + IIFE 封装，兼容 IE10+ / Chrome 40+）
 *
 * 职责：
 *   1. 毛玻璃开关   —— html.no-glass（【玻璃效果控制】，配合 style.css 第 17 节）
 *   2. 日夜模式       —— data-theme（light / dark / auto 跟随系统）
 *   3. 减少动画       —— html.no-motion（默认尊重 prefers-reduced-motion，WCAG）
 *   4. 设置抽屉       —— 齿轮按钮弹出，集中控制上述三项
 *   5. 信号灯         —— 黄灯回到顶部 / 绿灯全屏阅读（红灯是真实链接返回首页）
 *   6. 汉堡菜单、Ctrl+Enter 提交评论
 *   7. 滚动浮现       —— .reveal 元素进入视口时淡入上浮（IntersectionObserver，
 *                       旧浏览器自动降级为直接显示）
 *   8. 水滴涟漪       —— 玻璃开关点击时的扩散波纹
 *
 * 状态持久化：localStorage（macglass_* 键），不可用时自动降级为内存变量。
 * 语法约束：不使用箭头函数 / 模板字符串 / const-let / forEach 之外的 ES6 特性，
 *           以保证 IE11 无需 Babel 转译即可直接运行。
 */
(function (window, document) {
    'use strict';

    /* ================= 工具函数 ================= */
    var docEl = document.documentElement;
    var body = document.body;

    /* localStorage 可用性检测（隐私模式 / 旧浏览器安全降级） */
    var memoryStore = {};
    var storageOK = false;
    try {
        window.localStorage.setItem('__macglass_test__', '1');
        window.localStorage.removeItem('__macglass_test__');
        storageOK = true;
    } catch (e) {
        storageOK = false;
    }

    function read(key, fallback) {
        if (!storageOK) {
            return Object.prototype.hasOwnProperty.call(memoryStore, key) ? memoryStore[key] : fallback;
        }
        try {
            var v = window.localStorage.getItem(key);
            return (v === null || v === undefined) ? fallback : v;
        } catch (e) {
            return fallback;
        }
    }
    function write(key, value) {
        if (!storageOK) {
            memoryStore[key] = value;
            return;
        }
        try { window.localStorage.setItem(key, value); } catch (e) {}
    }

    function hasClass(el, cls) {
        return (' ' + el.className + ' ').indexOf(' ' + cls + ' ') !== -1;
    }
    function addClass(el, cls) {
        if (!hasClass(el, cls)) { el.className += ' ' + cls; }
    }
    function removeClass(el, cls) {
        el.className = (' ' + el.className + ' ').replace(' ' + cls + ' ', ' ');
        el.className = el.className.replace(/^\s+|\s+$/g, '');
    }
    function on(el, evt, fn) {
        if (el.addEventListener) {
            el.addEventListener(evt, fn, false);
        } else if (el.attachEvent) {
            el.attachEvent('on' + evt, fn);
        }
    }
    function byId(id) { return document.getElementById(id); }
    function media(query) {
        try { return window.matchMedia ? window.matchMedia(query) : null; } catch (e) { return null; }
    }
    function mqMatches(mql) { return !!(mql && mql.matches); }

    var darkMql = media('(prefers-color-scheme: dark)');
    var reduceMql = media('(prefers-reduced-motion: reduce)');

    function systemDark() { return mqMatches(darkMql); }
    function systemReduce() { return mqMatches(reduceMql); }

    /* ================= 状态应用（与 header.php 引导脚本一致，幂等） ================= */

    /* ---- 日夜模式：解析偏好为最终 light/dark ---- */
    function resolveTheme(pref) {
        if (pref === 'light' || pref === 'dark') { return pref; }
        return systemDark() ? 'dark' : 'light';
    }
    function applyTheme() {
        docEl.setAttribute('data-theme', resolveTheme(read('macglass_theme', 'auto')));
        syncThemeUI();
    }
    function setTheme(pref) {
        write('macglass_theme', pref);
        applyTheme();
    }

    /* ---- 毛玻璃开关（【玻璃效果控制】核心：切换 html.no-glass） ---- */
    function applyGlass() {
        if (read('macglass_glass', 'on') === 'off') {
            addClass(docEl, 'no-glass');
        } else {
            removeClass(docEl, 'no-glass');
        }
        syncGlassUI();
    }
    function setGlass(turnOn) {
        write('macglass_glass', turnOn ? 'on' : 'off');
        applyGlass();
    }

    /* ---- 减少动画（WCAG：auto 时尊重系统设置） ---- */
    function motionOff() {
        var pref = read('macglass_motion', 'auto');
        return pref === 'off' || (pref !== 'on' && systemReduce());
    }
    function applyMotion() {
        if (motionOff()) {
            addClass(docEl, 'no-motion');
        } else {
            removeClass(docEl, 'no-motion');
        }
        syncMotionUI();
    }
    function setMotion(allowMotion) {
        write('macglass_motion', allowMotion ? 'on' : 'off');
        applyMotion();
    }

    /* ---- 右上角个人简介：读取关闭状态（html.profile-hidden） ---- */
    function applyProfile() {
        if (read('macglass_profile', 'shown') === 'hidden') {
            addClass(docEl, 'profile-hidden');
        } else {
            removeClass(docEl, 'profile-hidden');
        }
    }

    /* ================= UI 同步 ================= */
    function syncThemeUI() {
        var pref = read('macglass_theme', 'auto');
        var btns = document.querySelectorAll('[data-theme-pref]');
        var i;
        for (i = 0; i < btns.length; i++) {
            if (btns[i].getAttribute('data-theme-pref') === pref) {
                addClass(btns[i], 'is-active');
            } else {
                removeClass(btns[i], 'is-active');
            }
        }
    }
    function syncGlassUI() {
        var sw = byId('sw-glass');
        var fab = byId('glass-toggle');
        var onGlass = !hasClass(docEl, 'no-glass');
        if (sw) { sw.checked = onGlass; }
        if (fab) {
            fab.setAttribute('aria-pressed', onGlass ? 'true' : 'false');
            fab.setAttribute('title', onGlass ? '毛玻璃：开（点击关闭）' : '毛玻璃：关（点击开启）');
        }
    }
    function syncMotionUI() {
        var sw = byId('sw-motion');
        if (sw) { sw.checked = motionOff(); }
    }
    function syncAll() {
        applyTheme();
        applyGlass();
        applyMotion();
        applyProfile();
    }

    /* ================= 抽屉 ================= */
    var drawer, drawerBackdrop, drawerCloseBtn, gearBtn;

    function openDrawer() {
        addClass(docEl, 'drawer-open');
        if (gearBtn) { gearBtn.setAttribute('aria-expanded', 'true'); }
        if (drawer) { drawer.setAttribute('aria-hidden', 'false'); }
        if (drawerBackdrop) { drawerBackdrop.removeAttribute('hidden'); }
        if (drawerCloseBtn) { drawerCloseBtn.focus(); }
    }
    function closeDrawer() {
        removeClass(docEl, 'drawer-open');
        if (gearBtn) { gearBtn.setAttribute('aria-expanded', 'false'); }
        if (drawer) { drawer.setAttribute('aria-hidden', 'true'); }
        if (drawerBackdrop) { drawerBackdrop.setAttribute('hidden', 'hidden'); }
    }

    /* ================= 汉堡菜单 ================= */
    var navToggle;
    function closeNav() {
        removeClass(docEl, 'nav-open');
        if (navToggle) { navToggle.setAttribute('aria-expanded', 'false'); }
    }

    /* ================= 回顶（黄灯 / 回顶按钮共用；v1.0.0 滚动发生在正文面板内） ================= */
    function scrollTop() {
        var area = byId('content-area');
        if (area) {
            if (hasClass(docEl, 'no-motion')) { area.scrollTop = 0; return; }
            try {
                area.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                area.scrollTop = 0;
            }
            return;
        }
        if (hasClass(docEl, 'no-motion')) {
            window.scrollTo(0, 0);
            return;
        }
        try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (e) {
            window.scrollTo(0, 0);
        }
    }
    function enterFullscreen() {
        var el = docEl;
        try {
            if (el.requestFullscreen) { el.requestFullscreen(); }
            else if (el.webkitRequestFullscreen) { el.webkitRequestFullscreen(); }
            else if (el.msRequestFullscreen) { el.msRequestFullscreen(); }
        } catch (e) {}
    }
    function exitFullscreen() {
        try {
            if (document.exitFullscreen) { document.exitFullscreen(); }
            else if (document.webkitExitFullscreen) { document.webkitExitFullscreen(); }
            else if (document.msExitFullscreen) { document.msExitFullscreen(); }
        } catch (e) {}
    }
    function toggleReading() {
        if (hasClass(body, 'reading-mode')) {
            removeClass(body, 'reading-mode');
            exitFullscreen();
        } else {
            addClass(body, 'reading-mode');
            enterFullscreen(); /* 原生全屏为渐进增强，失败时仅保留阅读模式样式 */
        }
    }
    function onFullscreenChange() {
        var fs = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
        if (!fs) { removeClass(body, 'reading-mode'); } /* 用户按 ESC 退出全屏时同步退出阅读模式 */
    }

    /* ================= 滚动浮现（.reveal） ================= */
    /* IntersectionObserver 存在则元素进入视口时添加 .is-visible（一次性，
     * 之后取消观察省性能）；不支持（IE11 / Chrome 40）或 html.no-motion
     * 时直接全部显示。CSS 端以 html.js-anim 门控隐藏态，无 JS 时内容始终可见。 */
    function initReveal() {
        var items = document.querySelectorAll('.reveal');
        var i;
        if (!items.length) { return; }
        if (hasClass(docEl, 'no-motion') || !('IntersectionObserver' in window)) {
            for (i = 0; i < items.length; i++) { addClass(items[i], 'is-visible'); }
            return;
        }
        try {
            var io = new IntersectionObserver(function (entries) {
                for (var j = 0; j < entries.length; j++) {
                    var entry = entries[j];
                    if (entry.isIntersecting || entry.intersectionRatio > 0) {
                        addClass(entry.target, 'is-visible');
                        io.unobserve(entry.target);
                    }
                }
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            for (i = 0; i < items.length; i++) { io.observe(items[i]); }
        } catch (e) {
            for (i = 0; i < items.length; i++) { addClass(items[i], 'is-visible'); }
        }
    }

    /* ================= 事件绑定 ================= */
    function boot() {
        addClass(docEl, 'js-anim'); /* 门控 .reveal 隐藏态：JS 可用才启用滚动浮现 */
        syncAll();
        initReveal();

        drawer = byId('settings-drawer');
        drawerBackdrop = byId('drawer-backdrop');
        drawerCloseBtn = byId('drawer-close');
        gearBtn = byId('settings-gear');
        navToggle = byId('nav-toggle');

        /* 顶部太阳 / 月亮按钮：在浅色与深色间切换（写入显式偏好） */
        var themeToggle = byId('theme-toggle');
        if (themeToggle) {
            on(themeToggle, 'click', function () {
                var isDark = docEl.getAttribute('data-theme') === 'dark';
                setTheme(isDark ? 'light' : 'dark');
            });
        }

        /* 右下角水滴按钮：毛玻璃即时开关 + 涟漪反馈 */
        var glassToggle = byId('glass-toggle');
        if (glassToggle) {
            on(glassToggle, 'click', function () {
                setGlass(hasClass(docEl, 'no-glass'));
                addClass(glassToggle, 'ripple');
                window.setTimeout(function () {
                    removeClass(glassToggle, 'ripple');
                }, 650);
            });
        }

        /* 抽屉开关 */
        if (gearBtn) { on(gearBtn, 'click', openDrawer); }
        if (drawerCloseBtn) { on(drawerCloseBtn, 'click', closeDrawer); }
        if (drawerBackdrop) { on(drawerBackdrop, 'click', closeDrawer); }

        /* ESC 关闭抽屉 / 菜单 / 标题栏下拉 */
        on(document, 'keydown', function (e) {
            e = e || window.event;
            var key = e.keyCode || e.which;
            if (key === 27) {
                if (hasClass(docEl, 'drawer-open')) { closeDrawer(); }
                if (hasClass(docEl, 'nav-open')) { closeNav(); }
                closeDropdowns();
            }
        });

        /* 抽屉内：玻璃开关（勾选 = 开启玻璃） */
        var swGlass = byId('sw-glass');
        if (swGlass) {
            on(swGlass, 'change', function () {
                setGlass(swGlass.checked);
            });
        }
        /* 抽屉内：减少动画开关（勾选 = 减少动画） */
        var swMotion = byId('sw-motion');
        if (swMotion) {
            on(swMotion, 'change', function () {
                setMotion(!swMotion.checked);
            });
        }
        /* 抽屉内：日夜模式分段选择 */
        var segBtns = document.querySelectorAll('[data-theme-pref]');
        var i;
        for (i = 0; i < segBtns.length; i++) {
            on(segBtns[i], 'click', function () {
                setTheme(this.getAttribute('data-theme-pref'));
            });
        }

        /* 汉堡菜单 */
        if (navToggle) {
            on(navToggle, 'click', function () {
                if (hasClass(docEl, 'nav-open')) {
                    closeNav();
                } else {
                    addClass(docEl, 'nav-open');
                    navToggle.setAttribute('aria-expanded', 'true');
                }
            });
        }
        /* 点击导航链接后自动收起菜单 */
        var navLinks = byId('nav-links');
        if (navLinks) {
            on(navLinks, 'click', function (e) {
                e = e || window.event;
                var t = e.target || e.srcElement;
                while (t && t !== navLinks) {
                    if (t.tagName && t.tagName.toLowerCase() === 'a') {
                        closeNav();
                        return;
                    }
                    t = t.parentNode;
                }
            });
        }

        /* 点击空白处（菜单之外）同样关闭汉堡菜单 */
        on(document, 'click', function (e) {
            e = e || window.event;
            if (!hasClass(docEl, 'nav-open')) { return; }
            var t = e.target || e.srcElement;
            while (t && t.nodeType === 1) {
                if (t.id === 'nav-links' || t.id === 'nav-toggle') { return; }
                t = t.parentNode;
            }
            closeNav();
        });

        /* 信号灯：全局事件委托
         * 红灯 = <a href> 返回首页（无需 JS）；抽屉红灯 = 关闭抽屉
         * 黄灯 = 回到顶部；绿灯 = 全屏阅读模式 */
        on(document, 'click', function (e) {
            e = e || window.event;
            var t = e.target || e.srcElement;
            while (t && t.nodeType === 1) {
                if (t.className && typeof t.className === 'string') {
                    if (t.className.indexOf('tl-minimize') !== -1) {
                        scrollTop();
                        return;
                    }
                    if (t.className.indexOf('tl-zoom') !== -1) {
                        toggleReading();
                        return;
                    }
                    if (t.className.indexOf('tl-close') !== -1) {
                        /* 只有按钮形态的红灯需要 JS（如抽屉关闭钮）；
                         * 窗口上的红灯是 <a> 链接，交给浏览器原生跳转 */
                        if (t.tagName && t.tagName.toLowerCase() !== 'a') {
                            closeDrawer();
                            return;
                        }
                    }
                }
                t = t.parentNode;
            }
        });

        /* 全屏变化监听（用户按 ESC 退出原生全屏时同步退出阅读模式） */
        on(document, 'fullscreenchange', onFullscreenChange);
        on(document, 'webkitfullscreenchange', onFullscreenChange);
        on(document, 'MSFullscreenChange', onFullscreenChange);

        /* 评论框 Ctrl / Cmd + Enter 快速提交 */
        var textarea = byId('textarea');
        if (textarea) {
            on(textarea, 'keydown', function (e) {
                e = e || window.event;
                var key = e.keyCode || e.which;
                if ((e.ctrlKey || e.metaKey) && key === 13) {
                    var form = byId('comment-form');
                    if (form) { form.submit(); }
                }
            });
        }

        /* 右上角个人简介：点击 × 关闭并记忆 */
        var profileClose = byId('profile-close');
        if (profileClose) {
            on(profileClose, 'click', function () {
                write('macglass_profile', 'hidden');
                addClass(docEl, 'profile-hidden');
            });
        }

        /* 一键回顶按钮：正文面板滚动超过 300px 后显示，点击回到顶部。
         * scroll 事件用 requestAnimationFrame 节流，滚动帧内只做一次 class 判断，
         * 避免高频滚动时与玻璃背景重绘争抢主线程（掉帧优化）。 */
        var backtop = byId('backtop');
        var contentArea = byId('content-area');
        if (backtop && contentArea) {
            var backtopTicking = false;
            var updateBacktop = function () {
                backtopTicking = false;
                if (contentArea.scrollTop > 300) {
                    addClass(backtop, 'is-visible');
                } else {
                    removeClass(backtop, 'is-visible');
                }
            };
            var requestBacktop = function () {
                if (backtopTicking) { return; }
                backtopTicking = true;
                if (window.requestAnimationFrame) {
                    window.requestAnimationFrame(updateBacktop);
                } else {
                    updateBacktop();
                }
            };
            requestBacktop();
            on(contentArea, 'scroll', requestBacktop);
            on(backtop, 'click', function () {
                scrollTop();
            });
        }

        /* 标题栏下拉（分类 / 归档，桌面版）：点击切换、点击外部或 ESC 关闭 */
        function closeDropdowns() {
            var dds = document.querySelectorAll('.tb-dd.is-open');
            for (var di = 0; di < dds.length; di++) {
                removeClass(dds[di], 'is-open');
                var ddBtn = dds[di].getElementsByTagName('button')[0];
                if (ddBtn) { ddBtn.setAttribute('aria-expanded', 'false'); }
            }
        }
        var ddBtns = document.querySelectorAll('.tb-dd-btn');
        for (var dj = 0; dj < ddBtns.length; dj++) {
            on(ddBtns[dj], 'click', function (e) {
                e = e || window.event;
                if (e.stopPropagation) { e.stopPropagation(); }
                var dd = this.parentNode;
                var wasOpen = hasClass(dd, 'is-open');
                closeDropdowns();
                if (!wasOpen) {
                    addClass(dd, 'is-open');
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        }
        on(document, 'click', closeDropdowns);
    }

    /* 系统偏好变化监听（仅当用户选择「跟随系统 / auto」时生效） */
    function watchMedia(mql, handler) {
        if (!mql) { return; }
        try {
            if (mql.addEventListener) {
                mql.addEventListener('change', handler, false);
            } else if (mql.addListener) {
                mql.addListener(handler); /* Safari 旧版 / IE */
            }
        } catch (e) {}
    }
    watchMedia(darkMql, function () {
        if (read('macglass_theme', 'auto') === 'auto') { applyTheme(); }
    });
    watchMedia(reduceMql, function () {
        if (read('macglass_motion', 'auto') === 'auto') { applyMotion(); }
    });

    /* 视口拉宽到桌面布局（≥768px，浏览器响应式切换 / 手机横屏）时
     * 自动收起汉堡菜单：避免 nav-open 状态残留，标题栏出现滚轮条 */
    watchMedia(media('(min-width: 768px)'), function (e) {
        if (e && e.matches) { closeNav(); }
    });

    /* 页面切到后台时挂 html.page-hidden：CSS 端暂停极光漂移动画，
     * 停止 backdrop-filter 的逐帧重采样（省电 / 掉帧优化） */
    if (document.addEventListener) {
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                addClass(docEl, 'page-hidden');
            } else {
                removeClass(docEl, 'page-hidden');
            }
        }, false);
    }

    /* DOM 就绪后启动（IE8- 走 readystatechange 兜底） */
    function ready(fn) {
        if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', fn, false);
        } else if (document.attachEvent) {
            document.attachEvent('onreadystatechange', function () {
                if (document.readyState === 'complete') { fn(); }
            });
        }
    }
    ready(boot);
})(window, document);
