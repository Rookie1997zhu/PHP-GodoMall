---
name: godomall-entry-script-patterns
description: 总结 GodoMall 项目中独立 PHP 入口脚本的既有写法，适用于 module/Admin/custom_*.php 与 module/Custom/Controller/Front 下的入口文件。修改或新增入口脚本时，优先参考仓库里已验证可运行的旧文件。
---

# GodoMall 入口脚本写法

## 适用场景

当任务涉及以下内容时，优先使用本 Skill：

- `module/Admin/custom_*.php`
- 独立 PHP API 入口
- 非标准路由入口文件
- `init.php` 引导脚本

本 Skill 通常由以下规则触发：

- `.cursor/rules/entry-script-skill-routing.mdc`
- `.cursor/rules/skill-routing.mdc`

开始分析前，先读取：

- `.cursor/skills/godomall-existing-patterns/SKILL.md`

## 核心原则

1. 入口脚本只做薄层 HTTP 壳，不堆复杂业务。
2. 已有入口脚本是按 GodoMall 实际运行方式实验出来的，哪怕写法不够“标准化”，只要能稳定运行，就必须纳入参考。
3. 不擅自改动 `init.php` 引入方式、请求获取方式和输出头结构。

## 常见模式

1. 设置响应头
2. 按需处理 `OPTIONS`
3. `require_once .../init.php`
4. 读取请求参数或 JSON Body
5. 做最小校验
6. 分发到控制器或组件
7. 输出 JSON

参考：

- `module/Custom/Controller/Front/add_to_cart.php`
- `module/Admin/custom_color.php`
- `module/Admin/custom_pattern.php`
- `module/Admin/custom/custom_order_image.php`

## 写法要求

1. 保持与旧文件一致的响应头和编码方式。
2. 使用项目当前可运行的 `Request` 获取方式。
3. 入口脚本不承载长业务逻辑，复杂逻辑继续下放给控制器或组件。
4. 返回结构对齐旧接口，避免新旧前端无法兼容。

## 不要这样做

- 不要因为“更现代”就把入口脚本强改成另一套路由系统
- 不要跳过 `init.php`
- 不要把临时调通的老接口直接推翻重写

## 开发前自问

1. 这真的是入口脚本职责，还是应该放到控制器？
2. 有没有相邻的已运行入口文件可以直接照写？
3. 返回结构和请求格式是否需要兼容现有调用方？

## 优先规则

1. 先复用当前仓库已验证入口脚本的初始化与分发方式
2. 再遵循 `.cursor/rules/godomall.mdc` 与 `.cursor/rules/custom-backend.mdc`
3. 最后才参考通用“单文件 API”最佳实践

## 一句话规则

入口脚本优先保持 GodoMall 当前实际可运行链路，只做最薄的一层接入。
