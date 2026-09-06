<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* ============================================================
 * 小部件渲染函数 + 个人简介 / 头像统一助手
 * （原 w-intro / w-recent / w-cats / w-archives / w-tags 合并）
 *
 * 【重要】本文件只定义函数，不直接输出任何内容。
 * Typecho 的 $this->need() 每调用一次都是独立的 PHP 函数作用域，
 * 调用方（header.php / sidebar.php / page-archive.php）设置的局部变量
 * 无法传入被包含文件（Typecho 1.1/1.2 还会因 require_once 只执行一次），
 * 因此不能再靠「设置 $macglassWidgetMode + need()」传递渲染模式。
 * 现在由 header.php 顶部载入本文件一次，之后各调用点直接：
 *     macglass_widgets($this, $mode)
 *
 * 模式说明：
 *   intro        —— 关于我（About）卡片（手机主栏头部）
 *   sidebar      —— 关于我 + 最近文章 + 标签云（桌面固定侧边栏）
 *   nav-modules  —— 最近文章 + 分类 + 归档 + 标签云（手机汉堡菜单）
 *   tags         —— 标签云（归档页复用）
 *
 * 每个模式内使用带 @ 别名的独立 Widget 实例，避免不同模式之间
 * 共享 Widget 池实例、游标互相干扰（与 header.php 的 @tb 同理）。
 *
 * 头像 / 简介统一策略：
 *   - macglass_db_option()：直接查数据库 options 表取选项值。
 *     部分环境下 Widget 的 options 魔法属性（$archive->options->xxx）
 *     读取不可靠（配置合并失效 / 主题目录改名），数据库直查最可靠。
 *   - macglass_opt()：安全读取 $archive->options 的字符串属性
 *     （null / 非字符串 / 抛错一律回退为空字符串，不产生警告）。
 *   - macglass_theme_config()：读取主题配置行（options 表
 *     name = "theme:主题目录名"，反序列化数组）。优先当前主题名，
 *     取不到时扫描所有 theme:% 配置行（应对主题目录改名后
 *     配置行仍保存在旧目录名下），一次请求内缓存。
 *   - macglass_profile()：About 卡片与右上角固定简介卡片共用，头像
 *     统一读取主题设置的 profileAvatar 链接；未设置时用名字首字
 *     渐变圆形兜底；昵称 / 介绍留空时依次回退已合并的 options、
 *     站点名称 / 站点描述（options → 数据库直查）、固定文案——
 *     保证任何情况下卡片均有内容。
 *   - macglass_avatar()：单独取头像链接（评论博主头像共用）。
 *   - macglass_esc()：带 ENT_SUBSTITUTE 的安全转义——昵称 / 简介中
 *     若混入非法 UTF-8 字节，会被替换为 � 而不是让整段文字消失
 *     （显式传 ENT_QUOTES 会覆盖默认的 ENT_SUBSTITUTE，需显式补回）。
 * ============================================================ */

/* 安全输出转义：非法 UTF-8 序列替换为 U+FFFD，避免整段文字变空 */
if (!function_exists('macglass_esc')) {
    function macglass_esc($text)
    {
        $flags = defined('ENT_SUBSTITUTE') ? ENT_QUOTES | ENT_SUBSTITUTE : ENT_QUOTES;
        return htmlspecialchars($text, $flags, 'UTF-8');
    }
}

/* 取文本的第一个字符（名字首字头像用） */
if (!function_exists('macglass_initial')) {
    function macglass_initial($text)
    {
        $text = trim(strip_tags(html_entity_decode((string)$text, ENT_QUOTES, 'UTF-8')));
        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8'); /* 剔除非法字节序列 */
        }
        if ($text === '') {
            return '';
        }
        return function_exists('mb_substr')
            ? mb_substr($text, 0, 1, 'UTF-8')
            : (class_exists('Typecho_Common') ? Typecho_Common::subStr($text, 0, 1, 'UTF-8') : substr($text, 0, 1));
    }
}

/* 数据库直查选项值：绕过 Widget options 魔法属性，最可靠 */
if (!function_exists('macglass_db_option')) {
    function macglass_db_option($name, $fallback = '')
    {
        try {
            if (class_exists('Typecho_Db')) {
                $db = Typecho_Db::get();
            } elseif (class_exists('\\Typecho\\Db')) {
                $db = \Typecho\Db::get();
            } else {
                return $fallback;
            }
            $row = $db->fetchRow($db->select()->from('table.options')->where('name = ?', $name));
            if (is_array($row) && isset($row['value']) && is_string($row['value'])) {
                return $row['value'];
            }
        } catch (Exception $e) {
            /* 静默回退 */
        }
        return $fallback;
    }
}

/* 安全读取 $archive->options 的字符串属性（不可用时回退空字符串） */
if (!function_exists('macglass_opt')) {
    function macglass_opt($archive, $name)
    {
        $v = null;
        try {
            $v = @$archive->options->{$name};
        } catch (Exception $e) {
            $v = null;
        }
        return is_string($v) ? trim($v) : '';
    }
}

/* URL 安全校验：只允许 http(s)://、// 或根相对 / 开头（与自定义壁纸图同规则）。
 * 头像 / 品牌 Logo 会进入内联 style（background-image:url(...)）与 favicon，
 * 若允许任意字符可能注入额外 CSS 声明（属性实体解码后 ; 可作为声明分隔符）。 */
if (!function_exists('macglass_safe_url')) {
    function macglass_safe_url($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^(https?:)?//#i', $url) && $url[0] !== '/') {
            return '';
        }
        return $url;
    }
}

/* 主题配置行（反序列化数组）；一次请求内缓存 */
if (!function_exists('macglass_theme_config')) {
    function macglass_theme_config($archive)
    {
        static $macglassThemeConfig = null;
        if (is_array($macglassThemeConfig)) {
            return $macglassThemeConfig;
        }

        $config = array();
        try {
            /* 主题目录名：options → 数据库直查 */
            $theme = macglass_opt($archive, 'theme');
            if ($theme === '') {
                $theme = trim((string)macglass_db_option('theme'));
            }

            /* 配置行：options 魔法属性 → 数据库直查 */
            $raw = '';
            if ($theme !== '') {
                $raw = macglass_opt($archive, 'theme:' . $theme);
                if ($raw === '') {
                    $raw = trim((string)macglass_db_option('theme:' . $theme));
                }
                if ($raw !== '') {
                    $decoded = @unserialize($raw);
                    if (!is_array($decoded)) {
                        $decoded = json_decode($raw, true);
                    }
                    if (is_array($decoded)) {
                        $config = $decoded;
                    }
                }
            }

            /* 兜底：扫描所有主题配置行（应对主题目录改名后配置行仍在旧目录名下） */
            if (empty($config)) {
                $db = null;
                if (class_exists('Typecho_Db')) {
                    $db = Typecho_Db::get();
                } elseif (class_exists('\\Typecho\\Db')) {
                    $db = \Typecho\Db::get();
                }
                if ($db) {
                    $rows = $db->fetchAll($db->select()->from('table.options')->where('name LIKE ?', 'theme:%'));
                    foreach ($rows as $row) {
                        $decoded = null;
                        if (isset($row['value']) && is_string($row['value'])) {
                            $decoded = @unserialize($row['value']);
                            if (!is_array($decoded)) {
                                $decoded = json_decode($row['value'], true);
                            }
                        }
                        if (is_array($decoded)
                            && (isset($decoded['profileName']) || isset($decoded['profileAvatar'])
                                || isset($decoded['profileIntro']))) {
                            $config = $decoded;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            /* 静默回退 */
        }

        $macglassThemeConfig = $config;
        return $config;
    }
}

/* 头像链接：主题配置行 → 已合并的 options → 空字符串（返回值经 URL 校验） */
if (!function_exists('macglass_avatar')) {
    function macglass_avatar($archive)
    {
        $config = macglass_theme_config($archive);
        if (isset($config['profileAvatar']) && is_string($config['profileAvatar'])) {
            $avatar = macglass_safe_url($config['profileAvatar']);
            if ($avatar !== '') {
                return $avatar;
            }
        }
        return macglass_safe_url(macglass_opt($archive, 'profileAvatar'));
    }
}

/* 品牌 Logo（标题栏 / 浏览器标签页图标）：
 * 主题设置 brandLogo → 个人头像 profileAvatar → 空（CSS 渐变占位） */
if (!function_exists('macglass_brand_logo')) {
    function macglass_brand_logo($archive)
    {
        $config = macglass_theme_config($archive);
        if (isset($config['brandLogo']) && is_string($config['brandLogo'])) {
            $logo = macglass_safe_url($config['brandLogo']);
            if ($logo !== '') {
                return $logo;
            }
        }
        $logo = macglass_safe_url(macglass_opt($archive, 'brandLogo'));
        if ($logo !== '') {
            return $logo;
        }
        return macglass_avatar($archive);
    }
}

/* 个人简介数据（头像链接 / 昵称 / 介绍 / 名字首字）：统一读取主题设置 */
if (!function_exists('macglass_profile')) {
    function macglass_profile($archive)
    {
        $config = macglass_theme_config($archive);

        $avatar = '';
        $name   = '';
        $intro  = '';
        if (isset($config['profileAvatar']) && is_string($config['profileAvatar'])) {
            $avatar = macglass_safe_url($config['profileAvatar']);
        }
        if (isset($config['profileName']) && is_string($config['profileName'])) {
            $name = trim($config['profileName']);
        }
        if (isset($config['profileIntro']) && is_string($config['profileIntro'])) {
            $intro = trim($config['profileIntro']);
        }

        /* 回退：已合并的 options 主题设置 */
        if ($avatar === '') {
            $avatar = macglass_safe_url(macglass_opt($archive, 'profileAvatar'));
        }
        if ($name === '') {
            $name = macglass_opt($archive, 'profileName');
        }
        if ($intro === '') {
            $intro = macglass_opt($archive, 'profileIntro');
        }

        /* 回退：站点信息（options → 数据库直查） */
        if ($name === '') {
            $name = macglass_opt($archive, 'title');
        }
        if ($name === '') {
            $name = trim((string)macglass_db_option('title'));
        }
        if ($intro === '') {
            $intro = macglass_opt($archive, 'description');
        }
        if ($intro === '') {
            $intro = trim((string)macglass_db_option('description'));
        }

        /* 终极兜底：保证卡片永不空白（杜绝“只剩一个空头像框”） */
        if ($name === '') {
            $name = _t('博主');
        }
        if ($intro === '') {
            $intro = _t('这个博主很懒，什么都没有留下。');
        }

        return array($avatar, $name, $intro, macglass_initial($name));
    }
}

if (!function_exists('macglass_widgets')) {
    function macglass_widgets($archive, $mode = 'intro')
    {
        $mode = in_array($mode, array('intro', 'sidebar', 'nav-modules', 'tags'), true)
            ? $mode : 'intro';
        $show = array(
            'intro'    => in_array($mode, array('intro', 'sidebar'), true),
            'recent'   => in_array($mode, array('sidebar', 'nav-modules'), true),
            'cats'     => ($mode === 'nav-modules'),
            'archives' => ($mode === 'nav-modules'),
            'tags'     => in_array($mode, array('sidebar', 'nav-modules', 'tags'), true)
        );

        if ($show['intro']):
            list($avatar, $name, $intro, $initial) = macglass_profile($archive);
?>
<!-- ============ 关于我（About）卡片：头像居中置顶 + 昵称 + 简介 ============ -->
<section class="glass-panel side-card about-card">
    <div class="about-avatar">
        <?php if ($avatar !== ''): ?>
        <img src="<?php echo macglass_esc($avatar); ?>" alt="<?php echo macglass_esc($name); ?>">
        <?php else: ?>
        <span aria-hidden="true"><?php echo macglass_esc($initial); ?></span>
        <?php endif; ?>
    </div>
    <strong class="about-name"><?php echo macglass_esc($name); ?></strong>
    <p class="about-intro"><?php echo macglass_esc($intro); ?></p>
</section>
<?php
        endif;

        if ($show['recent']):
?>
<?php
/* 最近文章：桌面端在固定侧边栏，手机端在汉堡菜单 */
$recent = $archive->widget('Widget_Contents_Post_Recent@mg-recent-' . $mode, 'pageSize=5');
if ($recent->have()):
?>
<section class="glass-panel side-card">
    <div class="section-head compact">
        <h2><?php _e('最近文章'); ?></h2>
        <span><?php _e('Recent'); ?></span>
    </div>
    <ul class="widget-list">
        <?php while ($recent->next()): ?>
        <li>
            <a href="<?php $recent->permalink(); ?>" title="<?php $recent->title(); ?>"><?php $recent->title(); ?></a>
            <time><?php $recent->date('m-d'); ?></time>
        </li>
        <?php endwhile; ?>
    </ul>
</section>
<?php
endif;
        endif;

        if ($show['cats']):
?>
<?php
/* 分类：手机端在汉堡菜单（桌面端已集成到标题栏下拉） */
$cats = $archive->widget('Widget_Metas_Category_List@mg-cats-' . $mode);
if ($cats->have()):
?>
<section class="glass-panel side-card">
    <div class="section-head compact">
        <h2><?php _e('分类'); ?></h2>
        <span><?php _e('Categories'); ?></span>
    </div>
    <div class="chip-row sidebar-chip-row">
        <?php while ($cats->next()): ?>
        <a class="chip" href="<?php $cats->permalink(); ?>">
            <?php $cats->name(); ?>
            <span class="count"><?php echo intval($cats->count); ?></span>
        </a>
        <?php endwhile; ?>
    </div>
</section>
<?php
endif;
        endif;

        if ($show['archives']):
?>
<?php
/* 按月归档：手机端在汉堡菜单（桌面端已集成到标题栏下拉） */
$dates = $archive->widget('Widget_Contents_Post_Date@mg-dates-' . $mode, 'type=month&limit=12');
if ($dates->have()):
?>
<section class="glass-panel side-card">
    <div class="section-head compact">
        <h2><?php _e('归档'); ?></h2>
        <span><?php _e('Archive'); ?></span>
    </div>
    <ul class="widget-list">
        <?php while ($dates->next()): ?>
        <li>
            <a href="<?php $dates->permalink(); ?>" title="<?php $dates->date('Y 年 n 月'); ?>"><?php $dates->date('Y 年 n 月'); ?></a>
        </li>
        <?php endwhile; ?>
    </ul>
</section>
<?php
endif;
        endif;

        if ($show['tags']):
?>
<?php
/* 标签云：桌面端在固定侧边栏，手机端在汉堡菜单；归档页也复用 */
$tags = $archive->widget('Widget_Metas_Tag_Cloud@mg-tags-' . $mode, 'sort=count&ignoreZeroCount=1&desc=1&limit=30');
if ($tags->have()):
?>
<section class="glass-panel side-card">
    <div class="section-head compact">
        <h2><?php _e('标签云'); ?></h2>
        <span><?php _e('Tags'); ?></span>
    </div>
    <div class="tag-cloud">
        <?php while ($tags->next()): ?>
        <a class="tag-chip" href="<?php $tags->permalink(); ?>"><?php $tags->name(); ?><span class="count"><?php echo intval($tags->count); ?></span></a>
        <?php endwhile; ?>
    </div>
</section>
<?php
endif;
        endif;
    }
}
