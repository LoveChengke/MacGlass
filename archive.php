<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<!-- ============ 归档面板（分类 / 标签 / 搜索 / 日期 / 作者） ============ -->
<section class="glass-panel archive-panel reveal">
    <div class="section-head">
        <h2><?php $this->archiveTitle(array(
            'category' => _t('分类：%s'),
            'search'   => _t('搜索：%s'),
            'tag'      => _t('标签：%s'),
            'author'   => _t('作者：%s'),
            'date'     => _t('归档：%s')
        ), '', ''); ?></h2>
        <span><?php _e('Archive'); ?></span>
    </div>

    <?php if ($this->have()): ?>
    <ul class="archive-list">
        <?php while ($this->next()): ?>
        <li class="archive-item reveal">
            <time class="archive-date" datetime="<?php $this->date('c'); ?>"><?php $this->date('Y-m-d'); ?></time>
            <a class="archive-title" href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
            <span class="archive-meta"><?php $this->category(', ', true, _t('未分类')); ?> · <?php $this->commentsNum(_t('%d 条评论'), _t('1 条评论'), _t('%d 条评论')); ?></span>
        </li>
        <?php endwhile; ?>
    </ul>

    <nav class="pagination-wrap" aria-label="<?php _e('分页导航'); ?>">
        <?php $this->pageNav('‹ 上一页', '下一页 ›', 3, '…'); ?>
    </nav>
    <?php else: ?>
    <p class="empty-state"><?php _e('没有找到相关内容，换个关键词试试？'); ?></p>
    <?php endif; ?>
</section>

<?php $this->need('footer.php'); ?>
