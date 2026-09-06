<?php
/**
 * MacGlass —— macOS 窗口风格 · 毛玻璃 Typecho 主题
 *
 * functions.php 职责：
 *   1. themeConfig()          —— Typecho 后台「外观设置」面板
 *      （主题主色 / 玻璃模糊强度 / 毛玻璃默认态 / 动画默认态 /
 *        桌面壁纸风格 / 自定义背景图片 / 个人简介：About 头像·昵称·介绍）
 *   2. themeInit()            —— 挂载内容过滤器（正文图片懒加载）
 *   3. macglass_content_ex()  —— 给正文 <img> 注入 loading="lazy"
 *      （旧浏览器不认识该属性会自动忽略，属优雅降级，不影响任何功能）
 *
 * 前端交互（玻璃开关 / 日夜模式 / 减少动画 / 抽屉面板）全部由
 * assets/js/main.js 与 style.css 完成，本文件只负责后台接口与内容输出过滤。
 *
 * 要求 Typecho 1.1+（推荐 1.2 / 1.3）。
 *
 * @package MacGlass
 * @version 1.0.0
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 后台「外观设置」面板
 *
 * @param Typecho_Widget_Helper_Form $form
 */
function themeConfig($form)
{
    /* ---------------- 主题主色 ---------------- */
    $primaryColor = new Typecho_Widget_Helper_Form_Element_Text(
        'primaryColor',
        NULL,
        '#0a84ff',
        _t('主题主色'),
        _t('用于链接、按钮、开关等强调色。请填写十六进制色值（如 #0a84ff），留空或格式非法时使用默认蓝。')
    );
    $primaryColor->input->setAttribute('class', 'w-60');
    $form->addInput($primaryColor);

    /* ---------------- 毛玻璃效果（首次访问默认值） ---------------- */
    $enableGlass = new Typecho_Widget_Helper_Form_Element_Radio(
        'enableGlass',
        array(
            '1' => _t('默认开启（推荐）'),
            '0' => _t('默认关闭')
        ),
        '1',
        _t('毛玻璃效果（默认状态）'),
        _t('仅决定访客第一次打开站点时的初始状态。访客可点击页面右下角水滴按钮实时切换，选择会记忆在其浏览器 localStorage 中；不支持 backdrop-filter 的旧浏览器始终自动降级为纯色背景。')
    );
    $form->addInput($enableGlass);

    /* ---------------- 动画效果（首次访问默认值） ---------------- */
    $enableMotion = new Typecho_Widget_Helper_Form_Element_Radio(
        'enableMotion',
        array(
            '1' => _t('默认开启（推荐）'),
            '0' => _t('默认关闭')
        ),
        '1',
        _t('动画效果（默认状态）'),
        _t('默认尊重访客系统的「减少动态效果」（prefers-reduced-motion，WCAG 无障碍规范）；访客可在右下角设置抽屉中强制开启或关闭，选择同样记忆在 localStorage。')
    );
    $form->addInput($enableMotion);

    /* ---------------- 桌面壁纸风格 ---------------- */
    $wallpaper = new Typecho_Widget_Helper_Form_Element_Radio(
        'wallpaper',
        array(
            'aurora'   => _t('极光（默认）'),
            'monterey' => _t('蒙特雷'),
            'sunset'   => _t('日落')
        ),
        'aurora',
        _t('桌面壁纸风格'),
        _t('窗口背后的渐变壁纸，深浅色模式各有独立配色；旧浏览器自动降级为接近的纯色背景。')
    );
    $form->addInput($wallpaper);

    /* ---------------- 自定义背景图片（优先于预设壁纸） ---------------- */
    /* 文件管理入口：Typecho 1.2+ 才有独立「管理 → 文件」页，用于上传图片；
     * 描述文本支持 HTML，直接给出跳转链接，上传后复制附件链接粘贴即可。 */
    $macglassMediaUrl = '';
    if (class_exists('Typecho_Common') && defined('Typecho_Common::VERSION')
        && version_compare(Typecho_Common::VERSION, '1.2.0', '>=')) {
        $macglassMediaUrl = Typecho_Common::url('admin/manage-medias.php', Helper::options()->rootUrl);
    }
    $macglassUploadTip = ($macglassMediaUrl !== '')
        ? _t('上传图片：<a href="%s" target="_blank" rel="noopener">打开后台「文件管理」</a>，上传后复制附件链接粘贴到本框。', $macglassMediaUrl)
        : _t('图片地址（http(s)://…）。可在后台「管理 → 文件」上传后复制附件链接粘贴。');

    $wallpaperImage = new Typecho_Widget_Helper_Form_Element_Text(
        'wallpaperImage',
        NULL,
        NULL,
        _t('自定义背景图片'),
        _t('可选：一张桌面壁纸图片 URL（http(s)://… 或 /… 根相对地址）。填写后优先于上面的壁纸风格；深色模式下自动叠加暗化层保证可读性。留空则使用预设渐变壁纸。') . '<br>' . $macglassUploadTip
    );
    $wallpaperImage->input->setAttribute('class', 'w-100');
    $form->addInput($wallpaperImage);

    /* ---------------- 玻璃模糊强度 ---------------- */
    $glassBlur = new Typecho_Widget_Helper_Form_Element_Radio(
        'glassBlur',
        array(
            '14' => _t('轻柔（更省电）'),
            '20' => _t('标准（推荐）'),
            '32' => _t('强烈')
        ),
        '20',
        _t('玻璃模糊强度'),
        _t('控制毛玻璃 backdrop-filter 的模糊半径：数值越小磨砂感越轻、滚动越流畅；旧浏览器与关闭玻璃时无影响。')
    );
    $form->addInput($glassBlur);

    /* ---------------- 个人简介（右上角固定卡片 + 侧边栏 About 卡片共用） ---------------- */
    $profileAvatar = new Typecho_Widget_Helper_Form_Element_Text(
        'profileAvatar',
        NULL,
        NULL,
        _t('个人简介 · 头像地址'),
        _t('右上角固定简介卡片与侧边栏 About 卡片共用的头像图片 URL（https://…）。About 卡片中头像居中置顶显示；留空时显示名字首字的渐变圆形。') . '<br>' . $macglassUploadTip
    );
    $profileAvatar->input->setAttribute('class', 'w-100');
    $form->addInput($profileAvatar);

    $profileName = new Typecho_Widget_Helper_Form_Element_Text(
        'profileName',
        NULL,
        NULL,
        _t('个人简介 · 昵称'),
        _t('About 卡片与右上角简介卡片中的昵称，留空时使用站点名称。')
    );
    $profileName->input->setAttribute('class', 'w-60');
    $form->addInput($profileName);

    $profileIntro = new Typecho_Widget_Helper_Form_Element_Textarea(
        'profileIntro',
        NULL,
        NULL,
        _t('个人简介 · 介绍文字'),
        _t('About 卡片与右上角简介卡片中的个人介绍（建议 60 字以内），留空时使用站点描述。右上角卡片不随页面滚动，访客可点击 × 关闭。')
    );
    $profileIntro->input->setAttribute('class', 'w-100');
    $form->addInput($profileIntro);

    /* ---------------- 品牌 Logo（标题栏左上角站点图标 + 浏览器标签页图标） ---------------- */
    $brandLogo = new Typecho_Widget_Helper_Form_Element_Text(
        'brandLogo',
        NULL,
        NULL,
        _t('品牌 Logo 图片'),
        _t('标题栏左上角站点图标与浏览器标签页图标的图片 URL（http(s)://… 或 /… 根相对地址）。填写后优先显示；留空时自动使用「个人简介 · 头像地址」；两者都留空时显示主题默认的蓝色渐变占位。') . '<br>' . $macglassUploadTip
    );
    $brandLogo->input->setAttribute('class', 'w-100');
    $form->addInput($brandLogo);
}

/**
 * 主题初始化钩子（每个请求执行）
 *
 * @param Widget_Archive $archive
 */
function themeInit($archive)
{
    /* 为文章 / 页面正文中的图片注入 loading="lazy"：
     * 现代浏览器原生懒加载；IE11 / Chrome 40 等旧浏览器不认识该属性，
     * 会自动忽略并按普通 <img> 加载 —— 优雅降级，不影响任何功能。 */
    Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = 'macglass_content_ex';
}

/**
 * 正文过滤器：给 <img> 补 loading="lazy"
 *
 * @param string $content
 * @param Widget_Abstract_Contents $widget
 * @return string
 */
function macglass_content_ex($content, $widget)
{
    return preg_replace_callback('/<img([^>]*)>/i', 'macglass_img_lazy', $content);
}

/**
 * 单个 <img> 标签处理：已含 loading 属性则原样返回
 *
 * @param array $matches
 * @return string
 */
function macglass_img_lazy($matches)
{
    if (false !== stripos($matches[1], 'loading=')) {
        return $matches[0];
    }
    return '<img' . $matches[1] . ' loading="lazy">';
}

/**
 * 后台文章 / 页面自定义字段（占位，便于日后扩展）
 *
 * @param Typecho_Widget_Helper_Layout $layout
 */
function themeFields($layout) {}
