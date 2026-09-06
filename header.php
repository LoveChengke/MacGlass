<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* ============================================================
 * 主题安全配置（后台设置 → 前端变量）
 * ============================================================ */
/* 主色安全校验：只允许 #RGB / #RGBA / #RRGGBB / #RRGGBBAA 格式，防止 CSS 注入 */
$macglassPrimary = trim((string)$this->options->primaryColor);
if (!preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $macglassPrimary)) {
    $macglassPrimary = '#0a84ff';
}
/* 壁纸风格校验 */
$macglassWallpaper = in_array($this->options->wallpaper, array('aurora', 'monterey', 'sunset'))
    ? $this->options->wallpaper : 'aurora';

/* 玻璃模糊强度校验（14 / 20 / 32，非法回退 20） */
$macglassGlassBlur = in_array($this->options->glassBlur, array('14', '20', '32'))
    ? $this->options->glassBlur : '20';

/* 自定义背景图片校验：http(s):// 或 // 或根相对 /…，非法回退预设壁纸 */
$macglassWallpaperImage = trim((string)$this->options->wallpaperImage);
if ($macglassWallpaperImage !== ''
    && !preg_match('#^(https?:)?//#i', $macglassWallpaperImage)
    && $macglassWallpaperImage[0] !== '/') {
    $macglassWallpaperImage = '';
}

/* 标题栏导航的独立页面列表 */
$this->widget('Widget_Contents_Page_List')->to($macglassPages);

/* ------------------------------------------------------------
 * 标题栏下拉（桌面版）：分类 / 归档 集成在 Mac 窗口栏上
 * 使用 @tb 别名实例化独立 Widget，避免与手机汉堡菜单的 Widget
 * 池实例互相干扰（部分迭代 + break 会打乱共享实例的游标）。
 * ------------------------------------------------------------ */
$this->widget('Widget_Metas_Category_List@tb')->to($macglassTbCats);
$this->widget('Widget_Contents_Post_Date@tb', 'type=month')->to($macglassTbDates);

/* 「显示更多」的目标：使用 page-archive.php 模板的归档页面；
 * 未创建该页面时回退到站点首页。 */
$macglassArchivePageUrl = $this->options->siteUrl;
$macglassHasArchivePage = false;
$this->widget('Widget_Contents_Page_List@tb')->to($macglassTbPages);
while ($macglassTbPages->next()) {
    if (!empty($macglassTbPages->template) && 'page-archive.php' == $macglassTbPages->template) {
        $macglassArchivePageUrl = $macglassTbPages->permalink;
        $macglassHasArchivePage = true;
        break;
    }
}

/* ------------------------------------------------------------
 * 固定个人小简介（右上角，不随页面滚动）
 * 后台外观设置：profileAvatar / profileName / profileIntro，
 * 留空时自动回退站点名与站点描述。
 * ------------------------------------------------------------ */
/* 载入小部件渲染函数（widgets.php 只定义函数、不直接输出；
 * 规避 need() 独立作用域 / require_once 限制） */
if (!function_exists('macglass_widgets')) {
    $this->need('widgets.php');
}

/* 个人简介数据（头像 / 昵称 / 介绍 / 名字首字）：
 * 与侧边栏 About 卡片共用 macglass_profile()，头像统一读取主题设置链接 */
list($macglassProfileAvatar, $macglassProfileName, $macglassProfileIntro, $macglassProfileInitial)
    = macglass_profile($this);

/* 品牌 Logo（标题栏 / 标签页图标）：优先主题设置 brandLogo，其次个人头像 */
$macglassBrandLogo = macglass_brand_logo($this);
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
<meta charset="<?php $this->options->charset(); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#f7f7fa" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1c1c1e" media="(prefers-color-scheme: dark)">
<title><?php $this->archiveTitle(array(
    'category' => _t('分类 %s 下的文章'),
    'search'   => _t('包含关键字 %s 的文章'),
    'tag'      => _t('标签 %s 下的文章'),
    'author'   => _t('%s 发布的文章')
), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php $this->header(); ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>">
<?php if ($macglassBrandLogo !== ''): ?>
<link rel="icon" href="<?php echo macglass_esc($macglassBrandLogo); ?>">
<?php endif; ?>
<style>
/* 后台设置的主题色与玻璃模糊强度以 CSS 变量注入（style.css 的 :root 默认值为兜底） */
:root {
    --primary: <?php echo $macglassPrimary; ?>;
    --glass-blur: <?php echo $macglassGlassBlur; ?>px;
}
</style>
<?php if ($macglassWallpaperImage !== ''): ?>
<style>
/* 自定义背景图片（字面量 url，IE11 同样生效）；深色模式叠加暗化层保证文字可读 */
body.wallpaper-custom {
    background-image: url('<?php echo macglass_esc($macglassWallpaperImage); ?>');
}
html[data-theme="dark"] body.wallpaper-custom {
    background-image: linear-gradient(rgba(0, 0, 0, .48), rgba(0, 0, 0, .48)), url('<?php echo macglass_esc($macglassWallpaperImage); ?>');
}
</style>
<?php endif; ?>
<script>
/* ============================================================
 * 【首屏引导脚本】在页面绘制前同步执行，避免深色模式 / 玻璃状态闪烁。
 * 状态键（与 main.js 共用）：
 *   macglass_theme   light | dark | auto    —— 日夜模式
 *   macglass_glass   on | off               —— 毛玻璃开关
 *   macglass_motion  on | off | auto        —— 减少动画开关
 *   macglass_profile hidden                —— 右上角个人简介是否关闭
 * 纯 ES5 语法，兼容 IE10+；localStorage 不可用时静默降级为默认值。
 * ============================================================ */
(function () {
    'use strict';
    var d = document.documentElement, pref, dark;

    function read(key) {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    }
    function systemDark() {
        try { return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
        catch (e) { return false; }
    }
    function systemReduce() {
        try { return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); }
        catch (e) { return false; }
    }

    /* ---- 日夜模式：auto 跟随系统 prefers-color-scheme ---- */
    pref = read('macglass_theme');
    if (pref !== 'light' && pref !== 'dark') { pref = 'auto'; }
    dark = (pref === 'dark') || (pref === 'auto' && systemDark());
    d.setAttribute('data-theme', dark ? 'dark' : 'light');

    /* ---- 毛玻璃：off 时挂 no-glass 类（CSS 端移除 backdrop-filter 并改纯色） ---- */
    if (read('macglass_glass') === 'off') { d.className += ' no-glass'; }

    /* ---- 减少动画：auto 时尊重系统 prefers-reduced-motion（WCAG） ---- */
    pref = read('macglass_motion');
    if (pref === 'off' || (pref !== 'on' && systemReduce())) { d.className += ' no-motion'; }

    /* ---- 右上角个人简介：用户点 × 关闭后保持隐藏 ---- */
    if (read('macglass_profile') === 'hidden') { d.className += ' profile-hidden'; }
})();
</script>
</head>
<body class="wallpaper-<?php echo $macglassWallpaper; ?><?php if ($macglassWallpaperImage !== ''): ?> wallpaper-custom<?php endif; ?><?php if ($this->is('post') || $this->is('page')): ?> is-single<?php endif; ?>">

<!-- 固定个人小简介（页面右上角，position: fixed，不随页面滚动） -->
<aside class="profile-fixed glass-panel" id="profile-fixed" aria-label="<?php _e('个人简介'); ?>">
    <button type="button" class="profile-close" id="profile-close" aria-label="<?php _e('关闭个人简介'); ?>" title="<?php _e('关闭'); ?>">×</button>
    <div class="profile-avatar">
        <?php if ($macglassProfileAvatar !== ''): ?>
        <img src="<?php echo macglass_esc($macglassProfileAvatar); ?>" alt="<?php echo macglass_esc($macglassProfileName); ?>">
        <?php else: ?>
        <span aria-hidden="true"><?php echo macglass_esc($macglassProfileInitial); ?></span>
        <?php endif; ?>
    </div>
    <strong class="profile-name"><?php echo macglass_esc($macglassProfileName); ?></strong>
    <p class="profile-intro"><?php echo macglass_esc($macglassProfileIntro); ?></p>
</aside>

<!-- ============================================================
     唯一 Mac 窗口：顶部栏功能（导航/搜索/日夜切换/汉堡）集成在标题栏上
     ============================================================ -->
<main class="site-frame" id="site-frame">
<section class="mac-window root-window" id="root-window">

    <header class="titlebar toolbar">
        <span class="titlebar-sheen" aria-hidden="true"></span>
        <!-- 红 = 返回首页；黄 = 回到顶部；绿 = 全屏阅读 -->
        <div class="traffic-lights">
            <a class="traffic-light tl-close" href="<?php $this->options->siteUrl(); ?>" title="<?php _e('关闭：返回首页'); ?>" aria-label="<?php _e('返回首页'); ?>"></a>
            <button type="button" class="traffic-light tl-minimize" title="<?php _e('最小化：回到顶部'); ?>" aria-label="<?php _e('回到顶部'); ?>"></button>
            <button type="button" class="traffic-light tl-zoom" title="<?php _e('最大化：全屏阅读'); ?>" aria-label="<?php _e('全屏阅读'); ?>"></button>
        </div>

        <a class="nav-brand" href="<?php $this->options->siteUrl(); ?>" title="<?php $this->options->title(); ?>">
            <span class="brand-logo<?php if ($macglassBrandLogo !== ''): ?> has-logo<?php endif; ?>" aria-hidden="true"<?php if ($macglassBrandLogo !== ''): ?> style="background-image:url('<?php echo macglass_esc($macglassBrandLogo); ?>');"<?php endif; ?>></span>
            <span class="brand-name"><?php $this->options->title(); ?></span>
        </a>

        <nav class="nav-links" id="nav-links" aria-label="<?php _e('站点导航'); ?>">
            <a class="nav-link<?php if ($this->is('index')): ?> is-active<?php endif; ?>" href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>
            <?php while ($macglassPages->next()): ?>
            <a class="nav-link<?php if ($this->is('page', $macglassPages->slug)): ?> is-active<?php endif; ?>" href="<?php $macglassPages->permalink(); ?>" title="<?php $macglassPages->title(); ?>"><?php $macglassPages->title(); ?></a>
            <?php endwhile; ?>

            <!-- 桌面版：分类下拉（前 3 条 + 显示更多 → 归档页） -->
            <div class="tb-dd" id="dd-cats">
                <button type="button" class="nav-link tb-dd-btn" aria-haspopup="true" aria-expanded="false">
                    <?php _e('分类'); ?>
                    <svg class="dd-chevron" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                </button>
                <div class="tb-dd-menu" role="menu" aria-label="<?php _e('分类'); ?>">
                    <?php $macglassTbCatI = 0; ?>
                    <?php while ($macglassTbCats->next()): $macglassTbCatI++; if ($macglassTbCatI > 3) { break; } ?>
                    <a class="dd-item" role="menuitem" href="<?php $macglassTbCats->permalink(); ?>">
                        <span class="dd-name"><?php $macglassTbCats->name(); ?></span>
                        <span class="count"><?php echo intval($macglassTbCats->count); ?></span>
                    </a>
                    <?php endwhile; ?>
                    <?php if ($macglassTbCats->length > 3): ?>
                    <a class="dd-item dd-more" role="menuitem" href="<?php echo $macglassArchivePageUrl; ?><?php if ($macglassHasArchivePage): ?>#all-categories<?php endif; ?>"><?php _e('显示更多'); ?> →</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 桌面版：归档下拉（前 3 个月 + 显示更多 → 归档页） -->
            <div class="tb-dd" id="dd-archives">
                <button type="button" class="nav-link tb-dd-btn" aria-haspopup="true" aria-expanded="false">
                    <?php _e('归档'); ?>
                    <svg class="dd-chevron" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                </button>
                <div class="tb-dd-menu" role="menu" aria-label="<?php _e('归档'); ?>">
                    <?php $macglassTbDateI = 0; ?>
                    <?php while ($macglassTbDates->next()): $macglassTbDateI++; if ($macglassTbDateI > 3) { break; } ?>
                    <a class="dd-item" role="menuitem" href="<?php $macglassTbDates->permalink(); ?>">
                        <span class="dd-name"><?php $macglassTbDates->date('Y 年 n 月'); ?></span>
                    </a>
                    <?php endwhile; ?>
                    <?php if ($macglassTbDates->length > 3): ?>
                    <a class="dd-item dd-more" role="menuitem" href="<?php echo $macglassArchivePageUrl; ?><?php if ($macglassHasArchivePage): ?>#all-archives<?php endif; ?>"><?php _e('显示更多'); ?> →</a>
                    <?php endif; ?>
                </div>
            </div>

            <form class="search-form nav-search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
                <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="s" class="search-input" placeholder="<?php _e('搜索…'); ?>" aria-label="<?php _e('站内搜索'); ?>">
            </form>

            <!-- 手机端：小部件折叠进汉堡菜单（不含站点简介——简介在主栏头部） -->
            <div class="nav-modules">
                <?php macglass_widgets($this, 'nav-modules'); ?>
            </div>
        </nav>

        <div class="nav-actions">
            <!-- 手动日夜切换（太阳 / 月亮图标，点击在浅色与深色间切换） -->
            <button type="button" id="theme-toggle" class="icon-btn" aria-label="<?php _e('切换深色 / 浅色模式'); ?>" title="<?php _e('切换深色 / 浅色模式'); ?>">
                <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            </button>
            <!-- 汉堡菜单（仅 <768px 显示；展开时图标动画切换为 X，点击空白处可关闭） -->
            <button type="button" id="nav-toggle" class="icon-btn nav-toggle" aria-label="<?php _e('打开菜单'); ?>" aria-expanded="false" aria-controls="nav-links">
                <svg class="icon-burger" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                <svg class="icon-x" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
        </div>
    </header>

    <div class="window-body root-body">
        <div class="window-grid">
            <div class="content-area" id="content-area">
            <?php if (!$this->is('post') && !$this->is('page')): ?>
                <!-- 手机版：关于我（About）卡片放在主栏头部（≥1024px 桌面自动隐藏） -->
                <div class="mobile-intro">
                    <?php macglass_widgets($this, 'intro'); ?>
                </div>
            <?php endif; ?>
