---
name: godomall-existing-patterns
description: 总结 GodoMall 项目中 module/Custom 与 module/Controller 的已验证开发写法。用于修改或新增这些目录下的 PHP 控制器、组件、入口脚本时，优先参考当前项目现有可运行文件，而不是套用通用 PHP 框架习惯。涉及模板与前端静态资源时，遵循文内「前端资源约定」（HTML/CSS/JS 解耦）。
---

# GodoMall 既有写法优先

## 适用场景

当任务涉及以下路径时，优先使用本 Skill：

- `module/Custom/**`
- `module/Controller/**`
- `module/Admin/custom_*.php`

## 关联规则与前置阅读

本 Skill 与以下规则配合使用：

- `.cursor/rules/skill-routing.mdc`
- `.cursor/rules/controller-skill-routing.mdc`
- `.cursor/rules/component-skill-routing.mdc`
- `.cursor/rules/entry-script-skill-routing.mdc`
- `.cursor/rules/db-component-skill-routing.mdc`

典型触发词：GodoMall 二次开发、控制器写法、组件写法、后台 API、前台覆写、`module/Custom`、`module/Controller`。

## 核心原则

1. GodoMall 有独立的类加载、路由、控制器继承和模板数据约定，不能直接套用通用 PHP 框架经验。
2. 当前项目 `module/Custom`、`module/Controller` 下的很多代码已经过实际联调与实验验证，哪怕局部看起来不够“规范”，只要已验证可运行，就必须纳入参考并作为优先依据。
3. 修改现有逻辑时，先模仿相邻、同模块、同类型的旧文件；只有在明确 bug、需求变化或用户要求时，才调整结构。
4. 不要为了“更优雅”擅自改动以下关键点：
   - 控制器基类
   - `parent::index()` 等父类调用顺序
   - `mode` 分发方式
   - `Request` / `App::load()` / `new Component()` 的既有用法
   - JSON 返回结构
   - 已在线上验证的路由与文件命名对应关系

## 子 Skill 指引

当任务范围更明确时，继续优先匹配以下子 Skill：

- `godomall-controller-patterns`：控制器写法
- `godomall-component-patterns`：组件写法
- `godomall-entry-script-patterns`：独立入口脚本写法
- `godomall-db-extension-patterns`：扩展表与数据库写法

## 开发前检查

1. 先阅读项目规则：
   - `.cursor/rules/godomall.mdc`
   - `.cursor/rules/custom-backend.mdc`
2. 再找最近似的既有文件，至少看 1 个控制器和 1 个组件。
3. 优先参考同目录、同业务、同前后台类型的旧实现。
4. 若发现“通用规范”和“当前项目可运行写法”冲突，以当前项目已验证写法为先。

## 决策优先级

当不同规则、Skill、旧文件结论不一致时，按以下顺序决策：

1. 当前项目同目录、同类型、已验证可运行的旧文件
2. `.cursor/rules/godomall.mdc`
3. `.cursor/rules/custom-backend.mdc`
4. 当前 Skill 与对应专用 Skill
5. 通用 PHP / MVC 最佳实践

编码落地时若仍有冲突，再优先：相邻已验证旧文件 → 上述规则 → 通用实践。

## 路径职责

**默认落点**：新增二开逻辑优先放在 **`module/Custom`**，其下以 **`Controller`**（定制控制器）与 **`Component`**（可复用业务与数据层）分工；**`module/Controller`** 专用于与官方同 namespace、路径对齐的**覆写/扩展**。目录名须与仓库一致（**`module/Custom`**，勿写成 `module/custom`）。细则见 `.cursor/rules/custom-backend.mdc`「新文件落点（默认约定）」。

### `module/Controller`

- 用于覆盖或扩展官方控制器。
- 路径与官方控制器保持一致时，通常继承对应 `\Bundle\Controller\...`。
- 适合做最小覆写：先 `parent::index()`，再补充筛选、字段、模板数据。

#### 参考线索

- 当前项目中同目录、同职责、同前后台类型的前台覆写控制器旧文件

### `module/Custom/Controller`

- 用于独立的后台/前台定制控制器。
- 后台通常继承 `\Controller\Admin\Controller`。
- 前台通常继承 `\Controller\Front\Controller`。
- 控制器保持薄层，复杂逻辑下放到 `Custom\Component\*`。

#### 参考线索

- 当前项目中同目录、同职责的后台定制控制器旧文件

### `module/Custom/Component`

- 承载可复用业务逻辑、定制表查询、数据组装。
- 定制表优先放在组件里处理，不在控制器里堆 SQL。
- 官方数据优先通过 `\App::load('\\Component\\...')` 获取，定制表再用 `CustomPDO` 补充。

#### 参考线索

- 当前项目中同业务类型的组件旧文件
- 当前项目中的数据库封装组件旧文件

### `module/Admin/custom_*.php`

- 属于后台入口脚本或定制入口。
- 只做薄层 HTTP 入口：加载 `init.php`、获取请求、分发到控制器或组件。
- 不在入口脚本里堆复杂业务。

## 控制器写法

### 后台控制器

- 继承：`extends \Controller\Admin\Controller`
- 构造函数里先 `parent::__construct()`
- 再初始化对应业务组件
- 后台 POST/AJAX 要保留 CSRF、参数校验、白名单与错误返回结构

#### 参考线索

- 当前项目中已验证可运行的后台定制控制器

### 前台控制器

- 独立前台页通常继承：`extends \Controller\Front\Controller`
- 若是覆写官方页，优先继承对应 `\Bundle\Controller\...`
- `index()` 中如果存在 `mode` / Ajax 分支，要优先分流并 `return`，避免继续渲染 HTML

#### 参考线索

- 当前项目中已验证可运行的前台覆写控制器
- 当前项目中已验证可运行的前台定制控制器

## 组件写法

1. 命名空间使用 `Custom\Component\...`
2. 组件以业务复用为主，不强求继承父类
3. 组件内部可以使用：
   - `Custom\Component\Database\CustomPDO`
   - `\App::load('\\Component\\Goods\\Goods')`
   - `\Logger::channel('userLog')`
4. 组件负责：
   - 定制表读写
   - 数据整合
   - 与官方组件拼装结果
5. 控制器只负责：
   - 取参
   - 校验
   - 分发
   - `setData`
   - 输出 JSON / 页面

#### 参考线索

- 当前项目中已验证可运行的同类业务组件

## 返回与模板数据

### 页面数据

- 使用 `$this->setData('key', $value)` 传给模板
- 对齐同模块已有变量名，不随意重新命名

### JSON 响应

- 优先对齐现有结构：`success`、`message`、`data`
- 错误时保持稳定 `code` 或 HTTP 状态
- 不要因个人习惯改成另一套返回协议

## 前端资源约定

涉及模板（HTML）、样式（CSS）、脚本（JS）时，优先与当前项目既有静态资源组织方式一致，并遵循**结构解耦**：

### 分层原则

- **HTML**：负责结构与语义；避免在模板里堆长段业务逻辑。
- **CSS**：放在独立样式文件或项目约定的样式目录中；避免在 HTML 里写大块 `<style>` 或大量内联 `style=""`（仅极少数布局微调可例外，且须与相邻页面风格一致）。
- **JavaScript**：放在独立 `.js` 文件或项目约定的脚本目录中；**避免**在 HTML 里写大块 `<script>...</script>` 业务逻辑（**内联脚本**仅保留项目已普遍使用的引导/兼容片段时，须与现有页面写法一致）。

### 不要这样做

- 把整页交互、数据处理、DOM 操作都写在模板里的 `<script>` 中。
- 把可复用样式散落在各模板的 `<style>` 或重复的内联样式里。
- 为图省事新建与现有资源目录、命名、打包方式不一致的另一套路径（除非用户明确要求且同步文档）。

### 落地时

- 新增或修改页面时，先找**同模块、同类型**的模板与静态资源文件，沿用其引用方式（`link` / `script` 引入、模板变量注入等）。
- 若官方或现有二开页面仍保留历史内联写法，**增量修改以兼容为先**；新写代码仍优先独立 CSS/JS 文件，便于维护与缓存。

## 注释与命名

1. 注释使用中文。
2. 文件头优先写中文 PHPDoc，说明功能、依赖、入口关系、安全要点。
3. 类名 UpperCamelCase，方法/变量 lowerCamelCase，常量 UPPER_SNAKE_CASE。
4. 路由文件名、控制器类名、模板名的对应关系，优先复用现有写法，不自行发明新映射。

## 数据与扩展原则

1. 不直接改 `module/Bundle/**` 核心代码，优先在 `module/Custom/**`、`module/Controller/**` 增量扩展。
2. 新增字段时，不直接加到官方核心表，优先使用 `custom_*` 扩展表。
3. 字段定义以数据库为准，后端和前端都要对齐现有真实字段。

## 数据库排序规则（Collation）补充

### 场景范围

- 新建 `custom_*` 扩展表
- 扩展表字段要与官方旧表做 `JOIN` / `=` 比较
- 使用 `CustomPDO` 查询定制表并关联 `es_*` 官方表

### 已踩坑经验

如果扩展表只写：

```sql
DEFAULT CHARSET=utf8mb4
```

而没有显式指定：

```sql
COLLATE=utf8mb4_general_ci
```

那么在 MySQL 8 环境中，新表字段可能默认变成：

- `utf8mb4_0900_ai_ci`

但 GodoMall 旧表字段常见仍是：

- `utf8mb4_general_ci`

这会导致跨表字符串比较时报错，例如：

```sql
cg.cateCd = cbgc.goods_cate_cd
```

典型报错：

- `Illegal mix of collations`

前台如果有 `try/catch` 兜底，页面可能不会直接 500，而是表现为：

- 左侧筛选正常
- 卡片标签 / subtitle / 扩展字段取不到值

### 规则

1. 新建扩展表时，除 `CHARSET` 外，优先显式写 `COLLATE`
2. 若扩展字段将与官方旧表字符串字段做 `JOIN` / `WHERE =`，优先与旧表保持一致的 `collation`
3. 不要假设数据库默认排序规则与 GodoMall 旧表一致

### 推荐写法

```sql
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
```

字段级别也可显式指定：

```sql
`goods_cate_cd` VARCHAR(32)
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
```

### 排查顺序

当出现“扩展表里明明有值，但前台 JOIN 后取不到值”时，优先检查：

1. 扩展表字段 `COLLATION`
2. 官方旧表关联字段 `COLLATION`
3. 是否存在 `Illegal mix of collations` 报错

### 一句话规则

GodoMall 新建扩展表时，不要只写 `utf8mb4`；凡是要和旧表做字符串关联的字段，必须优先对齐旧表 `collation`。

## 实施要求

开始写代码前，先回答这 4 个问题：

1. 这是覆写官方控制器，还是新增定制控制器？
2. 最相似的旧文件是哪一个？
3. 这段逻辑应放控制器还是组件？
4. 有没有现成的 `mode`、JSON 结构、`setData` 变量名可以复用？

如果不能明确回答，先继续读相邻旧文件，不要直接新写一套。

## 参考清单

- 当前项目中同目录、同职责、同数据流、已验证可运行的后台定制控制器
- 当前项目中同目录、同职责、已验证可运行的业务组件
- 当前项目中已验证可运行的数据库封装组件
- 当前项目中同目录、同职责、已验证可运行的前台覆写控制器
- 当前项目中同目录、同职责、已验证可运行的前台定制控制器

## 一句话规则

在这个项目里，优先参考已经验证能运行的旧文件写法；不要为了“更规范”而破坏 GodoMall 现有机制。 
