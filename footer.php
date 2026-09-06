<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
            </div><!-- /.content-area -->
            <?php $this->need('sidebar.php'); ?>
        </div><!-- /.window-grid -->
    </div><!-- /.window-body -->

    <!-- 内嵌状态栏（嵌在窗口底部，与原页脚衔接自然） -->
    <footer class="window-status">
        <p>© <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a>
        <?php if (!empty($this->options->beianMiit)): ?> · <a href="https://beian.miit.gov.cn/" rel="nofollow" target="_blank"><?php echo macglass_esc($this->options->beianMiit); ?></a><?php endif; ?>
        · <?php _e('Power by'); ?> <a href="https://blog.hamhave.top" target="_blank" rel="noopener">Love_Chengke</a> · MacGlass</p>
    </footer>
</section><!-- /.mac-window root-window -->
</main><!-- /.site-frame -->

<!-- ============================================================
     悬浮控制区（右下角）
     - 回顶按钮：一键上滑（滚动正文面板超过 300px 后淡入）
     - 水滴按钮：毛玻璃效果即时开关（状态记忆在 localStorage）
     - 齿轮按钮：弹出设置抽屉（玻璃 / 动画 / 日夜模式集中控制）
     ============================================================ -->
<button type="button" id="backtop" class="fab glass" aria-label="<?php _e('回到顶部'); ?>" title="<?php _e('回到顶部'); ?>">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"></path><path d="M5 12l7-7 7 7"></path></svg>
</button>

<button type="button" id="glass-toggle" class="fab glass" aria-pressed="true" aria-label="<?php _e('开关毛玻璃效果'); ?>" title="<?php _e('毛玻璃：开'); ?>">
    <svg class="icon-drop" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
</button>

<button type="button" id="settings-gear" class="fab glass" aria-expanded="false" aria-controls="settings-drawer" aria-label="<?php _e('打开设置面板'); ?>" title="<?php _e('设置'); ?>">
    <svg class="icon-gear" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
</button>

<!-- 设置抽屉遮罩 -->
<div class="drawer-backdrop" id="drawer-backdrop" hidden></div>

<!-- ============================================================
     设置抽屉面板（右侧滑入，自带标题栏与信号灯）
     ============================================================ -->
<aside class="drawer glass" id="settings-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title" aria-hidden="true">
    <header class="titlebar">
        <span class="titlebar-sheen" aria-hidden="true"></span>
        <div class="traffic-lights">
            <button type="button" class="traffic-light tl-close" id="drawer-close" title="<?php _e('关闭设置面板'); ?>" aria-label="<?php _e('关闭设置面板'); ?>"></button>
        </div>
        <h2 class="titlebar-title" id="drawer-title"><span class="titlebar-text"><?php _e('设置'); ?></span></h2>
        <span class="titlebar-side" aria-hidden="true"></span>
    </header>

    <div class="drawer-body">
        <!-- 外观：日夜模式（浅色 / 深色 / 跟随系统） -->
        <section class="setting-group">
            <h3 class="setting-title"><?php _e('外观'); ?></h3>
            <div class="segmented" id="theme-segmented" role="group" aria-label="<?php _e('颜色模式'); ?>">
                <button type="button" class="seg-btn" data-theme-pref="light"><?php _e('浅色'); ?></button>
                <button type="button" class="seg-btn" data-theme-pref="dark"><?php _e('深色'); ?></button>
                <button type="button" class="seg-btn" data-theme-pref="auto"><?php _e('跟随系统'); ?></button>
            </div>
        </section>

        <!-- 效果：毛玻璃 / 减少动画 -->
        <section class="setting-group">
            <h3 class="setting-title"><?php _e('网页显示效果'); ?></h3>
            <div class="setting-row">
                <div class="setting-info">
                    <span class="setting-name"><?php _e('毛玻璃'); ?></span>
                    <span class="setting-desc"><?php _e('毛玻璃模糊与半透明质感'); ?></span>
                </div>
                <label class="switch">
                    <input type="checkbox" id="sw-glass" checked>
                    <span class="track" aria-hidden="true"></span>
                    <span class="knob" aria-hidden="true"></span>
                    <span class="sr-only"><?php _e('毛玻璃效果开关'); ?></span>
                </label>
            </div>
            <div class="setting-row">
                <div class="setting-info">
                    <span class="setting-name"><?php _e('减少动画'); ?></span>
                    <span class="setting-desc"><?php _e('移除过渡与动画（WCAG）'); ?></span>
                </div>
                <label class="switch">
                    <input type="checkbox" id="sw-motion">
                    <span class="track" aria-hidden="true"></span>
                    <span class="knob" aria-hidden="true"></span>
                    <span class="sr-only"><?php _e('减少动画开关'); ?></span>
                </label>
            </div>
        </section>

        <!-- 关于 -->
        <section class="setting-group">
            <h3 class="setting-title"><?php _e('关于'); ?></h3>
            <p class="setting-desc">
                MacGlass V1.0.0 · <?php _e('由Love_Chengke开发'); ?><br>
            </p>
        </section>
    </div>
</aside>

<script src="<?php $this->options->themeUrl('assets/js/main.js'); ?>"></script>
<?php $this->footer(); ?>
</body>
</html>
