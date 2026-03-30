---
name: godomall-db-extension-patterns
description: 总结 GodoMall 项目中二次开发数据库与扩展表的既有写法，适用于 custom_ 表、扩展字段、定制表查询和数据库封装相关开发。需要动数据库相关逻辑时，优先参考仓库已验证可运行的旧实现和项目规则。
---

# GodoMall 数据库扩展写法

## 适用场景

当任务涉及以下内容时，优先使用本 Skill：

- `custom_*` 表
- 扩展字段
- 定制表查询
- `module/Custom/Component/Database/**`
- 数据库配置与连接封装

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

## 开发前自问

1. 这次需求能否落在现有 `custom_*` 表或新增扩展表？
2. 有没有现成组件已经处理类似查询？
3. 是否会影响现有字段名和返回结构兼容性？

## 优先参考文件

- `module/Custom/Component/Database/CustomPDO.php`
- `module/Custom/Component/CustomGoods.php`
- `module/Custom/Database/install_custom_board_goods_category.sql`

## 一句话规则

数据库相关开发优先复用 GodoMall 当前已跑通的扩展表与查询方式，增量扩展，不动核心。
