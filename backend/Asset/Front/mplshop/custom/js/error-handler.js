/**
 * 前端错误处理器
 * 
 * 提供统一的前端错误处理、用户友好的错误提示和错误日志记录
 * 严格遵循Godomall开发规范
 * 
 * @author Custom Development Team
 * @version 1.0
 */

class CustomErrorHandler {
    constructor() {
        this.errorMessages = {
            'GOODS_NOT_FOUND': '상품을 찾을 수 없습니다',
            'GOODS_NOT_CUSTOMIZABLE': '해당 상품은 맞춤 제작을 지원하지 않습니다',
            'INVALID_GOODS_ID': '유효하지 않은 상품 번호입니다',
            'PATTERN_NOT_FOUND': '패턴을 찾을 수 없습니다',
            'INVALID_PATTERN_ID': '유효하지 않은 패턴 ID입니다',
            'COLOR_NOT_FOUND': '색상을 찾을 수 없습니다',
            'INVALID_COLOR_CODE': '유효하지 않은 색상 코드입니다',
            'SIZE_NOT_FOUND': '크기를 찾을 수 없습니다',
            'INVALID_SIZE_CODE': '유효하지 않은 크기 코드입니다',
            'CUSTOMIZATION_SAVE_FAILED': '맞춤 제작 정보 저장에 실패했습니다',
            'CUSTOMIZATION_NOT_FOUND': '맞춤 제작 정보를 찾을 수 없습니다',
            'INVALID_CUSTOMIZATION_DATA': '유효하지 않은 맞춤 제작 데이터입니다',
            'FILE_UPLOAD_FAILED': '파일 업로드에 실패했습니다',
            'INVALID_FILE_TYPE': '지원하지 않는 파일 형식입니다',
            'FILE_SIZE_EXCEEDED': '파일 크기가 제한을 초과했습니다',
            'CART_ADD_FAILED': '장바구니 추가에 실패했습니다',
            'INVALID_CSRF_TOKEN': '보안 토큰이 유효하지 않습니다',
            'PERMISSION_DENIED': '권한이 없습니다',
            'SYSTEM_ERROR': '시스템 오류가 발생했습니다. 잠시 후 다시 시도해 주세요',
            'NETWORK_ERROR': '네트워크 오류가 발생했습니다. 인터넷 연결을 확인해 주세요',
            'DATABASE_ERROR': '데이터베이스 오류가 발생했습니다. 관리자에게 문의해 주세요',
            'VALIDATION_ERROR': '입력 정보를 확인해 주세요',
            'UNKNOWN_ERROR': '알 수 없는 오류가 발생했습니다'
        };

        this.init();
    }

    /**
     * 初始化错误处理器
     */
    init() {
        // 全局错误捕获
        window.addEventListener('error', (event) => {
            this.handleGlobalError(event.error, event.filename, event.lineno);
        });

        // Promise rejection 捕获
        window.addEventListener('unhandledrejection', (event) => {
            this.handlePromiseRejection(event.reason);
        });

        // AJAX 错误拦截
        this.interceptAjaxErrors();
    }

    /**
     * 处理全局JavaScript错误
     * 
     * @param {Error} error 错误对象
     * @param {string} filename 文件名
     * @param {number} lineno 行号
     */
    handleGlobalError(error, filename, lineno) {
        const errorInfo = {
            message: error.message,
            stack: error.stack,
            filename: filename,
            lineno: lineno,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        };

        this.logError('JavaScript Error', errorInfo);
        this.showUserFriendlyError('SYSTEM_ERROR');
    }

    /**
     * 处理Promise rejection
     * 
     * @param {any} reason rejection原因
     */
    handlePromiseRejection(reason) {
        const errorInfo = {
            reason: reason,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        };

        this.logError('Promise Rejection', errorInfo);
        this.showUserFriendlyError('SYSTEM_ERROR');
    }

    /**
     * 拦截AJAX错误
     */
    interceptAjaxErrors() {
        // 保存原始的fetch方法
        const originalFetch = window.fetch;

        // 重写fetch方法
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);
                
                // 检查HTTP状态码
                if (!response.ok) {
                    const errorInfo = {
                        status: response.status,
                        statusText: response.statusText,
                        url: args[0],
                        timestamp: new Date().toISOString()
                    };
                    
                    this.logError('HTTP Error', errorInfo);
                    this.showUserFriendlyError('NETWORK_ERROR');
                }
                
                return response;
            } catch (error) {
                const errorInfo = {
                    message: error.message,
                    url: args[0],
                    timestamp: new Date().toISOString()
                };
                
                this.logError('Fetch Error', errorInfo);
                this.showUserFriendlyError('NETWORK_ERROR');
                throw error;
            }
        };
    }

    /**
     * 处理API响应错误
     * 
     * @param {Object} response API响应对象
     * @param {string} operation 操作名称
     * @returns {boolean} 是否处理了错误
     */
    handleApiError(response, operation = 'API调用') {
        if (!response || !response.success) {
            const errorCode = response.error_code || 'UNKNOWN_ERROR';
            const message = response.message || this.getErrorMessage(errorCode);
            
            this.logError(operation, {
                errorCode: errorCode,
                message: message,
                response: response,
                timestamp: new Date().toISOString()
            });
            
            this.showUserFriendlyError(errorCode, message);
            return true;
        }
        return false;
    }

    /**
     * 显示用户友好的错误消息
     * 
     * @param {string} errorCode 错误代码
     * @param {string} customMessage 自定义消息
     */
    showUserFriendlyError(errorCode, customMessage = null) {
        const message = customMessage || this.getErrorMessage(errorCode);
        
        // 使用Godomall的提示组件
        if (typeof alert === 'function') {
            alert(message);
        } else if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            // 创建自定义提示框
            this.createCustomAlert(message);
        }
    }

    /**
     * 创建自定义提示框
     * 
     * @param {string} message 消息内容
     */
    createCustomAlert(message) {
        // 移除已存在的提示框
        const existingAlert = document.querySelector('.custom-error-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // 创建提示框元素
        const alertDiv = document.createElement('div');
        alertDiv.className = 'custom-error-alert';
        alertDiv.innerHTML = `
            <div class="alert-content">
                <div class="alert-icon">⚠️</div>
                <div class="alert-message">${message}</div>
                <button class="alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        // 添加样式
        const style = document.createElement('style');
        style.textContent = `
            .custom-error-alert {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                background: #fff;
                border: 1px solid #e74c3c;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            }
            .alert-content {
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .alert-icon {
                font-size: 20px;
                flex-shrink: 0;
            }
            .alert-message {
                flex: 1;
                color: #e74c3c;
                font-size: 14px;
                line-height: 1.4;
            }
            .alert-close {
                background: none;
                border: none;
                font-size: 20px;
                color: #999;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .alert-close:hover {
                color: #666;
            }
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;

        // 添加到页面
        document.head.appendChild(style);
        document.body.appendChild(alertDiv);

        // 自动移除
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    }

    /**
     * 获取错误消息
     * 
     * @param {string} errorCode 错误代码
     * @returns {string} 错误消息
     */
    getErrorMessage(errorCode) {
        return this.errorMessages[errorCode] || this.errorMessages['UNKNOWN_ERROR'];
    }

    /**
     * 记录错误日志
     * 
     * @param {string} operation 操作名称
     * @param {Object} errorInfo 错误信息
     */
    logError(operation, errorInfo) {
        try {
            const logData = {
                operation: operation,
                ...errorInfo,
                sessionId: this.getSessionId(),
                userId: this.getUserId()
            };

            // 发送到服务器日志接口
            this.sendErrorToServer(logData);

            // 本地控制台输出
            console.error(`[CustomErrorHandler] ${operation}:`, logData);

        } catch (error) {
            console.error('Error logging failed:', error);
        }
    }

    /**
     * 发送错误到服务器
     * 
     * @param {Object} logData 日志数据
     */
    async sendErrorToServer(logData) {
        try {
            await fetch('/custom/api/log-error', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(logData)
            });
        } catch (error) {
            // 静默失败，避免日志记录本身导致错误
            console.warn('Failed to send error log to server:', error);
        }
    }

    /**
     * 获取会话ID
     * 
     * @returns {string} 会话ID
     */
    getSessionId() {
        return sessionStorage.getItem('sessionId') || 
               localStorage.getItem('sessionId') || 
               'anonymous_' + Date.now();
    }

    /**
     * 获取用户ID
     * 
     * @returns {string} 用户ID
     */
    getUserId() {
        return sessionStorage.getItem('userId') || 
               localStorage.getItem('userId') || 
               'guest';
    }

    /**
     * 验证输入数据
     * 
     * @param {Object} data 输入数据
     * @param {Object} rules 验证规则
     * @returns {Object} 验证结果
     */
    validateInput(data, rules) {
        const errors = {};

        for (const [field, rule] of Object.entries(rules)) {
            const value = data[field];

            // 必填验证
            if (rule.required && (value === undefined || value === null || value === '')) {
                errors[field] = `${field}는 필수입니다`;
                continue;
            }

            // 类型验证
            if (value !== undefined && value !== null && value !== '') {
                if (rule.type === 'number' && isNaN(Number(value))) {
                    errors[field] = `${field}는 숫자여야 합니다`;
                } else if (rule.type === 'email' && !this.isValidEmail(value)) {
                    errors[field] = `${field}는 유효한 이메일 주소여야 합니다`;
                } else if (rule.type === 'url' && !this.isValidUrl(value)) {
                    errors[field] = `${field}는 유효한 URL이어야 합니다`;
                }
            }

            // 长度验证
            if (rule.maxLength && value && value.length > rule.maxLength) {
                errors[field] = `${field}는 ${rule.maxLength}자를 초과할 수 없습니다`;
            }

            if (rule.minLength && value && value.length < rule.minLength) {
                errors[field] = `${field}는 최소 ${rule.minLength}자 이상이어야 합니다`;
            }
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * 验证邮箱格式
     * 
     * @param {string} email 邮箱地址
     * @returns {boolean} 是否有效
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * 验证URL格式
     * 
     * @param {string} url URL地址
     * @returns {boolean} 是否有效
     */
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * 显示验证错误
     * 
     * @param {Object} errors 错误对象
     */
    showValidationErrors(errors) {
        const errorMessages = Object.values(errors);
        if (errorMessages.length > 0) {
            this.showUserFriendlyError('VALIDATION_ERROR', errorMessages.join('\n'));
        }
    }

    /**
     * 处理文件上传错误
     * 
     * @param {Object} file 文件对象
     * @param {Object} options 选项
     * @returns {Object} 验证结果
     */
    validateFileUpload(file, options = {}) {
        const errors = [];

        // 文件大小验证
        if (options.maxSize && file.size > options.maxSize) {
            errors.push(`파일 크기가 ${this.formatFileSize(options.maxSize)}를 초과합니다`);
        }

        // 文件类型验证
        if (options.allowedTypes && options.allowedTypes.length > 0) {
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (!options.allowedTypes.includes(fileExtension)) {
                errors.push(`지원하지 않는 파일 형식입니다. 허용된 형식: ${options.allowedTypes.join(', ')}`);
            }
        }

        return {
            valid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * 格式化文件大小
     * 
     * @param {number} bytes 字节数
     * @returns {string} 格式化后的大小
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// 创建全局错误处理器实例
window.customErrorHandler = new CustomErrorHandler();

// 导出供其他模块使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CustomErrorHandler;
}
