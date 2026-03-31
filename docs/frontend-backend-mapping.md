# 前端模板与后端代码对应清单

> `module/skin/front/mplshop/custom` 为新开发页面；其余为官方自带页面。

---

## A 类 · 官方页面上的自定义代码

### `goods/goods_view.html`

| 类型 | 文件 | 说明 |
|------|------|------|
| JS | `js/custom/goods_view_custom.js` | 작업 가이드加载、定制状态检查、`goToCustomization()` 跳转 |
| JS 注入变量 | `var CUSTOM_GOODS_NO = '{=goodsView.goodsNo}';` | 内联声明，供上方 JS 文件读取 |
| 后端入口 | `module/Admin/custom_color.php` | Ajax GET 入口，转发至下方 Controller |
| 后端 Controller | `module/Custom/Controller/Admin/Custom/CustomColorController.php` | 返回 `is_customizable` / `custom_width` / `custom_height` |
| 后端 Component | `module/Custom/Component/CustomColor.php` | 从数据库读取商品定制状态 |

---

### `order/order_end.html`

| 类型 | 文件 | 说明 |
|------|------|------|
| JS | `js/custom/order_end_custom.js` | 读取 localStorage 定制图片 → 过滤 Base64 → Ajax POST 保存 → 清理 |
| JS 注入变量 | `var CUSTOM_ORDER_NO = '{orderInfo.orderNo}';` | 内联声明，供上方 JS 文件读取 |
| 后端 Controller | `module/Controller/Front/Custom/GoodsCustomController.php` | 接收 `mode=orderImage&action=save`，持久化定制数据 |
| 后端 Component | `module/Custom/Component/CustomOrderExtension.php` | 写入 `custom_order_extension` 表 |

---

### `order/cart.html`

| 类型 | 文件 | 说明 |
|------|------|------|
| JS | `js/custom/cart_custom.js` | 定制缩略图 Ajax 加载、图片放大预览弹窗、`removeCustomDesignCacheForCartSnos` 缓存清理 |
| 后端 Controller | `module/Controller/Front/Order/CustomCartController.php` | 覆写官方 CartController（SSR），追加定制图片字段 |
| 后端 Component | `module/Custom/Component/CustomCartExtension.php` | 从 `custom_cart_extension` 表读取定制数据 |

> **注**：`gd_cart_process` / `gd_order_all` 等官方函数因内嵌 `{=...}` 模板变量无法抽离，仍保留在 HTML 中；`cart_custom.js` 中声明的 `removeCustomDesignCacheForCartSnos` 全局可被这些官方函数调用。

---

## B 类 · 自定义新页面（`mplshop/custom/`）专用代码

### `custom/goods_custom.html`

| 类型 | 文件 | 说明 |
|------|------|------|
| JS | `js/custom/goods_custom_for_naver.js` | Naver 외부 주문 模式专用逻辑 |
| 后端 Controller | `module/Controller/Front/Custom/GoodsCustomController.php` | 主渲染控制器，SSR 注入所有定制数据 |
| 后端入口脚本 | `module/Custom/Controller/Front/add_to_cart.php` | 加入购物车 POST 入口（Ajax API） |
| 后端 Component | `module/Custom/Component/CustomGoods.php` | 商品定制数据组件 |
| 后端 Component | `module/Custom/Component/CustomColor.php` | 颜色选项数据 |
| 后端 Component | `module/Custom/Component/CustomPattern.php` | 图案数据 |
| 后端 Component | `module/Custom/Component/CustomSize.php` | 尺寸数据 |
| 后端 Component | `module/Custom/Component/CustomExtraImage.php` | 额外图片数据 |
| 后端 Component | `module/Custom/Component/CustomExtraOption.php` | 额外选项数据 |
| 后端 Controller | `module/Controller/Front/Custom/CustomGoodsMailController.php` | 外部订单邮件发送 API |

---

### `custom/external_order_entry_required.html`

| 类型 | 文件 | 说明 |
|------|------|------|
| 后端 Controller | `module/Controller/Front/Custom/GoodsCustomController.php` | 非 Naver 来源时由同一控制器渲染此引导页 |

---

## C 类 · 纯后端辅助（无直接前台模板）

| 文件 | 用途 |
|------|------|
| `module/Custom/Component/Database/CustomPDO.php` | 所有自定义 DB 操作的 PDO 封装 |
| `module/Custom/Component/Database/CustomDBTableField.php` | 扩展表字段定义辅助 |
| `module/Custom/Component/Storage/CustomStorage.php` | 定制文件存储（图片上传等） |
| `module/Custom/Controller/Front/CustomErrorLogController.php` | 前台 JS 错误日志接收接口（纯 API） |
