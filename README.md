# MacGlass —— macOS 毛玻璃 Typecho 主题

高度模仿 macOS 窗口管理风格的 Typecho 博客主题：**全站只有一扇 Mac 窗口**
（`position: fixed` 四边等距铺满视口，底部与顶部始终对称），原顶部栏的全部功能
（导航 / 搜索 / 日夜切换 / 汉堡菜单）都集成在窗口标题栏上；正文面板内滚动、
侧边栏固定、状态栏内嵌窗口底部，并整合毛玻璃（Glassmorphism）特效与可交互
控制面板。

> 要求 Typecho 1.1+（推荐 1.2 / 1.3）。所有后台设置在 `header.php` 中做安全
> 校验（主色正则白名单、URL 协议白名单、枚举白名单），非法输入自动回退默认值，
> 防止 CSS / XSS 注入。

## 页面结构

```
唯一 Mac 窗口（.mac-window，fixed 定位：手机 8px / 平板以上 20px，四边等距）
├── 标题栏（.titlebar.toolbar，磨砂玻璃 + 扫光动画，集成原顶部栏全部功能）
│   ├── 红/黄/绿信号灯（红=返回首页 · 黄=回到顶部 · 绿=全屏阅读）
│   ├── 品牌（logo + 站点名，点击回首页）
│   ├── 导航链接 + 【分类▾】【归档▾】下拉（仅桌面端 ≥768px）+ 站内搜索
│   │     └── 下拉只列前 3 条，超过 3 条显示「显示更多 →」跳转归档页
│   │         （page-archive.php 模板，锚点 #all-categories / #all-archives）
│   └── 日夜切换按钮 + 汉堡菜单按钮（<768px 显示）
│         └── 手机端：汉堡下拉面板 = 导航 + 搜索 + 最近文章/分类/归档/标签云
├── 窗口内容区（.window-grid）
│   ├── 正文面板（.content-area，唯一上下滚动区域）
│   │     └── 手机版（<1024px）：关于我（About）卡片位于主栏头部
│   └── 固定侧边栏（.sidebar，仅 ≥1024px 显示）
│         └── 关于我（About）卡片 / 最近文章 / 标签云
│             （分类与归档已集成到标题栏下拉，不再重复）
└── 内嵌状态栏（.window-status，嵌在窗口底部：© 版权 / 备案 / Power by）

窗口之外：
├── 右上角固定个人小简介（仅 ≥1024px 显示，不随滚动，可 × 关闭并记忆）
└── 右下角悬浮按钮（一键回顶 / 水滴毛玻璃开关 / 齿轮设置抽屉）
```

## 功能特性

### 后台设置（`functions.php` → `themeConfig()`）

| 设置项 | 选项键 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 主题主色 | `primaryColor` | `#0a84ff` | 链接 / 按钮 / 开关等强调色；只接受 `#RGB/#RGBA/#RRGGBB/#RRGGBBAA`，非法回退默认蓝 |
| 毛玻璃效果（默认状态） | `enableGlass` | 开启 | 只决定访客首次访问时的初始状态；访客可用右下角水滴按钮实时切换并记忆 |
| 动画效果（默认状态） | `enableMotion` | 开启 | 默认尊重系统「减少动态效果」（`prefers-reduced-motion`）；可在设置抽屉中强制开关 |
| 桌面壁纸风格 | `wallpaper` | 极光（aurora） | `aurora` / `monterey` / `sunset` 三种，深浅色模式各有独立配色 |
| 自定义背景图片 | `wallpaperImage` | 空 | 优先于预设壁纸；深色模式自动叠加暗化层保证可读性 |
| 玻璃模糊强度 | `glassBlur` | 标准 20px | 14px 轻柔（更省电）/ 20px 标准 / 32px 强烈 |
| 个人简介 · 头像 | `profileAvatar` | 空 | About 卡片与右上角简介卡片共用；留空显示名字首字渐变圆形 |
| 个人简介 · 昵称 | `profileName` | 空 | 留空回退站点名称 |
| 个人简介 · 介绍文字 | `profileIntro` | 空 | 建议 60 字以内；留空回退站点描述 |
| 品牌 Logo 图片 | `brandLogo` | 空 | 标题栏左上角图标 + 浏览器标签页图标；留空回退个人头像，再留空显示蓝色渐变占位 |

> `themeInit()` 同时为文章 / 页面正文中的 `<img>` 注入 `loading="lazy"`；
> 旧浏览器不认识该属性会自动忽略，属优雅降级。`themeFields()` 为占位接口。

### 窗口与标题栏（`header.php`）

- 唯一 Mac 窗口四边等距（手机 8px / 平板以上 20px），底部与顶部始终对称，
  桌面大屏水平居中（`max-width: 1296px`），不依赖 `vh/dvh` 计算。
- 标题栏集成分页导航（首页 + 独立页面）、分类 / 归档下拉（前 3 条 + 显示更多）、
  站内搜索、日夜切换与汉堡菜单；下拉点击外部或 ESC 关闭。
- 后台设置的主色与模糊强度以 CSS 变量（`--primary` / `--glass-blur`）注入；
  `style.css` 的 `:root` 默认值为兜底。
- 首屏引导脚本在页面绘制前同步执行，按 localStorage 恢复日夜模式 / 玻璃状态 /
  动画偏好 / 简介关闭状态，避免深色模式闪烁；纯 ES5，localStorage 不可用静默降级。

### 小部件与个人简介（`widgets.php`）

`macglass_widgets($archive, $mode)` 单入口四模式渲染：

| 模式 | 使用位置 | 内容 |
| --- | --- | --- |
| `intro` | 手机主栏头部（`header.php`） | 关于我（About）卡片 |
| `sidebar` | 桌面固定侧边栏（`sidebar.php`） | About 卡片 + 最近文章 5 篇 + 标签云 30 个 |
| `nav-modules` | 手机汉堡菜单（`header.php`） | 最近文章 + 分类 + 按月归档（12 条）+ 标签云 |
| `tags` | 归档页模板（`page-archive.php`） | 标签云 |

- 每个模式使用带 `@` 别名的独立 Widget 实例，互不干扰。
- 头像 / 昵称 / 介绍通过「主题配置行 → 已合并 options → 站点名称 / 描述 →
  固定文案」逐级回退，保证卡片永不空白；昵称 / 简介中的非法 UTF-8 字节
  被替换为 � 而非整段消失（`macglass_esc()` 使用 `ENT_SUBSTITUTE`）。
- 配置读取做了「数据库直查 + 扫描全部 `theme:%` 配置行」的双重兜底，
  主题目录改名后设置依然生效；URL 一律过协议白名单校验。

### 内容模板

| 文件 | 功能（来自文件注释） |
| --- | --- |
| `index.php` | 首页文章卡片流：整卡可点（分类以纯文本展示，避免 `<a>` 嵌套切断外层链接），标题 / 摘要 110 字 / 评论数 / 分页 / 空状态 |
| `archive.php` | 归档面板：分类 / 标签 / 搜索 / 日期 / 作者列表（日期 + 标题 + 分类 · 评论数）+ 分页 |
| `search.php` | 搜索结果页，直接复用 `archive.php`（`archiveTitle` 自动输出「搜索：%s」） |
| `page.php` | 独立页面面板：标题 + 正文；允许评论时加载 `comments.php` |
| `page-archive.php` | 归档页模板（页面模板选「archive」）：`#all-categories` 全部分类、`#all-archives` 按月归档、标签云 |
| `post.php` | 单篇文章面板：作者 · 时间 · 分类 · 评论链接、正文、标签、上一篇 / 下一篇 + 评论 |
| `404.php` | 404 面板：错误码 + 提示 + 返回首页按钮 |
| `comments.php` | 评论面板：官方 `threadedComments` 机制；博主评论头像使用主题头像，游客显示名字首字；支持就地回复 |

### 评论系统（`comments.php`）

- 使用 Typecho 官方评论机制（`threadedComments()` / `listComments()` 自动包裹
  `<ol class="comment-list">`），支持嵌套子评论。
- 博主（`authorId == ownerId`）头像统一使用主题设置的 `profileAvatar`，
  未设置时回退名字首字渐变圆形并带「博主」徽标；游客评论头像显示名字首字。
- **就地回复**：点击「回复」→ 表单移动到该评论下方（评论树内），隐藏的
  `parent` 输入框携带被回复的 `coid`，提交后即成为子评论；「取消回复」把表单
  移回面板底部。无 JS 时自动回退 Typecho 原生 `?replyTo=N#respond` 跳转流程。
- 评论框支持 `Ctrl / Cmd + Enter` 快速提交。

## 安装

1. 将主题目录（`MacGlass`）整体复制到 Typecho 的 `usr/themes/` 下；
2. 后台「控制台 → 外观」启用 **MacGlass**；
3. 「设置外观」中按需配置主色、玻璃模糊强度、毛玻璃 / 动画默认状态、壁纸风格、
   自定义背景图片、个人简介与品牌 Logo；
   Typecho 1.2+ 可直接点击选项描述里的「打开后台文件管理」上传图片后粘贴链接；
4. **（推荐）新建一个独立页面，模板选择「archive」**——标题栏「分类 / 归档」
   下拉的「显示更多」会自动跳到这个归档页的对应锚点
   （未创建时回退到站点首页）。

## 访客本地存储（localStorage）

| 键 | 取值 | 说明 |
| --- | --- | --- |
| `macglass_theme` | `light` / `dark` / `auto` | 日夜模式（auto 跟随系统 `prefers-color-scheme`） |
| `macglass_glass` | `on` / `off` | 毛玻璃开关（对应 `html.no-glass`） |
| `macglass_motion` | `on` / `off` / `auto` | 减少动画（auto 尊重系统 `prefers-reduced-motion`） |
| `macglass_profile` | `hidden` | 右上角个人简介关闭后保持隐藏 |

localStorage 不可用时（隐私模式 / 旧浏览器）自动降级为内存变量，功能不受影响。

## 目录结构

```
MacGlass/
├── index.php          # 首页：文章卡片流（整卡可点）+ 分页 + 空状态
├── archive.php        # 归档 / 分类 / 标签 / 搜索 / 日期 / 作者 列表面板
├── search.php         # 搜索页（复用 archive.php）
├── page.php           # 独立页面面板
├── page-archive.php   # 归档页模板（全部分类 / 按月归档 / 标签云，锚点跳转）
├── post.php           # 单篇文章面板 + 上一篇 / 下一篇 + 评论
├── comments.php       # 评论面板（官方 threadedComments 机制 + 就地回复）
├── 404.php            # 404 面板
├── header.php         # 安全校验、标题栏工具栏（含下拉）、固定简介、窗口骨架、引导脚本
├── footer.php         # 内嵌状态栏、悬浮按钮（回顶/玻璃/设置）、设置抽屉
├── sidebar.php        # 桌面固定侧边栏（About / 最近文章 / 标签云）
├── widgets.php        # 小部件渲染函数 macglass_widgets()（intro/sidebar/nav-modules/tags 四模式）+ 头像/简介助手
├── functions.php      # 后台设置接口（themeConfig）+ 正文图片懒加载过滤器（themeInit）
├── style.css          # 全部样式（19 个分节，含玻璃控制与降级 hack，注释见文件头目录）
└── assets/js/main.js  # 交互逻辑（纯 ES5 + IIFE，兼容 IE10+ / Chrome 40+）
```

## 交互说明

- **信号灯**：红 = 返回首页（真实 `<a>` 链接，无需 JS）；黄 = 回到顶部；
  绿 = 全屏阅读模式（隐藏侧边栏 / 简介 / 悬浮按钮并尝试原生全屏，
  按 ESC 退出全屏时同步退出阅读模式）。信号灯视觉 11px，触控热区扩大到 44×44px。
- **标题栏下拉（桌面版）**：分类 / 归档各显示前 3 条，超过 3 条出现
  「显示更多 →」并跳转归档页对应锚点；点击外部或 ESC 关闭。
- **正文面板**：`body` 本身不滚动（`overflow: hidden`），滚动只发生在正文面板内；
  侧边栏小部件固定不动。
- **手机端**：汉堡菜单滑出（图标动画切换为 X），条目错峰淡入；点击链接 /
  点击空白处 / ESC 自动收起；视口拉宽到 ≥768px 时自动收起避免状态残留。
  About 卡片显示在主栏头部而非菜单中。
- **右下角按钮**：① 一键回顶（正文滚动超过 300px 后淡入）② 水滴按钮毛玻璃开关
  （点击泛起扩散涟漪）③ 齿轮按钮弹出设置抽屉（浅色 / 深色 / 跟随系统分段选择 +
  毛玻璃 / 减少动画开关，右侧滑入，自带标题栏与信号灯）。
- **滚动浮现**：`.reveal` 元素进入视口时淡入上浮（IntersectionObserver，
  浮现后取消观察）；旧浏览器 / 开启减少动画时直接显示；CSS 以 `html.js-anim`
  门控隐藏态，**无 JS 时内容始终可见**。
- **玻璃质感**：面板带磨砂噪声颗粒、顶部镜面高光、斜向折射光带与更深投影；
  壁纸上的极光光斑缓慢漂移，透过窗口玻璃形成流动柔光；标题栏与卡片有扫光动画。

## 无障碍与低版本降级

- **减少动画（WCAG）**：`html.no-motion` 移除全部过渡与动画；`auto` 时跟随系统
  `prefers-reduced-motion`，系统偏好变化时实时同步。
- 无 `@supports`（IE11）→ 跳过全部增强块，纯色背景 + flex 布局 + 字面量配色；
- 无 CSS 变量（IE11 / Chrome 40）→ 每条 `var()` 前有字面量兜底，
  深色模式另有字面量配色组（第 18 节）；
- 无 `backdrop-filter` → 玻璃变量保持纯色，文字清晰可读；
- 无 ES6 → `main.js` 全程 ES5（IIFE、`var`、无箭头函数 / 模板字符串）；
- 壁纸三级降级：纯色 → `-ms-` 渐变 → 标准渐变；
- 评论无 JS 时回退 Typecho 原生 `?replyTo=N#respond` 跳转流程；
- 正文图片懒加载属性对旧浏览器无副作用（自动忽略）。

## 性能说明

- **窗口内面板不重复模糊**：`backdrop-filter` 只保留在背景是「未模糊内容」的表面
  （窗口、抽屉、下拉 / 移动菜单、右上角简介、悬浮按钮）。窗口内的玻璃面板 / 卡片
  背后已经是模糊过的壁纸，二次模糊没有视觉增益，却会在滚动时逐帧重采样。
- **空闲自动降耗**：关闭毛玻璃或使用自定义背景图片时，动态极光层直接隐藏；
  页面切到后台（`visibilitychange`）时挂 `html.page-hidden`，暂停极光漂移动画。
- **滚动节流**：回顶按钮的 `scroll` 监听用 `requestAnimationFrame` 节流；
  滚动浮现用 IntersectionObserver 并在浮现后取消观察。
- **图片懒加载**：正文图片注入原生 `loading="lazy"`。
- 模糊半径默认 20px，可在「设置外观 → 玻璃模糊强度」按设备调成
  14px（更流畅省电）或 32px（更磨砂）。

## 图标与许可

内联 SVG 图标基于 [Feather Icons](https://feathericons.com/)（MIT License）路径数据。
状态栏署名 `Power by Love_Chengke`（https://blog.hamhave.top）。主题代码可自由
使用与修改，欢迎在 Typecho 社区分发。
