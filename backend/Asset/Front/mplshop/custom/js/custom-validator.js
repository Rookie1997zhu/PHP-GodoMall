/**
 * 自定义验证工具类
 * 
 * 提供统一的前端数据验证功能，与后端验证规则保持一致
 * 严格遵循Godomall开发规范
 * 
 * @package Front\Custom\JS
 * @author Custom Development Team
 * @version 1.0
 */

(function($) {
    'use strict';

    /**
     * 自定义验证器
     */
    window.CustomValidator = {
        
        /**
         * 验证规则配置
         */
        rules: {
            // 分类名称验证
            categoryName: {
                required: true,
                maxLength: 100,
                pattern: /^[^<>"']*$/,
                message: {
                    required: '分类名称不能为空',
                    maxLength: '分类名称不能超过100个字符',
                    pattern: '分类名称包含非法字符'
                }
            },
            
            // 分类描述验证
            categoryDescription: {
                required: false,
                maxLength: 500,
                message: {
                    maxLength: '分类描述不能超过500个字符'
                }
            },
            
            // 商品编号验证
            goodsNo: {
                required: true,
                pattern: /^\d+$/,
                message: {
                    required: '商品编号不能为空',
                    pattern: '商品编号格式无效'
                }
            },
            
            // 页码验证
            page: {
                required: true,
                min: 1,
                pattern: /^\d+$/,
                message: {
                    required: '页码不能为空',
                    min: '页码必须大于0',
                    pattern: '页码格式无效'
                }
            },
            
            // 每页数量验证
            limit: {
                required: true,
                min: 1,
                max: 100,
                pattern: /^\d+$/,
                message: {
                    required: '每页数量不能为空',
                    min: '每页数量必须大于0',
                    max: '每页数量不能超过100',
                    pattern: '每页数量格式无效'
                }
            },
            
            // 排序值验证
            sortOrder: {
                required: false,
                min: 0,
                max: 9999,
                pattern: /^\d+$/,
                message: {
                    min: '排序值不能小于0',
                    max: '排序值不能超过9999',
                    pattern: '排序值格式无效'
                }
            },
            
            // 状态值验证
            status: {
                required: false,
                pattern: /^[01]$/,
                message: {
                    pattern: '状态值无效'
                }
            }
        },

        /**
         * 验证单个字段
         * 
         * @param {string} fieldName 字段名
         * @param {*} value 字段值
         * @param {object} customRules 自定义规则（可选）
         * @return {object} 验证结果
         */
        validateField: function(fieldName, value, customRules) {
            var rule = customRules || this.rules[fieldName];
            if (!rule) {
                return { valid: true, message: '', details: [] };
            }

            var errors = [];

            // 必填验证
            if (rule.required && (value === null || value === undefined || value === '')) {
                errors.push(rule.message.required);
                return { valid: false, message: '字段验证失败', details: errors };
            }

            // 如果值为空且非必填，直接通过
            if (!rule.required && (value === null || value === undefined || value === '')) {
                return { valid: true, message: '', details: [] };
            }

            // 长度验证
            if (rule.minLength && value.length < rule.minLength) {
                errors.push(rule.message.minLength);
            }
            if (rule.maxLength && value.length > rule.maxLength) {
                errors.push(rule.message.maxLength);
            }

            // 数值范围验证
            if (rule.min !== undefined && parseFloat(value) < rule.min) {
                errors.push(rule.message.min);
            }
            if (rule.max !== undefined && parseFloat(value) > rule.max) {
                errors.push(rule.message.max);
            }

            // 正则表达式验证
            if (rule.pattern && !rule.pattern.test(value)) {
                errors.push(rule.message.pattern);
            }

            if (errors.length === 0) {
                return { valid: true, message: '', details: [] };
            }

            return { valid: false, message: '字段验证失败', details: errors };
        },

        /**
         * 验证分类数据
         * 
         * @param {object} data 分类数据
         * @return {object} 验证结果
         */
        validateCategoryData: function(data) {
            var errors = [];

            // 验证分类名称
            var nameResult = this.validateField('categoryName', data.category_name);
            if (!nameResult.valid) {
                errors = errors.concat(nameResult.details);
            }

            // 验证分类描述
            var descResult = this.validateField('categoryDescription', data.category_description);
            if (!descResult.valid) {
                errors = errors.concat(descResult.details);
            }

            // 验证排序值
            var sortResult = this.validateField('sortOrder', data.sort_order);
            if (!sortResult.valid) {
                errors = errors.concat(sortResult.details);
            }

            // 验证状态值
            var statusResult = this.validateField('status', data.status);
            if (!statusResult.valid) {
                errors = errors.concat(statusResult.details);
            }

            // 验证父级ID
            if (data.parent_id !== undefined && data.parent_id < 0) {
                errors.push('父级分类ID不能为负数');
            }

            if (errors.length === 0) {
                return { valid: true, message: '', details: [] };
            }

            return { valid: false, message: '数据验证失败', details: errors };
        },

        /**
         * 验证分类列表参数
         * 
         * @param {object} params 请求参数
         * @return {object} 验证结果
         */
        validateCategoryListParams: function(params) {
            var errors = [];

            // 验证页码
            var pageResult = this.validateField('page', params.page);
            if (!pageResult.valid) {
                errors = errors.concat(pageResult.details);
            }

            // 验证每页数量
            var limitResult = this.validateField('limit', params.limit);
            if (!limitResult.valid) {
                errors = errors.concat(limitResult.details);
            }

            // 验证关键词
            if (params.keyword && params.keyword.length > 100) {
                errors.push('搜索关键词不能超过100个字符');
            }

            // 验证父级ID
            if (params.parent_id !== undefined && params.parent_id < 0) {
                errors.push('父级分类ID不能为负数');
            }

            // 验证状态
            if (params.status && !['', '0', '1'].includes(params.status)) {
                errors.push('状态值无效');
            }

            if (errors.length === 0) {
                return { valid: true, message: '', details: [] };
            }

            return { valid: false, message: '参数验证失败', details: errors };
        },

        /**
         * 验证商品参数
         * 
         * @param {object} params 请求参数
         * @return {object} 验证结果
         */
        validateGoodsParams: function(params) {
            var errors = [];

            // 验证商品编号
            var goodsResult = this.validateField('goodsNo', params.goodsNo);
            if (!goodsResult.valid) {
                errors = errors.concat(goodsResult.details);
            }

            if (errors.length === 0) {
                return { valid: true, message: '', details: [] };
            }

            return { valid: false, message: '参数验证失败', details: errors };
        },

        /**
         * 验证定制数据
         * 
         * @param {object} data 定制数据
         * @return {object} 验证结果
         */
        validateCustomizationData: function(data) {
            var errors = [];

            // 验证商品编号
            var goodsResult = this.validateField('goodsNo', data.goodsNo);
            if (!goodsResult.valid) {
                errors = errors.concat(goodsResult.details);
            }

            // 验证定制数据
            if (!data.customizationData) {
                errors.push('定制数据不能为空');
            }

            // 验证颜色
            if (data.selectedColor && data.selectedColor.length > 50) {
                errors.push('颜色名称不能超过50个字符');
            }

            // 验证尺寸
            if (data.selectedSize && data.selectedSize.length > 50) {
                errors.push('尺寸名称不能超过50个字符');
            }

            if (errors.length === 0) {
                return { valid: true, message: '', details: [] };
            }

            return { valid: false, message: '数据验证失败', details: errors };
        },

        /**
         * 实时验证输入框
         * 
         * @param {jQuery} $input 输入框元素
         * @param {string} fieldName 字段名
         * @param {object} customRules 自定义规则（可选）
         */
        validateInput: function($input, fieldName, customRules) {
            var value = $input.val();
            var result = this.validateField(fieldName, value, customRules);
            
            // 移除之前的错误样式
            $input.removeClass('error');
            $input.siblings('.error-message').remove();
            
            if (!result.valid) {
                // 添加错误样式
                $input.addClass('error');
                
                // 显示错误消息
                var $errorMsg = $('<div class="error-message" style="color: #d32f2f; font-size: 12px; margin-top: 4px;"></div>');
                $errorMsg.text(result.details[0]); // 显示第一个错误
                $input.after($errorMsg);
            }
            
            return result;
        },

        /**
         * 验证表单
         * 
         * @param {jQuery} $form 表单元素
         * @param {object} fieldRules 字段规则映射
         * @return {object} 验证结果
         */
        validateForm: function($form, fieldRules) {
            var allErrors = [];
            var isValid = true;

            $.each(fieldRules, function(fieldName, ruleName) {
                var $input = $form.find('[name="' + fieldName + '"]');
                if ($input.length > 0) {
                    var result = CustomValidator.validateInput($input, ruleName);
                    if (!result.valid) {
                        isValid = false;
                        allErrors = allErrors.concat(result.details);
                    }
                }
            });

            if (isValid) {
                return { valid: true, message: '', details: [] };
            }

            return { valid: false, message: '表单验证失败', details: allErrors };
        }
    };

    // jQuery插件形式
    $.fn.customValidate = function(fieldName, customRules) {
        return this.each(function() {
            var $this = $(this);
            CustomValidator.validateInput($this, fieldName, customRules);
        });
    };

})(jQuery);
