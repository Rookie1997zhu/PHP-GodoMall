/**
 * 自定义错误处理器
 * 
 * 提供统一的错误提示UI组件，与后端错误响应格式保持一致
 * 严格遵循Godomall开发规范
 * 
 * @package Front\Custom\JS
 * @author Custom Development Team
 * @version 1.0
 */

(function($) {
    'use strict';

    /**
     * 自定义错误处理器
     */
    window.CustomErrorHandler = {
        
        /**
         * 错误类型常量
         */
        ERROR_TYPES: {
            VALIDATION: 'validation',
            NETWORK: 'network',
            SERVER: 'server',
            BUSINESS: 'business'
        },

        /**
         * 显示成功消息
         * 
         * @param {string} message 成功消息
         * @param {object} options 选项
         */
        showSuccess: function(message, options) {
            options = $.extend({
                duration: 3000,
                position: 'top-right',
                autoHide: true
            }, options || {});

            this.showMessage(message, 'success', options);
        },

        /**
         * 显示错误消息
         * 
         * @param {string|array} message 错误消息
         * @param {object} options 选项
         */
        showError: function(message, options) {
            options = $.extend({
                duration: 5000,
                position: 'top-right',
                autoHide: true
            }, options || {});

            this.showMessage(message, 'error', options);
        },

        /**
         * 显示警告消息
         * 
         * @param {string} message 警告消息
         * @param {object} options 选项
         */
        showWarning: function(message, options) {
            options = $.extend({
                duration: 4000,
                position: 'top-right',
                autoHide: true
            }, options || {});

            this.showMessage(message, 'warning', options);
        },

        /**
         * 显示信息消息
         * 
         * @param {string} message 信息消息
         * @param {object} options 选项
         */
        showInfo: function(message, options) {
            options = $.extend({
                duration: 3000,
                position: 'top-right',
                autoHide: true
            }, options || {});

            this.showMessage(message, 'info', options);
        },

        /**
         * 显示消息
         * 
         * @param {string|array} message 消息内容
         * @param {string} type 消息类型
         * @param {object} options 选项
         */
        showMessage: function(message, type, options) {
            var self = this;
            
            // 处理数组消息
            if (Array.isArray(message)) {
                message = message.join('<br>');
            }

            // 创建消息容器
            var $container = this.getMessageContainer(options.position);
            
            // 创建消息元素
            var $message = this.createMessageElement(message, type, options);
            
            // 添加到容器
            $container.append($message);
            
            // 显示动画
            $message.addClass('show');
            
            // 自动隐藏
            if (options.autoHide && options.duration > 0) {
                setTimeout(function() {
                    self.hideMessage($message);
                }, options.duration);
            }
        },

        /**
         * 获取消息容器
         * 
         * @param {string} position 位置
         * @return {jQuery} 容器元素
         */
        getMessageContainer: function(position) {
            var containerId = 'custom-message-container-' + position;
            var $container = $('#' + containerId);
            
            if ($container.length === 0) {
                $container = $('<div id="' + containerId + '" class="custom-message-container"></div>');
                $container.css(this.getContainerStyles(position));
                $('body').append($container);
            }
            
            return $container;
        },

        /**
         * 创建消息元素
         * 
         * @param {string} message 消息内容
         * @param {string} type 消息类型
         * @param {object} options 选项
         * @return {jQuery} 消息元素
         */
        createMessageElement: function(message, type, options) {
            var $message = $('<div class="custom-message custom-message-' + type + '"></div>');
            
            // 添加图标
            var icon = this.getTypeIcon(type);
            if (icon) {
                $message.append('<span class="custom-message-icon">' + icon + '</span>');
            }
            
            // 添加内容
            $message.append('<span class="custom-message-content">' + message + '</span>');
            
            // 添加关闭按钮
            if (options.closable !== false) {
                var $closeBtn = $('<span class="custom-message-close">&times;</span>');
                $closeBtn.on('click', function() {
                    CustomErrorHandler.hideMessage($message);
                });
                $message.append($closeBtn);
            }
            
            // 设置样式
            $message.css(this.getMessageStyles(type));
            
            return $message;
        },

        /**
         * 隐藏消息
         * 
         * @param {jQuery} $message 消息元素
         */
        hideMessage: function($message) {
            $message.removeClass('show');
            setTimeout(function() {
                $message.remove();
            }, 300);
        },

        /**
         * 获取类型图标
         * 
         * @param {string} type 消息类型
         * @return {string} 图标HTML
         */
        getTypeIcon: function(type) {
            var icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            return icons[type] || '';
        },

        /**
         * 获取容器样式
         * 
         * @param {string} position 位置
         * @return {object} 样式对象
         */
        getContainerStyles: function(position) {
            var styles = {
                position: 'fixed',
                zIndex: 9999,
                pointerEvents: 'none'
            };

            switch (position) {
                case 'top-right':
                    styles.top = '20px';
                    styles.right = '20px';
                    break;
                case 'top-left':
                    styles.top = '20px';
                    styles.left = '20px';
                    break;
                case 'bottom-right':
                    styles.bottom = '20px';
                    styles.right = '20px';
                    break;
                case 'bottom-left':
                    styles.bottom = '20px';
                    styles.left = '20px';
                    break;
                case 'top-center':
                    styles.top = '20px';
                    styles.left = '50%';
                    styles.transform = 'translateX(-50%)';
                    break;
                case 'bottom-center':
                    styles.bottom = '20px';
                    styles.left = '50%';
                    styles.transform = 'translateX(-50%)';
                    break;
            }

            return styles;
        },

        /**
         * 获取消息样式
         * 
         * @param {string} type 消息类型
         * @return {object} 样式对象
         */
        getMessageStyles: function(type) {
            var baseStyles = {
                display: 'flex',
                alignItems: 'center',
                padding: '12px 16px',
                marginBottom: '10px',
                borderRadius: '4px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                minWidth: '300px',
                maxWidth: '500px',
                opacity: '0',
                transform: 'translateX(100%)',
                transition: 'all 0.3s ease',
                pointerEvents: 'auto'
            };

            var typeStyles = {
                success: {
                    backgroundColor: '#f6ffed',
                    border: '1px solid #b7eb8f',
                    color: '#52c41a'
                },
                error: {
                    backgroundColor: '#fff2f0',
                    border: '1px solid #ffccc7',
                    color: '#ff4d4f'
                },
                warning: {
                    backgroundColor: '#fffbe6',
                    border: '1px solid #ffe58f',
                    color: '#faad14'
                },
                info: {
                    backgroundColor: '#e6f7ff',
                    border: '1px solid #91d5ff',
                    color: '#1890ff'
                }
            };

            return $.extend(baseStyles, typeStyles[type] || typeStyles.info);
        },

        /**
         * 处理API错误响应
         * 
         * @param {object} response 错误响应
         * @param {object} options 选项
         */
        handleApiError: function(response, options) {
            options = $.extend({
                showDetails: true,
                type: 'error'
            }, options || {});

            var message = response.message || '请求失败';
            var details = response.data || [];

            if (options.showDetails && Array.isArray(details) && details.length > 0) {
                message += '<br><ul style="margin: 8px 0 0 0; padding-left: 20px;">';
                details.forEach(function(detail) {
                    message += '<li>' + detail + '</li>';
                });
                message += '</ul>';
            }

            this.showMessage(message, options.type);
        },

        /**
         * 显示表单验证错误
         * 
         * @param {object} errors 错误对象
         * @param {jQuery} $form 表单元素
         */
        showFormErrors: function(errors, $form) {
            var self = this;
            
            // 清除之前的错误
            $form.find('.field-error').removeClass('field-error');
            $form.find('.error-message').remove();

            if (Array.isArray(errors)) {
                // 显示通用错误消息
                this.showError(errors);
            } else if (typeof errors === 'object') {
                // 显示字段错误
                $.each(errors, function(fieldName, fieldErrors) {
                    var $field = $form.find('[name="' + fieldName + '"]');
                    if ($field.length > 0) {
                        $field.addClass('field-error');
                        
                        var $errorMsg = $('<div class="error-message" style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"></div>');
                        $errorMsg.text(Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors);
                        $field.after($errorMsg);
                    }
                });
            }
        },

        /**
         * 清除所有消息
         */
        clearAll: function() {
            $('.custom-message-container').remove();
        }
    };

    // 添加CSS样式
    if (!$('#custom-error-handler-styles').length) {
        $('head').append(`
            <style id="custom-error-handler-styles">
                .custom-message.show {
                    opacity: 1 !important;
                    transform: translateX(0) !important;
                }
                
                .custom-message-icon {
                    margin-right: 8px;
                    font-weight: bold;
                }
                
                .custom-message-content {
                    flex: 1;
                }
                
                .custom-message-close {
                    margin-left: 8px;
                    cursor: pointer;
                    font-size: 18px;
                    line-height: 1;
                }
                
                .custom-message-close:hover {
                    opacity: 0.7;
                }
                
                .field-error {
                    border-color: #ff4d4f !important;
                    box-shadow: 0 0 0 2px rgba(255, 77, 79, 0.2) !important;
                }
            </style>
        `);
    }

    // jQuery插件形式
    $.fn.customError = function(message, type, options) {
        return this.each(function() {
            CustomErrorHandler.showMessage(message, type || 'error', options);
        });
    };

})(jQuery);
