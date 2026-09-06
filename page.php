<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<!-- ============ 独立页面面板 ============ -->
<article class="glass-panel post-panel post-single reveal">
    <h1 class="post-title"><?php $this->title(); ?></h1>

    <div class="post-content">
        <?php $this->content(); ?>
    </div>
</article>

<?php if ($this->allow('comment')): ?>
    <?php $this->need('comments.php'); ?>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
