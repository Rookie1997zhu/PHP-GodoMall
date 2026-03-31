---
name: godomall-skin-migration
description: GodoMall 前端主题迁移的已验证写法，适用于将自定义前端代码（HTML/CSS/JS/资源）从一个皮肤目录迁移到另一个皮肤目录。需要跨主题复制 custom 前端文件、补丁注入官方模板、或修正资源路径时使用。
---

# GodoMall 前端主题迁移

## 适用场景

当任务涉及以下操作时，优先使用本 Skill：

- 将 `module/skin/front/主题A/` 下的自定义前端代码迁移到 `module/skin/front/主题B/`
- 跨主题复制 `custom/`、`css/custom/`、`js/custom/`、资源目录
- 向新主题的官方 HTML 模板注入自定义补丁
- 迁移后修正资源引用路径

本 Skill 与以下规则 / Skill 配合使用：

- `.cursor/rules/godomall.mdc`（全局规则）
- `.cursor/skills/godomall-existing-patterns/SKILL.md`（前台 JS 外部化约定）
- `docs/frontend-backend-mapping.md`（前端模板与后端代码对应清单）

## 迁移三步法

### 第一步：直接复制

将无冲突的自定义目录整体复制到目标主题，保持相对路径结构不变：

```
目标主题/
├── custom/                    # 自定义 HTML 页面
│   ├── goods_custom.html
│   └── external_order_entry_required.html
├── css/custom/                # 自定义 CSS
│   ├── goods_custom.css
│   ├── custom_purchase_button.css
│   └── ...
├── js/custom/                 # 自定义 JS（外部化后的业务脚本）
│   ├── goods_view_custom.js
│   ├── cart_custom.js
│   ├── order_end_custom.js
│   └── ...
└── _mplshop/                  # 前端静态资源（图片、字体等）
    └── images/
```

`custom/*.html` 内的相对路径（`../css/custom/`、`../js/custom/`）在目标主题下结构一致，无需修改。

### 第二步：补丁注入官方 HTML

目标主题已有完整的官方 HTML，**不替换整文件**，只在精确锚点插入自定义代码块。

每个注入点需确认：
1. CSS `<link>` 引用
2. 自定义 DOM 块（条件渲染区域、表格列、隐藏字段等）
3. 全局变量声明（内联 `<script>`，仅做模板变量中转）
4. 外部 JS `<script src>` 引用
5. 条件内联脚本（仅当功能函数定义不在外部 JS 中时）

#### goods_view.html 注入清单

| 注入内容 | 锚点位置 |
|----------|----------|
| CSS 引用 `custom_purchase_button.css` | `{ # header }` 之后 |
| `custom_goods_section` DOM（预览区 + 颜色/尺寸选择 + 隐藏字段） | `<div class="btn_choice_box">` 之前 |
| `#customPurchaseSection` 定制入口按钮块 | `btn_choice_box` 内部，购买按钮之后 |
| workGuide 标签页 `<li>` + `#workGuideContainer` 面板 | tab `<ul>` 中 + `detail_cont` 同级 |
| `initCustomizationFeatures()` 调用 | `$(document).ready` 内 `{customReadyScript}` 之前 |
| `var CUSTOM_GOODS_NO` 全局变量 | 文件末尾 `gd_board_goods.js` 之后 |
| `goods_view_custom.js` 引用 | 全局变量之后 |
| 条件内联脚本（6 个定制函数） | `goods_view_custom.js` 引用之后 |

#### cart.html 注入清单

| 注入内容 | 锚点位置 |
|----------|----------|
| CSS 引用 `goods_custom.css` | `{ # header }` 之后 |
| 추가 옵션列（colgroup + thead + tbody 同步） | 할인/적립列之后、내 디자인列之前 |
| `td_customization_image` + `td_customer_pattern` 列 | 합계금额列之前 |
| 합계 td 补 `js-cart-total-cell` class + `data-base-total` | 替换原合计 td |
| `cart_custom.js` 引用 | 主 `</script>` 之后 |
| `{=fbCartScript}` + 추가 옵션价格 IIFE | `cart_custom.js` 之后、`{ # footer }` 之前 |

#### order_end.html 注入清单

| 注入内容 | 锚点位置 |
|----------|----------|
| `var CUSTOM_ORDER_NO` 全局变量 | 文件末尾最后一个 `</script>` 之后 |
| `order_end_custom.js` 引用 | 全局变量之后 |

### 第三步：路径修正 + 补漏

1. **绝对路径替换**：全局搜索复制文件中的 `/data/skin/front/旧主题/`，替换为 `/data/skin/front/新主题/`
2. **逐文件对比**：将源主题和目标主题的已注入页面逐段对比，检查遗漏的 DOM、JS 钩子、条件块
3. **表格列补丁完整性**：凡新增列必须同步 `colgroup`、`thead`、`tbody` 三处

## 路径引用规则（强制）

### CSS 引用

`goods/`、`order/` 等一级子目录下的 HTML 引用 CSS，用 `../`（一级）回到主题根：

```html
<!-- 正确 -->
<link rel="stylesheet" href="../css/custom/custom_purchase_button.css">

<!-- 错误：多了一级，超出主题根目录，CSS 加载 404 -->
<link rel="stylesheet" href="../../css/custom/custom_purchase_button.css">
```

`custom/` 子目录下的 HTML 同理用 `../`：

```html
<link rel="stylesheet" href="../css/custom/goods_custom.css">
```

### JS 引用

统一使用 GodoMall 模板变量 `{=c.PATH_SKIN}` 拼绝对路径，**不用相对路径**：

```html
<script type="text/javascript" src="\{=c.PATH_SKIN}js/custom/goods_view_custom.js"></script>
```

### 图片 / 静态资源

从 `goods/`、`order/` 引用主题根下的资源目录用 `../`：

```html
<img src="../_mplshop/images/icon/icon_custom_entrance.png" alt="...">
```

### 速查表

| 引用位置 | 正确写法 | 常见错误 |
|----------|----------|----------|
| `goods/*.html` → CSS | `../css/custom/xxx.css` | `../../css/custom/xxx.css` |
| `order/*.html` → CSS | `../css/custom/xxx.css` | `../../css/custom/xxx.css` |
| `custom/*.html` → CSS | `../css/custom/xxx.css` | -- |
| 任意 HTML → JS | `\{=c.PATH_SKIN}js/custom/xxx.js` | `../js/custom/xxx.js` |
| `goods/*.html` → 图片 | `../_mplshop/images/xxx.png` | `/data/skin/front/旧主题/_mplshop/...` |

## 已踩坑经验

1. **CSS 路径 `../../` 导致 404**：GodoMall 页面 URL 只有一级深度（如 `/goods/goods_view.php`），`../` 即回到主题根；`../../` 超出主题根，浏览器无法加载 CSS
2. **写死绝对路径未替换**：`goods_custom.html`、`goods_custom.js` 等文件可能包含 `/data/skin/front/mplshop/` 硬编码路径，复制后不替换会导致请求指向旧主题
3. **条件 DOM 与配套脚本脱节**：`custom_goods_section`（`is_customizable` 条件块）的 DOM 和 `initCustomizationFeatures()` 等函数定义必须成对注入，缺一会导致 JS 报错
4. **表格列补丁不完整**：新增购物车列时必须同步 `colgroup`（宽度）、`thead`（表头）、`tbody`（数据行），漏任一处会导致列错位
5. **合计 td 缺少 JS 钩子**：추가 옵션价格 IIFE 依赖 `.js-cart-total-cell` class 和 `data-base-total` 属性来动态更新合计显示，漏加则价格不联动
6. **`_mplshop/` 资源目录是否已复制**：自定义页面和 JS 引用了 `_mplshop/images/` 下的图标、字体等，复制主题时不要遗漏此目录

## 参考文件

- `docs/frontend-backend-mapping.md` -- 前端模板与后端代码完整对应清单
- `.cursor/skills/godomall-existing-patterns/SKILL.md` -- 前台 JS 外部化约定（全局变量注入模式）
- `module/skin/front/mplshop/` -- 迁移源主题（已验证可运行）
- `module/skin/front/designbookR_toolbox/` -- 迁移目标主题（首次迁移实例）

## 一句话规则

迁移主题时，自定义目录整体复制 → 官方 HTML 只在锚点注入补丁 → 最后全局修正路径并逐页对比补漏；CSS 用 `../`，JS 用 `\{=c.PATH_SKIN}`，绝不用 `../../`。
