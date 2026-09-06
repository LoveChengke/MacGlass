<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* 固定侧边栏（桌面端 ≥1024px；手机端小部件折叠进标题栏的汉堡菜单）。
 * 分类 / 归档已集成到标题栏下拉菜单，侧边栏保留：
 * 关于我（About）卡片 / 最近文章 / 标签云。 */
?>
<aside class="sidebar" id="sidebar">
    <?php macglass_widgets($this, 'sidebar'); ?>
</aside>
