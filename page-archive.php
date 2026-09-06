<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* ============================================================
 * 归档页模板（页面创建时选择模板「archive」即可使用）
 * 标题栏「分类 / 归档」下拉的「显示更多」跳转目标：
 *   #all-categories 全部分类 / #all-archives 全部按月归档
 * ============================================================ */
$this->need('header.php');

$this->widget('Widget_Metas_Category_List@page')->to($macglassPageCats);
$this->widget('Widget_Contents_Post_Date@page', 'type=month')->to($macglassPageDates);
?>

<?php if ($macglassPageCats->have()): ?>
<!-- ============ 全部分类 ============ -->
<section class="glass-panel reveal" id="all-categories">
    <div class="section-head">
        <h2><?php _e('全部分类'); ?></h2>
        <span><?php _e('Categories'); ?></span>
    </div>
    <div class="chip-row">
        <?php while ($macglassPageCats->next()): ?>
        <a class="chip" href="<?php $macglassPageCats->permalink(); ?>">
            <?php $macglassPageCats->name(); ?>
            <span class="count"><?php echo intval($macglassPageCats->count); ?></span>
        </a>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($macglassPageDates->have()): ?>
<!-- ============ 全部按月归档 ============ -->
<section class="glass-panel reveal" id="all-archives">
    <div class="section-head">
        <h2><?php _e('按月归档'); ?></h2>
        <span><?php _e('Archive'); ?></span>
    </div>
    <ul class="archive-list">
        <?php while ($macglassPageDates->next()): ?>
        <li class="archive-item">
            <time class="archive-date"><?php $macglassPageDates->date('Y-m'); ?></time>
            <a class="archive-title" href="<?php $macglassPageDates->permalink(); ?>"><?php $macglassPageDates->date('Y 年 n 月'); ?></a>
        </li>
        <?php endwhile; ?>
    </ul>
</section>
<?php endif; ?>

<!-- ============ 标签云（复用 widgets.php 的 macglass_widgets 渲染函数） ============ -->
<?php macglass_widgets($this, 'tags'); ?>

<?php $this->need('footer.php'); ?>
