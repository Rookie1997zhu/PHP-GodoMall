---
name: godomall-component-patterns
description: 总结 GodoMall 项目中 Custom 组件的既有写法，适用于 module/Custom/Component 下的业务组件、数据库封装组件与数据组装逻辑开发。新增或修改组件时，优先参考仓库里已验证可运行的旧文件。
---

# GodoMall 组件写法

## 适用场景

当任务涉及以下内容时，优先使用本 Skill：

- `module/Custom/Component/**/*.php`
- 定制表查询
- 业务数据组装
- 控制器下沉逻辑
- 数据库访问封装

本 Skill 通常由以下规则触发：

- `.cursor/rules/component-skill-routing.mdc`
- `.cursor/rules/skill-routing.mdc`

开始分析前，先读取：

- `.cursor/skills/godomall-existing-patterns/SKILL.md`

## 核心原则

1. 组件承载可复用业务逻辑，控制器保持薄层。
2. 仓库中的现有组件很多已通过实验验证，哪怕结构不够“理想”，只要运行稳定，就必须纳入参考。
3. 不因“更规范”就强制改成另一套 Service / Repository 分层。

## 常见模式

### 独立业务组件

- 命名空间使用 `Custom\Component\...`
- 可以不继承父类
- 构造中初始化 `CustomPDO` 或所需依赖
- 结合官方组件与定制表拼装结果

参考：

- `module/Custom/Component/CustomGoods.php`

### 数据库封装组件

- 放在 `module/Custom/Component/Database/**`
- 封装连接、查询、异常处理
- 与项目当前配置和连接方式保持一致

参考：

- `module/Custom/Component/Database/CustomPDO.php`

## 写法要求

1. 官方数据优先通过 `\App::load('\\Component\\...')` 读取。
2. 定制表数据优先通过现有 `CustomPDO` 或相邻封装处理。
3. 组件中允许：
   - SQL
   - 数据拼装
   - 批量查询
   - 日志记录
4. 组件返回结构优先对齐调用方旧习惯，不擅自改变字段名。
5. 发生异常时显式记录日志或抛出项目既有异常。

## 不要这样做

- 不要把组件里已经稳定运行的 PDO / `App::load()` 写法强行改成另一种抽象层
- 不要把已有字段名改成自己喜欢的命名
- 不要在没有必要时拆出多层新类，导致偏离项目现有结构

## 开发前自问

1. 相邻模块里最像的组件是哪一个？
2. 这段逻辑是否应该留在组件而不是控制器？
3. 现有返回结构、日志方式、取数方式能否直接复用？

## 优先参考文件

- `module/Custom/Component/CustomGoods.php`
- `module/Custom/Component/CustomPattern.php`
- `module/Custom/Component/CustomPatternCategory.php`
- `module/Custom/Component/Database/CustomPDO.php`

## 一句话规则

组件以“贴合项目现状、稳定可跑、便于控制器复用”为先，不追求脱离 GodoMall 实际环境的理想化分层。
