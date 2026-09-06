<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* ============================================================
 * 评论渲染（Typecho 官方机制，已对照核心源码核实）：
 * Widget_Comments_Archive 构造时检测到全局函数 threadedComments()
 * 存在，即用它渲染每一条评论；$comments->listComments() 会自动
 * 包裹 <ol class="comment-list"> 容器，本函数只需输出 <li>。
 * 参数 $options 为 Typecho_Config，可通过 ->replyWord 等取值。
 * ============================================================ */
function threadedComments($comments, $options)
{
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    }
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent';
?>
<li id="<?php $comments->theId(); ?>" class="comment-item<?php echo $commentLevelClass, $commentClass; ?>">
    <div class="comment-body">
        <div class="comment-avatar">
            <?php if ($comments->authorId == $comments->ownerId): ?>
            <?php
            /* 博主：头像统一使用主题设置的个人头像链接（profileAvatar）；
             * 未设置时回退名字首字渐变圆形 */
            $macglassCommentAvatar = macglass_avatar($comments);
            if ($macglassCommentAvatar !== ''):
            ?>
            <img src="<?php echo macglass_esc($macglassCommentAvatar); ?>" alt="<?php echo macglass_esc(strip_tags((string)$comments->author)); ?>">
            <?php else: ?>
            <span class="comment-initial" aria-hidden="true"><?php echo macglass_esc(macglass_initial($comments->author)); ?></span>
            <?php endif; ?>
            <span class="owner-badge"><?php _e('博主'); ?></span>
            <?php else: ?>
            <?php /* 游客：头像显示名字的第一个字 */ ?>
            <span class="comment-initial" aria-hidden="true"><?php echo macglass_esc(macglass_initial($comments->author)); ?></span>
            <?php endif; ?>
        </div>
        <div class="comment-main">
            <div class="comment-head">
                <span class="comment-author"><?php $comments->author(); ?></span>
                <a class="comment-time" href="<?php $comments->permalink(); ?>"><?php $comments->date(); ?></a>
            </div>
            <div class="comment-content">
                <?php $comments->content(); ?>
            </div>
            <div class="comment-foot">
                <?php $comments->reply(); ?>
            </div>
        </div>
    </div>
    <?php if ($comments->children): ?>
    <div class="comment-children">
        <?php $comments->threadedComments(); ?>
    </div>
    <?php endif; ?>
</li>
<?php
}
?>

<?php $this->comments()->to($comments); ?>

<!-- ============ 评论面板（窗口内的玻璃面板模块） ============ -->
<section class="glass-panel comments-panel reveal" id="comments">
    <div class="section-head">
        <h2><?php _e('评论'); ?></h2>
        <span><?php $this->commentsNum(_t('0'), _t('1'), _t('%d')); ?></span>
    </div>

    <?php if ($comments->have()): ?>
    <?php $comments->listComments(); ?>
    <?php $comments->pageNav('‹ 上一页', '下一页 ›'); ?>
    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
    <div id="<?php $this->respondId(); ?>" class="respond">
        <div class="cancel-comment-reply">
            <?php $comments->cancelReply(); ?>
        </div>

        <h3 class="respond-title" id="response"><?php _e('发表评论'); ?><?php if ($comments->have()): ?><span class="respond-note">（<?php _e('点击「回复」可嵌套回复'); ?>）</span><?php endif; ?></h3>

        <form method="post" action="<?php $this->commentUrl(); ?>" id="comment-form" role="form">
            <?php if ($this->user->hasLogin()): ?>
            <p class="respond-logged"><?php _e('登录身份：'); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> · <a href="<?php $this->options->logoutUrl(); ?>" title="Logout"><?php _e('退出'); ?></a></p>
            <?php else: ?>
            <div class="respond-grid">
                <p class="respond-field">
                    <label for="author"><?php _e('称呼'); ?><?php if ($this->options->commentsRequireMail): ?> <span class="sr-only">*</span><?php endif; ?></label>
                    <input type="text" name="author" id="author" class="text" size="20" value="<?php $this->remember('author'); ?>" required />
                </p>
                <p class="respond-field">
                    <label for="mail"><?php _e('邮箱'); ?><?php if ($this->options->commentsRequireMail): ?> <span class="sr-only">*</span><?php endif; ?></label>
                    <input type="email" name="mail" id="mail" class="text" size="20" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?> />
                </p>
                <p class="respond-field">
                    <label for="url"><?php _e('网站'); ?></label>
                    <input type="url" name="url" id="url" class="text" size="20" placeholder="<?php _e('https://'); ?>" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?> />
                </p>
            </div>
            <?php endif; ?>

            <p class="respond-textarea">
                <label for="textarea" class="sr-only"><?php _e('评论内容'); ?></label>
                <textarea rows="6" name="text" id="textarea" required><?php $this->remember('text'); ?></textarea>
            </p>

            <p class="respond-submit">
                <button type="submit" class="btn btn-primary"><?php _e('提交评论'); ?></button>
                <span class="respond-tip"><?php _e('Ctrl + Enter 快速提交'); ?></span>
            </p>
        </form>
    </div>
    <?php else: ?>
    <p class="respond-closed"><?php _e('评论已关闭。'); ?></p>
    <?php endif; ?>
</section>

<script>
/* ============================================================
 * 就地回复（与 Typecho 核心回复按钮协议 TypechoComment.reply 对接）：
 * 点击「回复」→ 把表单移动到该评论下方（评论树内），并隐藏的
 * parent 输入框携带被回复的 coid，提交后即成为该评论的子评论；
 * 「取消回复」把表单移回面板底部。若页面已有第三方定义则跳过。
 * 回退保障：TypechoComment 未定义时，核心 onclick 抛错不影响
 * 锚点链接默认跳转（?replyTo=N#respond 原生流程依然可用）。
 * ============================================================ */
(function () {
    'use strict';
    if (window.TypechoComment) { return; }
    var respondId = '<?php echo $this->respondId(); ?>';
    var holder = null;
    function getRespond() { return document.getElementById(respondId); }
    function setParent(coid) {
        var form = document.getElementById('comment-form');
        if (!form) { return; }
        var input = document.getElementById('comment-parent');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'parent';
            input.id = 'comment-parent';
            form.appendChild(input);
        }
        input.value = coid;
    }
    function removeParent() {
        var input = document.getElementById('comment-parent');
        if (input && input.parentNode) { input.parentNode.removeChild(input); }
    }
    window.TypechoComment = {
        reply: function (cid, coid) {
            var li = document.getElementById(cid);
            var respond = getRespond();
            if (!li || !respond) { return true; } /* 找不到目标则走默认跳转 */
            if (!holder) {
                holder = document.createElement('div');
                respond.parentNode.insertBefore(holder, respond);
            }
            /* 插到该评论与其子评论之间（仍在 li 内，块级堆叠） */
            var children = li.getElementsByClassName('comment-children')[0];
            li.insertBefore(respond, children || null);
            setParent(coid);
            var cancel = document.getElementById('cancel-comment-reply-link');
            if (cancel) { cancel.style.display = ''; }
            try { respond.scrollIntoView({ block: 'start' }); }
            catch (e) { respond.scrollIntoView(true); }
            var ta = respond.getElementsByTagName('textarea')[0];
            if (ta) { ta.focus(); }
            return false;
        },
        cancelReply: function () {
            var respond = getRespond();
            var cancel = document.getElementById('cancel-comment-reply-link');
            if (cancel) { cancel.style.display = 'none'; }
            removeParent();
            if (respond && holder && holder.parentNode) {
                holder.parentNode.insertBefore(respond, holder);
            }
            return false;
        }
    };
})();
</script>
