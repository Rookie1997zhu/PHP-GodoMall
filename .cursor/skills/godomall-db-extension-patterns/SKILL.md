---
name: godomall-db-extension-patterns
description: 总结 GodoMall 项目中二次开发数据库与扩展表的既有写法，适用于 custom_ 表、扩展字段、定制表查询、数据库封装与事务使用。需要动数据库相关逻辑时，优先参考仓库已验证可运行的旧实现和项目规则。
---

# GodoMall 数据库扩展写法

## 适用场景

当任务涉及以下内容时，优先使用本 Skill：

- `custom_*` 表
- 扩展字段
- 定制表查询
- `module/Custom/Component/Database/**`
- 数据库配置与连接封装
- 多表/多步写库与事务（`beginTransaction` / `commit` / `rollback`）

本 Skill 通常由以下规则触发：

- `.cursor/rules/db-component-skill-routing.mdc`
- `.cursor/rules/skill-routing.mdc`

开始分析前，先读取：

- `.cursor/skills/godomall-existing-patterns/SKILL.md`

## 核心原则

1. 不直接修改官方核心表和 `module/Bundle/**` 核心逻辑。
2. 新字段、新能力优先通过 `custom_*` 扩展表承载。
3. GodoMall 当前数据库访问方式是按项目现状跑通的，哪怕局部不够“优雅”，只要稳定可运行，就必须纳入参考。
4. 字段定义以真实数据库为准，后端返回字段要与实际数据结构一致。

## 常见模式

### 扩展表

- 使用 `custom_*` 命名
- 通过业务键关联官方表，如 `goods_id`、`order_no`
- 不在官方原表直接加字段

### 数据库访问

- 优先复用 `CustomPDO` 等现有封装
- 沿用现有配置加载方式
- SQL 写法优先参考相邻旧文件，不重新发明抽象层

参考：

- `module/Custom/Component/Database/CustomPDO.php`
- `module/Custom/Component/CustomGoods.php`

### 数据库事务

**何时使用**

- **建议使用**：同一业务动作包含**多条 SQL** 或**多张表**写入，且要求「要么全部成功、要么全部回滚」（例如：先删后插多行、扩展表与官方表同一订单内联动更新）。
- **可不强制**：单条语句完成的单表写入；或业务上允许短暂不一致且可容忍补偿的场景。

**本项目约定**

- 事务边界优先放在 **`module/Custom/Component/**`**（组件内），控制器保持薄层；与现有 `custom-backend.mdc` 一致。
- 使用 **`CustomPDO`** 时：`beginTransaction()` → 成功则 `commit()`，异常或失败路径必须 `rollback()`，避免半提交。
- **同一连接**：MySQL 事务绑定在**单个 PDO 连接**上。多处 `new CustomPDO()` 会得到**不同连接**，无法用一条事务包住两次独立实例上的写操作。需要跨表原子写时，应在**同一 `CustomPDO` 实例**内顺序执行，或封装为组件方法（参考 `CustomCartExtension::saveCustomizationDataWithCartQuantity`：扩展表与 `es_cart` 同连接事务）。
- **勿依赖嵌套事务**：不要假设「事务里再 begin」能嵌套；若外层已开事务，内层应通过参数由外层统一 `commit`/`rollback`（见 `BoardGoodsCategory::saveArticleCategories` 的 `$inTransaction` 用法）。

**已验证参考**

- `module/Custom/Component/Database/CustomPDO.php` — `beginTransaction` / `commit` / `rollback`
- `module/Custom/Component/Board/BoardGoodsCategory.php` — 分类与 meta 多步写、删除双表
- `module/Custom/Component/CustomCartExtension.php` — 扩展表与 `es_cart` 同事务
- `module/Custom/Component/CustomColor.php`、`CustomExtraOption.php` — 单业务多步写表示例
- `module/Custom/Component/CustomPattern.php` / `CustomPatternCategory.php` — 批量操作可选事务

## 写法要求

1. 数据结构优先级：
   - 数据库
   - 后端
   - 前端
2. 新增字段前，先确认是否已有扩展表可复用。
3. 查询逻辑优先写在组件，不直接堆到控制器。
4. 不擅自改字段名大小写或返回结构。

## 不要这样做

- 不要直接给官方核心表加字段
- 不要为追求“ORM 化”而抛弃已稳定运行的现有查询方式
- 不要在数据库字段、后端字段、前端字段之间随意改名
- 不要假设多个 `new CustomPDO()` 能共享同一条事务；跨表原子写需同一实例或合并为组件内方法

## 开发前自问

1. 这次需求能否落在现有 `custom_*` 表或新增扩展表？
2. 有没有现成组件已经处理类似查询？
3. 是否会影响现有字段名和返回结构兼容性？
4. 是否多步写库？若是，是否应用 `CustomPDO` 事务且保证同一连接？

## 优先参考文件

- `module/Custom/Component/Database/CustomPDO.php`
- `module/Custom/Component/CustomGoods.php`
- `module/Custom/Database/install_custom_board_goods_category.sql`

## 一句话规则

数据库相关开发优先复用 GodoMall 当前已跑通的扩展表与查询方式，增量扩展，不动核心；多步写库用事务并保证同一 PDO 连接。
