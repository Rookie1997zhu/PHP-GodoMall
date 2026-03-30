---
name: godomall-controller-patterns
description: 总结 GodoMall 项目中控制器的既有写法，适用于 module/Controller 与 module/Custom/Controller 下的前台、后台、覆写控制器开发。遇到控制器新增或修改时，优先参考仓库里已验证可运行的旧文件，而不是套用通用 MVC 习惯。
---

# GodoMall 控制器写法

## 适用场景

当任务涉及以下文件时，优先使用本 Skill：

- `module/Controller/**/*.php`
- `module/Custom/Controller/**/*.php`
- 控制器覆写
- 后台 API 控制器
- 前台页面控制器

本 Skill 通常由以下规则触发：

- `.cursor/rules/controller-skill-routing.mdc`
- `.cursor/rules/skill-routing.mdc`

开始分析前，先读取：

- `.cursor/skills/godomall-existing-patterns/SKILL.md`

## 核心原则

1. 先看同目录、同业务的既有控制器。
2. GodoMall 当前文件多数经过实验验证，哪怕局部不够“规范”，只要能稳定运行，就必须纳入参考。
3. 不为了“重构”而改变控制器基类、父类调用顺序、路由映射和 `mode` 分发。

## 控制器分类

### 覆写官方控制器

- 路径通常在 `module/Controller/**`
- 优先继承对应 `\Bundle\Controller\...`
- 常见模式：先 `parent::index()`，再增量补数据

参考：

- `module/Controller/Front/Board/ListController.php`

### 定制后台控制器

- 路径通常在 `module/Custom/Controller/Admin/**`
- 基类通常是 `\Controller\Admin\Controller`
- 构造函数里先 `parent::__construct()`，再初始化组件
- 若独立入口场景下父类初始化异常，先评估“继承不变 + `try/catch parent::__construct()`”兼容方案
- 后台 POST/AJAX 保留 CSRF、参数校验、稳定 JSON 结构

参考：

- `module/Custom/Controller/Admin/Custom/CustomGoodsController.php`

### 定制前台控制器

- 路径通常在 `module/Controller/Front/**` 或 `module/Custom/Controller/Front/**`
- 独立前台页通常继承 `\Controller\Front\Controller`
- 有 `mode` 时，在 `index()` 开头优先分流并 `return`

参考：

- `module/Controller/Front/Custom/GoodsCustomController.php`

## 写法要求

1. 构造函数：
   - 先 `parent::__construct()`
   - 再初始化组件或数据库对象
2. 页面数据：
   - 通过 `$this->setData()` 传模板
   - 变量名尽量复用旧文件
3. AJAX / JSON：
   - 对齐旧接口的 `success`、`message`、`data`
   - 不擅自切换成另一套返回格式
4. 控制器保持薄层：
   - 取参
   - 校验
   - 分发
   - 页面赋值或响应输出
   - 复杂查询下放组件

## 开发前自问

1. 这是覆写官方控制器，还是新增定制控制器？
2. 最像的现有控制器是哪一个？
3. 是否必须保留 `parent::index()` 或既有父类调用链？
4. 是否已有现成 `mode`、变量名、响应结构可复用？

## 优先参考文件

- `module/Controller/Front/Board/ListController.php`
- `module/Controller/Front/Board/ViewController.php`
- `module/Controller/Front/Custom/GoodsCustomController.php`
- `module/Controller/Admin/Custom/CustomOrderImageController.php`
- `module/Custom/Controller/Admin/Custom/CustomGoodsController.php`
- `module/Custom/Controller/Admin/Custom/CustomColorController.php`

## 一句话规则

先照着已验证可运行的控制器写，再做最小业务调整；不要把 GodoMall 控制器改成通用 MVC 教科书写法。

## 前台覆写控制器稳定性补充

### 适用范围

- `module/Controller/Front/**`
- 覆写官方前台页面控制器
- `parent::index()` 之后追加标签、筛选、扩展字段、附加展示数据

### 稳定性优先级

- 前台页面覆写应优先保证“官方主流程可正常渲染”
- 自定义增强逻辑失败时，默认不应直接导致整页 500
- 除非业务明确要求阻断页面，否则附加逻辑应允许降级

### 推荐模式

1. 先执行 `parent::index()`
2. 再进入单独的私有方法补充自定义数据
3. 在私有方法内部使用 `try/catch`
4. 失败时记录 `error_log`
5. 仅更新必要的模板变量，不重写整份官方控制器逻辑

示例模式：

```php
public function index()
{
    parent::index();
    $this->appendCustomData();
}

private function appendCustomData()
{
    try {
        $pageData = $this->getData('pageData');
        if (empty($pageData) || !is_array($pageData)) {
            return;
        }

        // 自定义增强逻辑
        $this->setData('pageData', $pageData);
    } catch (\Throwable $e) {
        error_log(__METHOD__ . ' failed: ' . $e->getMessage());
    }
}
```

### 优先参考的现有文件

- `module/Controller/Front/Order/CustomCartController.php`
- `module/Controller/Front/Board/ListController.php`

其中 `CustomCartController` 更适合作为“官方主流程优先，自定义增强可降级”的参考样板。

### 不推荐模式

- 为了补少量字段，整份复制官方前台控制器
- 在 `parent::index()` 后直接堆叠大量无保护逻辑
- 自定义查询失败时直接让整个前台页面 500
- 未先参考同目录、同类型、已验证可运行的控制器

### 补充判断规则

开发前先判断：

1. 这段逻辑是“页面核心逻辑”还是“附加增强逻辑”？
2. 如果附加逻辑失败，是否真的必须阻断整页？
3. 当前需求能否通过 `getData()` / `setData()` 在官方数据上最小追加？

### 一句话补充规则

前台覆写控制器优先保证官方页面可用；自定义增强逻辑应可失败、可降级、可记录，不应轻易把整页打挂。
