# Godomall 定制功能接口文档

## 概述

本文档描述了Godomall定制功能模块的API接口，包括图案管理和分类管理功能。所有接口均遵循RESTful设计原则，使用JSON格式进行数据交换。

## 基础信息

- **基础URL**: `https://your-domain.com`
- **API版本**: v1.0
- **数据格式**: JSON
- **字符编码**: UTF-8
- **认证方式**: Session认证（管理员后台）

## 通用响应格式

### 成功响应格式
```json
{
    "success": true,
    "code": 200,
    "message": "操作成功",
    "data": {
        // 具体数据内容
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 错误响应格式
```json
{
    "success": false,
    "code": 400,
    "message": "错误描述",
    "data": null,
    "timestamp": "2024-01-01 12:00:00"
}
```

### 分页响应格式
```json
{
    "success": true,
    "code": 200,
    "message": "获取成功",
    "data": {
        "items": [
            // 数据列表
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100,
            "total_pages": 5,
            "has_next": true,
            "has_prev": false
        }
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 405 | 请求方法不允许 |
| 500 | 服务器内部错误 |

---

## 一、图案管理功能API

### 1.1 获取图案列表

**接口地址**: `GET /admin/custom/pattern/list`

**功能描述**: 获取图案列表，支持分页、搜索、分类筛选

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| page_size | int | 否 | 每页数量，默认20，最大100 |
| keyword | string | 否 | 搜索关键词 |
| category | string | 否 | 分类筛选 |
| status | string | 否 | 状态筛选（active/inactive） |

**请求示例**:
```
GET /admin/custom/pattern/list?page=1&page_size=20&keyword=星星&category=基本分类
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案列表获取成功",
    "data": {
        "patterns": [
            {
                "pattern_id": 1,
                "pattern_name": "星星图案",
                "pattern_url": "/data/custom_images/patterns/2024/01/01/pattern_123456.png",
                "pattern_thumbnail": "/data/custom_images/patterns/2024/01/01/thumb_pattern_123456.png",
                "category": "基本分类",
                "tags": "几何,装饰",
                "description": "经典的星星图案",
                "sort_order": 1,
                "status": "active",
                "created_at": "2024-01-01 10:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 1,
            "total_pages": 1,
            "has_next": false,
            "has_prev": false
        }
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.2 获取图案信息

**接口地址**: `GET /admin/custom/pattern/get`

**功能描述**: 根据图案ID获取详细信息

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_id | int | 是 | 图案ID |

**请求示例**:
```
GET /admin/custom/pattern/get?pattern_id=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案信息获取成功",
    "data": {
        "pattern_id": 1,
        "pattern_name": "星星图案",
        "pattern_url": "/data/custom_images/patterns/2024/01/01/pattern_123456.png",
        "pattern_thumbnail": "/data/custom_images/patterns/2024/01/01/thumb_pattern_123456.png",
        "pattern_category": "基本分类",
        "pattern_group": "几何图案",
        "pattern_description": "经典的星星图案，适合各种装饰用途",
        "uploader": "admin",
        "file_size": 102400,
        "file_type": "png",
        "status": "active",
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.3 上传图案

**接口地址**: `POST /admin/custom/pattern/upload-pattern`

**功能描述**: 上传新的图案文件

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_file | file | 是 | 图案文件（支持jpg、png、gif、svg，最大5MB） |
| pattern_name | string | 是 | 图案名称 |
| pattern_category | string | 否 | 图案分类 |
| pattern_group | string | 否 | 图案分组 |
| pattern_description | string | 否 | 图案描述 |
| status | int | 否 | 状态（0-禁用，1-启用），默认1 |

**请求示例**:
```
POST /admin/custom/pattern/upload-pattern
Content-Type: multipart/form-data

pattern_file: [文件数据]
pattern_name: 新星星图案
pattern_category: 基本分类
pattern_group: 几何图案
pattern_description: 全新的星星图案设计
status: 1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案上传成功",
    "data": {
        "pattern_id": 2,
        "pattern_url": "/data/custom_images/patterns/2024/01/01/pattern_789012.png"
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.4 更新图案

**接口地址**: `POST /admin/custom/pattern/update`

**功能描述**: 更新图案信息

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_id | int | 是 | 图案ID |
| pattern_name | string | 是 | 图案名称 |
| pattern_category | string | 否 | 图案分类 |
| pattern_group | string | 否 | 图案分组 |
| pattern_description | string | 否 | 图案描述 |
| status | int | 否 | 状态（0-禁用，1-启用） |

**请求示例**:
```json
{
    "pattern_id": 1,
    "pattern_name": "更新后的星星图案",
    "pattern_category": "节日主题",
    "pattern_group": "装饰图案",
    "pattern_description": "更新后的图案描述",
    "status": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案更新成功",
    "data": null,
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.5 删除图案

**接口地址**: `POST /admin/custom/pattern/delete`

**功能描述**: 删除指定图案

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_id | int | 是 | 图案ID |

**请求示例**:
```json
{
    "pattern_id": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "图案删除成功",
    "data": null,
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.6 批量更新图案状态

**接口地址**: `POST /admin/custom/pattern/batch-update-status`

**功能描述**: 批量更新图案状态

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_ids | array | 是 | 图案ID数组 |
| status | int | 是 | 状态（0-禁用，1-启用） |

**请求示例**:
```json
{
    "pattern_ids": [1, 2, 3],
    "status": 0
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "批量禁用成功",
    "data": null,
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.7 批量删除图案

**接口地址**: `POST /admin/custom/pattern/batch-delete`

**功能描述**: 批量删除图案

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pattern_ids | array | 是 | 图案ID数组 |

**请求示例**:
```json
{
    "pattern_ids": [1, 2, 3]
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "批量删除完成：成功 2 个，失败 1 个",
    "data": {
        "total": 3,
        "success_count": 2,
        "failed_count": 1,
        "errors": [
            "图案ID 1: 该图案正在被商品使用，无法删除"
        ]
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 1.8 获取图案分类列表

**接口地址**: `GET /admin/custom/pattern/categories`

**功能描述**: 获取图案分类列表

**请求参数**: 无

**请求示例**:
```
GET /admin/custom/pattern/categories
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类列表获取成功",
    "data": [
        {
            "id": 1,
            "name": "基本分类",
            "parent_id": 0,
            "sort_order": 1,
            "status": 1
        },
        {
            "id": 2,
            "name": "节日主题",
            "parent_id": 0,
            "sort_order": 2,
            "status": 1
        }
    ],
    "timestamp": "2024-01-01 12:00:00"
}
```

---

## 二、分类管理功能API

### 2.1 获取分类列表

**接口地址**: `GET /admin/custom/category/list`

**功能描述**: 获取分类列表，支持分页和搜索

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认20 |
| keyword | string | 否 | 搜索关键词 |
| status | string | 否 | 状态筛选 |
| parent_id | int | 否 | 父分类ID筛选 |

**请求示例**:
```
GET /admin/custom/category/list?page=1&limit=20&keyword=基本&status=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类列表获取成功",
    "data": {
        "categories": [
            {
                "category_id": 1,
                "category_name": "基本分类",
                "category_description": "系统基本分类",
                "parent_id": 0,
                "parent_name": null,
                "sort_order": 1,
                "status": 1,
                "pattern_count": 5,
                "created_at": "2024-01-01 10:00:00",
                "created_at_formatted": "2024-01-01 10:00",
                "updated_at": "2024-01-01 10:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 1,
            "total_pages": 1,
            "has_next": false,
            "has_prev": false
        }
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.2 获取分类树形结构

**接口地址**: `GET /admin/custom/category/tree`

**功能描述**: 获取分类的树形结构

**请求参数**: 无

**请求示例**:
```
GET /admin/custom/category/tree
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类树获取成功",
    "data": {
        "category_tree": [
            {
                "category_id": 1,
                "category_name": "基本分类",
                "category_description": "系统基本分类",
                "parent_id": 0,
                "sort_order": 1,
                "status": 1,
                "created_at": "2024-01-01 10:00:00",
                "children": [
                    {
                        "category_id": 2,
                        "category_name": "几何图案",
                        "category_description": "几何形状图案",
                        "parent_id": 1,
                        "sort_order": 1,
                        "status": 1,
                        "created_at": "2024-01-01 10:00:00",
                        "children": []
                    }
                ]
            }
        ]
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.3 添加分类

**接口地址**: `POST /admin/custom/category/add`

**功能描述**: 添加新的分类

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| category_name | string | 是 | 分类名称 |
| category_description | string | 否 | 分类描述 |
| parent_id | int | 否 | 父分类ID，默认0 |
| sort_order | int | 否 | 排序顺序，默认0 |
| status | int | 否 | 状态（0-禁用，1-启用），默认1 |

**请求示例**:
```json
{
    "category_name": "新分类",
    "category_description": "新分类的描述",
    "parent_id": 0,
    "sort_order": 10,
    "status": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类添加成功",
    "data": {
        "category_id": 3,
        "category_name": "新分类"
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.4 更新分类

**接口地址**: `POST /admin/custom/category/update`

**功能描述**: 更新分类信息

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| category_id | int | 是 | 分类ID |
| category_name | string | 是 | 分类名称 |
| category_description | string | 否 | 分类描述 |
| parent_id | int | 否 | 父分类ID |
| sort_order | int | 否 | 排序顺序 |
| status | int | 否 | 状态（0-禁用，1-启用） |

**请求示例**:
```json
{
    "category_id": 1,
    "category_name": "更新后的基本分类",
    "category_description": "更新后的分类描述",
    "parent_id": 0,
    "sort_order": 1,
    "status": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类更新成功",
    "data": {
        "category_id": 1,
        "updated_fields": ["category_name", "category_description", "updated_at"]
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.5 删除分类

**接口地址**: `POST /admin/custom/category/delete`

**功能描述**: 删除指定分类

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| category_id | int | 是 | 分类ID |

**请求示例**:
```json
{
    "category_id": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类删除成功",
    "data": {
        "category_id": 1
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.6 更新分类排序

**接口地址**: `POST /admin/custom/category/update-sort`

**功能描述**: 批量更新分类排序

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| sort_data | object | 是 | 排序数据，键为分类ID，值为排序顺序 |

**请求示例**:
```json
{
    "sort_data": {
        "1": 10,
        "2": 20,
        "3": 30
    }
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类排序更新成功",
    "data": {
        "updated_count": 3
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.7 获取分类信息

**接口地址**: `GET /admin/custom/category/info`

**功能描述**: 根据分类ID获取详细信息

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| category_id | int | 是 | 分类ID |

**请求示例**:
```
GET /admin/custom/category/info?category_id=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类信息获取成功",
    "data": {
        "category_id": 1,
        "category_name": "基本分类",
        "category_description": "系统基本分类",
        "parent_id": 0,
        "sort_order": 1,
        "status": 1,
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00",
        "created_at_formatted": "2024-01-01 10:00",
        "updated_at_formatted": "2024-01-01 10:00"
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 2.8 验证分类名称唯一性

**接口地址**: `GET /admin/custom/category/check-name`

**功能描述**: 验证分类名称是否唯一

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| category_name | string | 是 | 分类名称 |
| parent_id | int | 否 | 父分类ID，默认0 |
| category_id | int | 否 | 分类ID（更新时排除自己） |

**请求示例**:
```
GET /admin/custom/category/check-name?category_name=基本分类&parent_id=0&category_id=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "分类名称唯一性检查完成",
    "data": {
        "exists": false
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

---

## 三、商品管理功能API

### 3.1 获取商品列表

**接口地址**: `GET /admin/goods/list`

**功能描述**: 获取商品列表，支持分页、搜索、分类筛选

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| page_size | int | 否 | 每页数量，默认20，最大100 |
| keyword | string | 否 | 搜索关键词 |
| category | string | 否 | 分类筛选 |
| status | string | 否 | 状态筛选（active/inactive） |
| is_customizable | int | 否 | 是否支持定制（0-不支持，1-支持） |

**请求示例**:
```
GET /admin/goods/list?page=1&page_size=20&keyword=定制&is_customizable=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "商品列表获取成功",
    "data": {
        "goods": [
            {
                "goods_no": 1,
                "goods_name": "定制T恤",
                "goods_price": 29.99,
                "goods_image": "/data/goods/2024/01/01/goods_123456.jpg",
                "category_name": "服装",
                "is_customizable": 1,
                "status": "active",
                "created_at": "2024-01-01 10:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 1,
            "total_pages": 1,
            "has_next": false,
            "has_prev": false
        }
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.2 获取商品信息

**接口地址**: `GET /admin/goods/info`

**功能描述**: 根据商品ID获取详细信息

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_no | int | 是 | 商品编号 |

**请求示例**:
```
GET /admin/goods/info?goods_no=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "商品信息获取成功",
    "data": {
        "goods_no": 1,
        "goods_name": "定制T恤",
        "goods_price": 29.99,
        "goods_image": "/data/goods/2024/01/01/goods_123456.jpg",
        "goods_description": "高品质定制T恤，支持个性化图案",
        "category_name": "服装",
        "is_customizable": 1,
        "default_color": "white",
        "default_size": "M",
        "custom_width": 30.0,
        "custom_height": 40.0,
        "status": "active",
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.3 设置商品定制属性

**接口地址**: `POST /admin/custom/goods/set-customizable`

**功能描述**: 设置商品是否支持定制

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_no | int | 是 | 商品编号 |
| is_customizable | int | 是 | 是否支持定制（0-不支持，1-支持） |

**请求示例**:
```json
{
    "goods_no": 1,
    "is_customizable": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "商品定制属性设置成功",
    "data": null,
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.4 设置商品定制图案

**接口地址**: `POST /admin/custom/goods/set-patterns`

**功能描述**: 设置商品可用的定制图案

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_no | int | 是 | 商品编号 |
| pattern_ids | array | 是 | 图案ID数组 |

**请求示例**:
```json
{
    "goods_no": 1,
    "pattern_ids": [1, 2, 3, 4]
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "商品定制图案设置成功",
    "data": {
        "pattern_count": 4
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.5 获取商品定制图案

**接口地址**: `GET /admin/custom/goods/patterns`

**功能描述**: 获取商品关联的定制图案列表

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_no | int | 是 | 商品编号 |

**请求示例**:
```
GET /admin/custom/goods/patterns?goods_no=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "商品定制图案获取成功",
    "data": {
        "patterns": [
            {
                "pattern_id": 1,
                "pattern_name": "星星图案",
                "pattern_url": "/data/custom_images/patterns/2024/01/01/pattern_123456.png",
                "pattern_thumbnail": "/data/custom_images/patterns/2024/01/01/thumb_pattern_123456.png",
                "category": "基本分类",
                "sort_order": 1
            }
        ],
        "total": 1
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.6 批量设置商品定制图案

**接口地址**: `POST /admin/custom/goods/batch-set-patterns`

**功能描述**: 批量设置多个商品的定制图案

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_nos | array | 是 | 商品编号数组 |
| pattern_ids | array | 是 | 图案ID数组 |

**请求示例**:
```json
{
    "goods_nos": [1, 2, 3],
    "pattern_ids": [1, 2, 3]
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "批量设置成功",
    "data": {
        "total_goods": 3,
        "success_count": 3,
        "failed_count": 0,
        "errors": []
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.7 添加商品自定义尺寸

**接口地址**: `POST /admin/custom/goods/add-custom-size`

**功能描述**: 为商品添加自定义尺寸

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |
| size_name | string | 是 | 尺寸名称 |
| size_width | float | 是 | 宽度 |
| size_height | float | 是 | 高度 |
| size_unit | string | 否 | 尺寸单位（inch/cm/mm/m），默认inch |

**请求示例**:
```json
{
    "goods_id": 1,
    "size_name": "超大号",
    "size_width": 50.0,
    "size_height": 60.0,
    "size_unit": "cm"
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "自定义尺寸添加成功",
    "data": {
        "custom_size_id": 1,
        "size_name": "超大号",
        "size_width": 50.0,
        "size_height": 60.0,
        "size_unit": "cm"
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.8 获取商品所有尺寸

**接口地址**: `GET /admin/custom/goods/all-sizes`

**功能描述**: 获取商品的所有尺寸（标准尺寸+自定义尺寸）

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| goods_id | int | 是 | 商品ID |

**请求示例**:
```
GET /admin/custom/goods/all-sizes?goods_id=1
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "商品尺寸信息获取成功",
    "data": {
        "standard_sizes": [
            {
                "size_id": 1,
                "size_code": "S",
                "size_name": "Small",
                "size_display": "S (小号)",
                "is_custom": 0
            }
        ],
        "custom_sizes": [
            {
                "custom_size_id": 1,
                "size_name": "超大号",
                "size_width": 50.0,
                "size_height": 60.0,
                "size_unit": "cm",
                "is_custom": 1
            }
        ]
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.9 删除商品自定义尺寸

**接口地址**: `POST /admin/custom/goods/delete-custom-size`

**功能描述**: 删除商品的自定义尺寸

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| custom_size_id | int | 是 | 自定义尺寸ID |

**请求示例**:
```json
{
    "custom_size_id": 1
}
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "自定义尺寸删除成功",
    "data": null,
    "timestamp": "2024-01-01 12:00:00"
}
```

### 3.10 获取可选图案库

**接口地址**: `GET /admin/custom/goods/available-patterns`

**功能描述**: 获取可用于商品定制的图案库

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认20 |
| category | string | 否 | 分类筛选 |
| group | string | 否 | 分组筛选 |
| keyword | string | 否 | 搜索关键词 |
| status | int | 否 | 状态筛选，默认1 |

**请求示例**:
```
GET /admin/custom/goods/available-patterns?page=1&limit=20&category=基本分类&keyword=星星
```

**响应示例**:
```json
{
    "success": true,
    "code": 200,
    "message": "可选图案库获取成功",
    "data": {
        "patterns": [
            {
                "pattern_id": 1,
                "pattern_name": "星星图案",
                "pattern_url": "/data/custom_images/patterns/2024/01/01/pattern_123456.png",
                "pattern_thumbnail": "/data/custom_images/patterns/2024/01/01/thumb_pattern_123456.png",
                "category": "基本分类",
                "tags": "几何,装饰",
                "description": "经典的星星图案",
                "status": "active"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 1,
            "total_pages": 1,
            "has_next": false,
            "has_prev": false
        }
    },
    "timestamp": "2024-01-01 12:00:00"
}
```

---

## 四、数据格式说明

### 4.1 商品数据格式

#### 商品基本信息
```json
{
    "goods_no": 1,                          // 商品编号（整数）
    "goods_name": "定制T恤",                 // 商品名称（字符串，最大100字符）
    "goods_price": 29.99,                   // 商品价格（浮点数）
    "goods_image": "/path/to/image.jpg",     // 商品图片URL（字符串）
    "goods_description": "商品描述",          // 商品描述（文本）
    "category_name": "服装",                 // 分类名称（字符串）
    "is_customizable": 1,                   // 是否支持定制（0-不支持，1-支持）
    "default_color": "white",               // 默认颜色（字符串）
    "default_size": "M",                    // 默认尺寸（字符串）
    "custom_width": 30.0,                   // 自定义宽度（浮点数）
    "custom_height": 40.0,                  // 自定义高度（浮点数）
    "status": "active",                     // 状态（active/inactive）
    "created_at": "2024-01-01 10:00:00",    // 创建时间（日期时间）
    "updated_at": "2024-01-01 10:00:00"     // 更新时间（日期时间）
}
```

#### 商品定制配置数据格式
```json
{
    "goods_no": 1,                          // 商品编号（必填）
    "is_customizable": 1,                   // 是否支持定制（必填）
    "default_color": "white",               // 默认颜色（可选）
    "default_size": "M",                    // 默认尺寸（可选）
    "custom_width": 30.0,                   // 自定义宽度（可选）
    "custom_height": 40.0                   // 自定义高度（可选）
}
```

#### 商品尺寸数据格式
```json
{
    "size_id": 1,                           // 尺寸ID（整数）
    "size_code": "S",                       // 尺寸代码（字符串）
    "size_name": "Small",                   // 尺寸名称（字符串）
    "size_display": "S (小号)",              // 显示名称（字符串）
    "size_width": 30.0,                     // 宽度（浮点数）
    "size_height": 40.0,                    // 高度（浮点数）
    "size_unit": "cm",                      // 尺寸单位（字符串）
    "is_custom": 0                          // 是否为自定义尺寸（0-标准，1-自定义）
}
```

#### 自定义尺寸添加数据格式
```json
{
    "goods_id": 1,                          // 商品ID（必填）
    "size_name": "超大号",                   // 尺寸名称（必填，最大50字符）
    "size_width": 50.0,                     // 宽度（必填，浮点数）
    "size_height": 60.0,                    // 高度（必填，浮点数）
    "size_unit": "cm"                       // 尺寸单位（可选，默认inch）
}
```

### 4.2 图案数据格式

#### 图案基本信息
```json
{
    "pattern_id": 1,                    // 图案ID（整数）
    "pattern_name": "星星图案",          // 图案名称（字符串，最大100字符）
    "pattern_url": "/path/to/image.png", // 图案文件URL（字符串）
    "pattern_thumbnail": "/path/to/thumb.png", // 缩略图URL（字符串，可选）
    "category": "基本分类",              // 分类（字符串，最大50字符）
    "tags": "几何,装饰",                // 标签（字符串，最大255字符）
    "description": "图案描述",           // 描述（文本）
    "sort_order": 1,                    // 排序顺序（整数）
    "status": "active",                 // 状态（active/inactive）
    "created_at": "2024-01-01 10:00:00" // 创建时间（日期时间）
}
```

#### 图案上传数据格式
```json
{
    "pattern_name": "新图案",           // 图案名称（必填）
    "pattern_category": "分类名",       // 图案分类（可选）
    "pattern_group": "分组名",          // 图案分组（可选）
    "pattern_description": "描述",      // 图案描述（可选）
    "status": 1                        // 状态（可选，默认1）
}
```

### 4.3 分类数据格式

#### 分类基本信息
```json
{
    "category_id": 1,                   // 分类ID（整数）
    "category_name": "基本分类",         // 分类名称（字符串，最大100字符）
    "category_description": "描述",      // 分类描述（文本）
    "parent_id": 0,                     // 父分类ID（整数，0表示顶级分类）
    "sort_order": 1,                    // 排序顺序（整数）
    "status": 1,                        // 状态（0-禁用，1-启用）
    "pattern_count": 5,                 // 图案数量（整数）
    "created_at": "2024-01-01 10:00:00", // 创建时间（日期时间）
    "updated_at": "2024-01-01 10:00:00"  // 更新时间（日期时间）
}
```

#### 分类树形结构
```json
{
    "category_id": 1,
    "category_name": "基本分类",
    "category_description": "描述",
    "parent_id": 0,
    "sort_order": 1,
    "status": 1,
    "created_at": "2024-01-01 10:00:00",
    "children": [                       // 子分类数组
        {
            "category_id": 2,
            "category_name": "子分类",
            "parent_id": 1,
            "sort_order": 1,
            "status": 1,
            "created_at": "2024-01-01 10:00:00",
            "children": []              // 递归结构
        }
    ]
}
```

### 4.4 分页数据格式

```json
{
    "current_page": 1,                  // 当前页码（整数）
    "per_page": 20,                     // 每页数量（整数）
    "total": 100,                       // 总记录数（整数）
    "total_pages": 5,                   // 总页数（整数）
    "has_next": true,                   // 是否有下一页（布尔值）
    "has_prev": false                   // 是否有上一页（布尔值）
}
```

### 4.5 文件上传格式

#### 支持的文件类型
- **图片格式**: JPG, JPEG, PNG, GIF, SVG
- **文件大小**: 最大5MB
- **上传方式**: multipart/form-data

#### 文件命名规则
- 自动生成唯一文件名：`pattern_` + 时间戳 + 扩展名
- 存储路径：`/data/custom_images/patterns/年/月/日/`

### 4.6 状态码说明

#### 图案状态
- `active`: 启用状态
- `inactive`: 禁用状态

#### 分类状态
- `1`: 启用
- `0`: 禁用

---

## 五、使用示例

### 5.1 完整的图案管理流程

#### 1. 获取图案列表
```bash
curl -X GET "https://your-domain.com/admin/custom/pattern/list?page=1&page_size=20" \
  -H "Cookie: session_id=your_session_id"
```

#### 2. 上传新图案
```bash
curl -X POST "https://your-domain.com/admin/custom/pattern/upload-pattern" \
  -H "Cookie: session_id=your_session_id" \
  -F "pattern_file=@/path/to/image.png" \
  -F "pattern_name=新图案" \
  -F "pattern_category=基本分类" \
  -F "status=1"
```

#### 3. 更新图案信息
```bash
curl -X POST "https://your-domain.com/admin/custom/pattern/update" \
  -H "Content-Type: application/json" \
  -H "Cookie: session_id=your_session_id" \
  -d '{
    "pattern_id": 1,
    "pattern_name": "更新后的图案名称",
    "pattern_category": "新分类",
    "status": 1
  }'
```

### 5.2 完整的分类管理流程

#### 1. 获取分类树
```bash
curl -X GET "https://your-domain.com/admin/custom/category/tree" \
  -H "Cookie: session_id=your_session_id"
```

#### 2. 添加新分类
```bash
curl -X POST "https://your-domain.com/admin/custom/category/add" \
  -H "Content-Type: application/json" \
  -H "Cookie: session_id=your_session_id" \
  -d '{
    "category_name": "新分类",
    "category_description": "新分类描述",
    "parent_id": 0,
    "sort_order": 10,
    "status": 1
  }'
```

#### 3. 更新分类排序
```bash
curl -X POST "https://your-domain.com/admin/custom/category/update-sort" \
  -H "Content-Type: application/json" \
  -H "Cookie: session_id=your_session_id" \
  -d '{
    "sort_data": {
      "1": 10,
      "2": 20,
      "3": 30
    }
  }'
```

### 5.3 完整的商品管理流程

#### 1. 获取商品列表
```bash
curl -X GET "https://your-domain.com/admin/goods/list?page=1&page_size=20&is_customizable=1" \
  -H "Cookie: session_id=your_session_id"
```

#### 2. 设置商品定制属性
```bash
curl -X POST "https://your-domain.com/admin/custom/goods/set-customizable" \
  -H "Content-Type: application/json" \
  -H "Cookie: session_id=your_session_id" \
  -d '{
    "goods_no": 1,
    "is_customizable": 1
  }'
```

#### 3. 设置商品定制图案
```bash
curl -X POST "https://your-domain.com/admin/custom/goods/set-patterns" \
  -H "Content-Type: application/json" \
  -H "Cookie: session_id=your_session_id" \
  -d '{
    "goods_no": 1,
    "pattern_ids": [1, 2, 3, 4]
  }'
```

#### 4. 添加商品自定义尺寸
```bash
curl -X POST "https://your-domain.com/admin/custom/goods/add-custom-size" \
  -H "Content-Type: application/json" \
  -H "Cookie: session_id=your_session_id" \
  -d '{
    "goods_id": 1,
    "size_name": "超大号",
    "size_width": 50.0,
    "size_height": 60.0,
    "size_unit": "cm"
  }'
```

---

## 六、注意事项

### 6.1 权限要求
- 所有接口都需要管理员登录状态
- 不同操作可能需要不同的权限级别
- 建议在调用前检查用户权限

### 6.2 文件上传限制
- 支持的文件格式：JPG, JPEG, PNG, GIF, SVG
- 文件大小限制：5MB
- 建议图片尺寸：不超过2048x2048像素

### 6.3 数据验证
- 所有必填字段都需要验证
- 字符串长度限制需要遵守
- 分类名称在同一父分类下必须唯一

### 6.4 错误处理
- 所有接口都会返回统一的错误格式
- 建议根据错误码进行相应的错误处理
- 网络错误或超时需要重试机制

### 6.5 性能优化
- 列表接口支持分页，建议合理设置每页数量
- 大量数据操作建议使用批量接口
- 图片上传建议先压缩再上传

---

## 七、更新日志

### v1.0 (2024-01-01)
- 初始版本发布
- 支持图案管理功能
- 支持分类管理功能
- 支持商品管理功能
- 提供完整的API接口文档

---

**文档版本**: v1.0  
**最后更新**: 2024-01-01  
**维护团队**: Custom Development Team
