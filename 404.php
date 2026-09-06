<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<!-- ============ 404 面板 ============ -->
<section class="glass-panel reveal">
    <div class="empty-state">
        <p class="error-code">404</p>
        <p><?php _e('你要找的页面不存在，或已经被移除，去看看其他文章吧!'); ?></p>
        <p>
            <a class="btn btn-primary" href="<?php $this->options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
        </p>
    </div>
</section>

<?php $this->need('footer.php'); ?>
