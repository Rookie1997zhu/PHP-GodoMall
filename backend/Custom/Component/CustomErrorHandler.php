<?php

/**
 * 自定义错误处理器
 * 
 * 提供统一的错误处理、日志记录和用户友好的错误消息
 * 严格遵循Godomall开发规范
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Component;

use Exception;
use Logger;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;

/**
 * 自定义错误处理器
 */
class CustomErrorHandler
{
    /**
     * 错误级别常量
     */
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';

    /**
     * 错误类型常量
     */
    const TYPE_VALIDATION = 'VALIDATION';
    const TYPE_DATABASE = 'DATABASE';
    const TYPE_FILE = 'FILE';
    const TYPE_NETWORK = 'NETWORK';
    const TYPE_SYSTEM = 'SYSTEM';
    const TYPE_BUSINESS = 'BUSINESS';

    /**
     * 记录错误日志
     * 
     * @param string $module 模块名称
     * @param string $message 错误消息
     * @param array $context 上下文信息
     * @param string $level 错误级别
     * @param string $type 错误类型
     * @return void
     */
    public function logError($module, $message, $context = [], $level = self::LEVEL_ERROR, $type = self::TYPE_SYSTEM)
    {
        try {
            $logData = [
                'module' => $module,
                'message' => $message,
                'level' => $level,
                'type' => $type,
                'context' => $context,
                'timestamp' => date('Y-m-d H:i:s'),
                'request_id' => $this->generateRequestId(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->getClientIp(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? ''
            ];

            // 根据错误级别选择日志方法
            switch ($level) {
                case self::LEVEL_ERROR:
                    Logger::error($message, $logData);
                    break;
                case self::LEVEL_WARNING:
                    Logger::warning($message, $logData);
                    break;
                case self::LEVEL_INFO:
                    Logger::info($message, $logData);
                    break;
                case self::LEVEL_DEBUG:
                    Logger::debug($message, $logData);
                    break;
                default:
                    Logger::error($message, $logData);
            }

            // 记录到自定义错误日志文件
            $this->writeToCustomLog($logData);

        } catch (Exception $e) {
            // 如果日志记录失败，至少记录到系统日志
            error_log("CustomErrorHandler::logError failed: " . $e->getMessage());
        }
    }

    /**
     * 记录验证错误
     * 
     * @param string $module 模块名称
     * @param string $field 字段名
     * @param string $message 错误消息
     * @param array $context 上下文信息
     * @return void
     */
    public function logValidationError($module, $field, $message, $context = [])
    {
        $context['field'] = $field;
        $this->logError($module, "验证失败: {$message}", $context, self::LEVEL_WARNING, self::TYPE_VALIDATION);
    }

    /**
     * 记录数据库错误
     * 
     * @param string $module 模块名称
     * @param string $operation 操作类型
     * @param string $message 错误消息
     * @param array $context 上下文信息
     * @return void
     */
    public function logDatabaseError($module, $operation, $message, $context = [])
    {
        $context['operation'] = $operation;
        $this->logError($module, "数据库操作失败: {$message}", $context, self::LEVEL_ERROR, self::TYPE_DATABASE);
    }

    /**
     * 记录文件操作错误
     * 
     * @param string $module 模块名称
     * @param string $operation 操作类型
     * @param string $filePath 文件路径
     * @param string $message 错误消息
     * @param array $context 上下文信息
     * @return void
     */
    public function logFileError($module, $operation, $filePath, $message, $context = [])
    {
        $context['operation'] = $operation;
        $context['file_path'] = $filePath;
        $this->logError($module, "文件操作失败: {$message}", $context, self::LEVEL_ERROR, self::TYPE_FILE);
    }

    /**
     * 记录业务逻辑错误
     * 
     * @param string $module 模块名称
     * @param string $operation 操作类型
     * @param string $message 错误消息
     * @param array $context 上下文信息
     * @return void
     */
    public function logBusinessError($module, $operation, $message, $context = [])
    {
        $context['operation'] = $operation;
        $this->logError($module, "业务逻辑错误: {$message}", $context, self::LEVEL_WARNING, self::TYPE_BUSINESS);
    }

    /**
     * 获取用户友好的错误消息
     * 
     * @param string $errorCode 错误代码
     * @param array $params 参数
     * @return string
     */
    public function getUserFriendlyMessage($errorCode, $params = [])
    {
        $messages = [
            'GOODS_NOT_FOUND' => '상품을 찾을 수 없습니다',
            'GOODS_NOT_CUSTOMIZABLE' => '해당 상품은 맞춤 제작을 지원하지 않습니다',
            'INVALID_GOODS_ID' => '유효하지 않은 상품 번호입니다',
            'PATTERN_NOT_FOUND' => '패턴을 찾을 수 없습니다',
            'INVALID_PATTERN_ID' => '유효하지 않은 패턴 ID입니다',
            'COLOR_NOT_FOUND' => '색상을 찾을 수 없습니다',
            'INVALID_COLOR_CODE' => '유효하지 않은 색상 코드입니다',
            'SIZE_NOT_FOUND' => '크기를 찾을 수 없습니다',
            'INVALID_SIZE_CODE' => '유효하지 않은 크기 코드입니다',
            'CUSTOMIZATION_SAVE_FAILED' => '맞춤 제작 정보 저장에 실패했습니다',
            'CUSTOMIZATION_NOT_FOUND' => '맞춤 제작 정보를 찾을 수 없습니다',
            'INVALID_CUSTOMIZATION_DATA' => '유효하지 않은 맞춤 제작 데이터입니다',
            'FILE_UPLOAD_FAILED' => '파일 업로드에 실패했습니다',
            'INVALID_FILE_TYPE' => '지원하지 않는 파일 형식입니다',
            'FILE_SIZE_EXCEEDED' => '파일 크기가 제한을 초과했습니다',
            'CART_ADD_FAILED' => '장바구니 추가에 실패했습니다',
            'INVALID_CSRF_TOKEN' => '보안 토큰이 유효하지 않습니다',
            'PERMISSION_DENIED' => '권한이 없습니다',
            'SYSTEM_ERROR' => '시스템 오류가 발생했습니다. 잠시 후 다시 시도해 주세요',
            'NETWORK_ERROR' => '네트워크 오류가 발생했습니다. 인터넷 연결을 확인해 주세요',
            'DATABASE_ERROR' => '데이터베이스 오류가 발생했습니다. 관리자에게 문의해 주세요',
            'VALIDATION_ERROR' => '입력 정보를 확인해 주세요',
            'UNKNOWN_ERROR' => '알 수 없는 오류가 발생했습니다'
        ];

        $message = $messages[$errorCode] ?? $messages['UNKNOWN_ERROR'];

        // 替换参数
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }

        return $message;
    }

    /**
     * 处理异常并返回标准错误响应
     * 
     * @param Exception $exception 异常对象
     * @param string $module 模块名称
     * @param array $context 上下文信息
     * @return array
     */
    public function handleException(Exception $exception, $module, $context = [])
    {
        $errorCode = $this->getErrorCodeFromException($exception);
        $userMessage = $this->getUserFriendlyMessage($errorCode);
        
        // 记录详细错误信息
        $this->logError($module, $exception->getMessage(), array_merge($context, [
            'error_code' => $errorCode,
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]));

        return [
            'success' => false,
            'message' => $userMessage,
            'data' => null,
            'error_code' => $errorCode
        ];
    }

    /**
     * 验证输入参数
     * 
     * @param array $data 输入数据
     * @param array $rules 验证规则
     * @param string $module 模块名称
     * @return array 验证结果
     */
    public function validateInput($data, $rules, $module)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // 必填验证
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $this->getUserFriendlyMessage('VALIDATION_ERROR') . ": {$field}는 필수입니다";
                $this->logValidationError($module, $field, "필수 필드 누락", ['value' => $value]);
                continue;
            }

            // 类型验证
            if (!empty($value) && isset($rule['type'])) {
                if (!$this->validateType($value, $rule['type'])) {
                    $errors[$field] = $this->getUserFriendlyMessage('VALIDATION_ERROR') . ": {$field}의 형식이 올바르지 않습니다";
                    $this->logValidationError($module, $field, "타입 검증 실패", ['value' => $value, 'expected_type' => $rule['type']]);
                }
            }

            // 长度验证
            if (!empty($value) && isset($rule['max_length'])) {
                if (strlen($value) > $rule['max_length']) {
                    $errors[$field] = $this->getUserFriendlyMessage('VALIDATION_ERROR') . ": {$field}는 {$rule['max_length']}자를 초과할 수 없습니다";
                    $this->logValidationError($module, $field, "길이 제한 초과", ['value' => $value, 'max_length' => $rule['max_length']]);
                }
            }

            // 数值范围验证
            if (!empty($value) && isset($rule['min']) && isset($rule['max'])) {
                if ($value < $rule['min'] || $value > $rule['max']) {
                    $errors[$field] = $this->getUserFriendlyMessage('VALIDATION_ERROR') . ": {$field}는 {$rule['min']}~{$rule['max']} 범위여야 합니다";
                    $this->logValidationError($module, $field, "범위 검증 실패", ['value' => $value, 'min' => $rule['min'], 'max' => $rule['max']]);
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 验证数据类型
     * 
     * @param mixed $value 值
     * @param string $type 类型
     * @return bool
     */
    private function validateType($value, $type)
    {
        switch ($type) {
            case 'int':
                return is_numeric($value) && (int)$value == $value;
            case 'float':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'json':
                json_decode($value);
                return json_last_error() === JSON_ERROR_NONE;
            default:
                return true;
        }
    }

    /**
     * 从异常获取错误代码
     * 
     * @param Exception $exception 异常对象
     * @return string
     */
    private function getErrorCodeFromException(Exception $exception)
    {
        $message = $exception->getMessage();
        
        if (strpos($message, '商品不存在') !== false) {
            return 'GOODS_NOT_FOUND';
        } elseif (strpos($message, '不支持定制') !== false) {
            return 'GOODS_NOT_CUSTOMIZABLE';
        } elseif (strpos($message, 'CSRF') !== false) {
            return 'INVALID_CSRF_TOKEN';
        } elseif (strpos($message, '数据库') !== false) {
            return 'DATABASE_ERROR';
        } elseif (strpos($message, '文件') !== false) {
            return 'FILE_UPLOAD_FAILED';
        } else {
            return 'SYSTEM_ERROR';
        }
    }

    /**
     * 生成请求ID
     * 
     * @return string
     */
    private function generateRequestId()
    {
        return uniqid('req_', true);
    }

    /**
     * 获取客户端IP地址
     * 
     * @return string
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * 写入自定义错误日志文件
     * 
     * @param array $logData 日志数据
     * @return void
     */
    private function writeToCustomLog($logData)
    {
        try {
            $logDir = _ROOT_PATH_ . '/data/logs/custom/';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = $logDir . 'custom_errors_' . date('Y-m-d') . '.log';
            $logLine = date('Y-m-d H:i:s') . ' [' . $logData['level'] . '] ' . 
                      $logData['module'] . ': ' . $logData['message'] . 
                      ' | Context: ' . json_encode($logData['context'], JSON_UNESCAPED_UNICODE) . 
                      ' | Request: ' . $logData['request_id'] . PHP_EOL;

            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // 静默失败，避免日志记录本身导致错误
        }
    }

    /**
     * 清理旧日志文件
     * 
     * @param int $days 保留天数
     * @return void
     */
    public function cleanOldLogs($days = 30)
    {
        try {
            $logDir = _ROOT_PATH_ . '/data/logs/custom/';
            if (!is_dir($logDir)) {
                return;
            }

            $files = glob($logDir . 'custom_errors_*.log');
            $cutoffTime = time() - ($days * 24 * 60 * 60);

            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
        } catch (Exception $e) {
            $this->logError('CustomErrorHandler', '清理旧日志失败', ['error' => $e->getMessage()]);
        }
    }
}
