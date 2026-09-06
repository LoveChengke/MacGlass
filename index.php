<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<?php if ($this->have()): ?>
<!-- ============ 文章卡片流（整卡可点，标题即入口，无需“阅读全文”按钮） ============ -->
<?php while ($this->next()): ?>
<article class="post-card lift reveal">
    <a class="post-card-link" href="<?php $this->permalink(); ?>" title="<?php echo macglass_esc($this->title); ?>">
        <div class="meta-row">
            <!-- 注意：卡片整体已是 <a> 链接，内部禁止再嵌套 <a>（HTML 规范不允许
                 嵌套超链接，浏览器会切断外层链接导致“文字与框分开”）。
                 因此这里的分类以纯文本展示（$link = false），不做超链接。 -->
            <span class="meta-pill"><?php $this->category(', ', false, _t('未分类')); ?></span>
            <span class="meta-time"><time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y-m-d'); ?></time></span>
        </div>
        <h2 class="post-card-title"><?php $this->title(); ?></h2>
        <p class="post-card-excerpt"><?php $this->excerpt(110, _t('…')); ?></p>
        <div class="post-card-foot">
            <span><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></span>
            <span class="card-arrow" aria-hidden="true">→</span>
        </div>
    </a>
</article>
<?php endwhile; ?>

<nav class="pagination-wrap" aria-label="<?php _e('分页导航'); ?>">
    <?php $this->pageNav('‹ 上一页', '下一页 ›', 3, '…'); ?>
</nav>
<?php else: ?>
<!-- ============ 空状态 ============ -->
<section class="glass-panel">
    <div class="empty-state">
        <p><?php _e('这里空空如也，还没有发布任何文章。'); ?></p>
        <p><a class="btn btn-primary" href="<?php $this->options->siteUrl(); ?>"><?php _e('刷新一下'); ?></a></p>
    </div>
</section>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
