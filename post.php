<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<!-- ============ 文章面板（单篇） ============ -->
<article class="glass-panel post-panel post-single reveal">
    <h1 class="post-title"><?php $this->title(); ?></h1>

    <div class="post-meta">
        <span><?php $this->author(); ?></span>
        <span class="meta-dot">·</span>
        <time datetime="<?php $this->date('c'); ?>"><?php $this->date(); ?></time>
        <span class="meta-dot">·</span>
        <span><?php $this->category(', '); ?></span>
        <span class="meta-dot">·</span>
        <a href="#comments"><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a>
    </div>

    <div class="post-content">
        <?php $this->content(); ?>
    </div>

    <div class="post-tags">
        <?php $this->tags(' ', true, _t('暂无标签')); ?>
    </div>

    <nav class="post-near">
        <div class="near-item"><?php $this->thePrev('<span class="near-label">' . _t('上一篇') . '</span>%s', _t('已经是最新文章')); ?></div>
        <div class="near-item near-next"><?php $this->theNext('<span class="near-label">' . _t('下一篇') . '</span>%s', _t('已经是最后一篇')); ?></div>
    </nav>
</article>

<?php $this->need('comments.php'); ?>

<?php $this->need('footer.php'); ?>
