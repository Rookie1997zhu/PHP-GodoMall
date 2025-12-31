<?php

/**
 * 定制图案存储类
 * 
 * 使用PHP原生文件操作，不依赖Godomall Storage框架
 * 提供简单可靠的文件上传、删除、验证功能
 * 
 * @package Custom\Component\Storage
 * @author Custom Development Team
 * @version 2.0
 */

namespace Custom\Component\Storage;

use Exception;
use Logger;

/**
 * 定制图案存储类（独立实现）
 */
class CustomStorage
{
    /**
     * 自定义图案基础目录（相对于项目根目录）
     */
    const BASE_DIRECTORY = 'data/upload/goods';

    /**
     * 临时文件目录（相对于项目根目录）
     */
    const TMP_DIRECTORY = 'tmp';

    /**
     * 获取完整的存储路径
     * 
     * @param string $relativePath 相对路径
     * @return string 完整路径
     */
    private static function getFullPath($relativePath)
    {
        return _ROOT_PATH_ . '/' . self::BASE_DIRECTORY . '/' . ltrim($relativePath, '/');
    }

    /**
     * 获取HTTP访问路径
     * 
     * @param string $relativePath 相对路径
     * @return string HTTP路径
     */
    private static function getHttpPath($relativePath)
    {
        return '/' . self::BASE_DIRECTORY . '/' . ltrim($relativePath, '/');
    }

    /**
     * 上传定制图案文件
     * 
     * @param string $filePath 临时文件路径
     * @param string $fileName 目标文件名（可包含子目录，如 colors/123/file.svg）
     * @param array $options 上传选项（保留参数兼容性，但不使用）
     * @return string|false 上传成功返回HTTP访问路径，失败返回false
     */
    public static function uploadCustomPattern($filePath, $fileName, $options = [])
    {
        try {
            Logger::info('开始上传定制图案', [
                'source' => $filePath,
                'target_name' => $fileName
            ]);

            // 验证源文件存在
            if (!file_exists($filePath)) {
                Logger::error('源文件不存在', ['file_path' => $filePath]);
                return false;
            }

            // 生成唯一文件名
            $pathInfo = pathinfo($fileName);
            $directory = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' 
                ? $pathInfo['dirname'] 
                : '';
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
            $basename = $pathInfo['filename'];
            
            // 添加时间戳和随机数确保唯一性
            $uniqueName = $basename . '_' . time() . '_' . mt_rand(1000, 9999);
            if ($extension) {
                $uniqueName .= '.' . $extension;
            }

            // 构建完整目标路径
            $relativePath = $directory ? $directory . '/' . $uniqueName : $uniqueName;
            $targetPath = self::getFullPath($relativePath);
            $targetDir = dirname($targetPath);

            Logger::info('目标路径信息', [
                'relative_path' => $relativePath,
                'target_path' => $targetPath,
                'target_dir' => $targetDir
            ]);

            // 确保目标目录存在
            if (!is_dir($targetDir)) {
                if (!@mkdir($targetDir, 0777, true)) {
                    Logger::error('无法创建目标目录', [
                        'dir' => $targetDir,
                        'error' => error_get_last()
                    ]);
                    return false;
                }
                Logger::info('创建目标目录成功', ['dir' => $targetDir]);
            }

            // 复制文件到目标位置
            if (!@copy($filePath, $targetPath)) {
                Logger::error('文件复制失败', [
                    'source' => $filePath,
                    'target' => $targetPath,
                    'error' => error_get_last()
                ]);
                return false;
            }

            // 设置文件权限
            @chmod($targetPath, 0644);

            // 验证文件已成功写入
            if (!file_exists($targetPath)) {
                Logger::error('文件写入后不存在', ['target' => $targetPath]);
                return false;
            }

            $httpPath = self::getHttpPath($relativePath);
            
            Logger::info('定制图案上传成功', [
                'target_path' => $targetPath,
                'http_path' => $httpPath,
                'file_size' => filesize($targetPath)
            ]);

            return $httpPath;

        } catch (Exception $e) {
            Logger::error('定制图案上传异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 删除定制图案文件
     * 
     * @param string $fileName 文件名（相对路径或HTTP路径）
     * @return bool 删除成功返回true，失败返回false
     */
    public static function deleteCustomPattern($fileName)
    {
        try {
            // 清理路径，移除基础目录前缀（如果有）
            $relativePath = str_replace(self::BASE_DIRECTORY . '/', '', ltrim($fileName, '/'));
            $fullPath = self::getFullPath($relativePath);

            Logger::info('删除定制图案', [
                'file_name' => $fileName,
                'full_path' => $fullPath
            ]);

            if (!file_exists($fullPath)) {
                Logger::warning('文件不存在，视为删除成功', ['path' => $fullPath]);
                return true;
            }

            if (@unlink($fullPath)) {
                Logger::info('定制图案删除成功', ['path' => $fullPath]);
                
                // 尝试删除空目录
                $dir = dirname($fullPath);
                if (is_dir($dir) && count(scandir($dir)) === 2) { // 只有 . 和 ..
                    @rmdir($dir);
                }
                
                return true;
            } else {
                Logger::error('定制图案删除失败', [
                    'path' => $fullPath,
                    'error' => error_get_last()
                ]);
                return false;
            }

        } catch (Exception $e) {
            Logger::error('定制图案删除异常', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 检查定制图案文件是否存在
     * 
     * @param string $fileName 文件名（相对路径或HTTP路径）
     * @return bool 文件存在返回true，不存在返回false
     */
    public static function customPatternExists($fileName)
    {
        try {
            $relativePath = str_replace(self::BASE_DIRECTORY . '/', '', ltrim($fileName, '/'));
            $fullPath = self::getFullPath($relativePath);
            return file_exists($fullPath);
        } catch (Exception $e) {
            Logger::error('检查文件存在性异常', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 获取定制图案文件URL
     * 
     * @param string $fileName 文件名（相对路径）
     * @return string|false 成功返回HTTP路径，失败返回false
     */
    public static function getCustomPatternUrl($fileName)
    {
        try {
            if (self::customPatternExists($fileName)) {
                $relativePath = str_replace(self::BASE_DIRECTORY . '/', '', ltrim($fileName, '/'));
                return self::getHttpPath($relativePath);
            }
            return false;
        } catch (Exception $e) {
            Logger::error('获取文件URL异常', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 生成唯一文件名（保留原扩展名）
     * 
     * @param string $originalName 原始文件名
     * @return string 唯一文件名
     */
    public static function generateUniqueFileName($originalName)
    {
        $pathInfo = pathinfo($originalName);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'];
        
        $uniqueName = $basename . '_' . time() . '_' . mt_rand(1000, 9999);
        if ($extension) {
            $uniqueName .= '.' . $extension;
        }
        
        return $uniqueName;
    }

    /**
     * 获取定制图案存储目录信息
     * 
     * @param string $subDir 子目录（可选）
     * @return array 目录信息
     */
    public static function getDirectoryInfo($subDir = '')
    {
        try {
            $relativePath = $subDir ? $subDir : '';
            $fullPath = self::getFullPath($relativePath);
            
            $info = [
                'path' => $fullPath,
                'exists' => is_dir($fullPath),
                'writable' => is_writable($fullPath),
                'files' => []
            ];
            
            if ($info['exists']) {
                $files = scandir($fullPath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $fullPath . '/' . $file;
                        $info['files'][] = [
                            'name' => $file,
                            'size' => filesize($filePath),
                            'type' => is_dir($filePath) ? 'dir' : 'file',
                            'modified' => filemtime($filePath)
                        ];
                    }
                }
            }
            
            return $info;
            
        } catch (Exception $e) {
            Logger::error('获取目录信息异常', [
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 批量上传定制图案文件
     * 
     * @param array $files 文件数组，每个元素包含 ['path' => '...', 'name' => '...']
     * @return array 上传结果 ['success' => [...], 'failed' => [...]]
     */
    public static function batchUploadCustomPattern($files)
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($files as $file) {
            $filePath = isset($file['path']) ? $file['path'] : '';
            $fileName = isset($file['name']) ? $file['name'] : '';

            if (empty($filePath) || empty($fileName)) {
                $results['failed'][] = [
                    'name' => $fileName,
                    'error' => '文件路径或名称为空'
                ];
                continue;
            }

            $result = self::uploadCustomPattern($filePath, $fileName);
            
            if ($result) {
                $results['success'][] = [
                    'name' => $fileName,
                    'url' => $result
                ];
            } else {
                $results['failed'][] = [
                    'name' => $fileName,
                    'error' => '上传失败'
                ];
            }
        }

        Logger::info('批量上传定制图案完成', [
            'total' => count($files),
            'success' => count($results['success']),
            'failed' => count($results['failed'])
        ]);

        return $results;
    }

    /**
     * 验证文件是否为有效的定制图案文件
     * 
     * @param string $filePath 文件路径
     * @param string $originalName 原始文件名
     * @param string $originalMimeType 原始MIME类型
     * @return array 验证结果 ['valid' => bool, 'message' => string, 'file_info' => array]
     */
    public static function validateCustomerPatternFile($filePath, $originalName = '', $originalMimeType = '')
    {
        $result = [
            'valid' => false,
            'message' => '',
            'file_info' => []
        ];
        
        try {
            // 检查文件是否存在
            if (!file_exists($filePath)) {
                $result['message'] = '文件不存在';
                return $result;
            }
            
            // 检查文件大小（最大20MB）
            $fileSize = filesize($filePath);
            if ($fileSize > 20 * 1024 * 1024) {
                $result['message'] = '文件大小超过20MB限制';
                return $result;
            }
            
            if ($fileSize === 0) {
                $result['message'] = '文件大小为0';
                return $result;
            }
            
            // 获取文件扩展名
            $extension = '';
            if (!empty($originalName)) {
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            }
            
            // 验证通过
            $result['valid'] = true;
            $result['message'] = '文件验证通过';
            $result['file_info'] = [
                'size' => $fileSize,
                'extension' => $extension,
                'mime_type' => $originalMimeType
            ];
            
            Logger::info('文件验证通过', $result['file_info']);
            
        } catch (Exception $e) {
            $result['message'] = '验证过程出错: ' . $e->getMessage();
            Logger::error('文件验证异常', ['error' => $e->getMessage()]);
        }
        
        return $result;
    }

    /**
     * 清理临时文件
     * 
     * @param int $olderThan 清理多少秒之前的临时文件（默认3600秒=1小时）
     * @return int 清理的文件数量
     */
    public static function cleanTempFiles($olderThan = 3600)
    {
        try {
            $tmpDir = _ROOT_PATH_ . '/' . self::TMP_DIRECTORY;
            if (!is_dir($tmpDir)) {
                return 0;
            }

            $count = 0;
            $files = glob($tmpDir . '/custom_upload_*');
            $now = time();

            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > $olderThan)) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            }

            if ($count > 0) {
                Logger::info('清理临时文件', ['count' => $count]);
            }

            return $count;

        } catch (Exception $e) {
            Logger::error('清理临时文件异常', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 验证文件是否为有效的图片格式（简化版，保留兼容性）
     * 
     * @param string $filePath 文件路径
     * @return bool 有效返回true，无效返回false
     */
    public static function validateImageFile($filePath)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        // 检查文件是否存在
        if (!file_exists($filePath)) {
            return false;
        }
        
        // 检查文件大小（最大10MB）
        if (filesize($filePath) > 10 * 1024 * 1024) {
            return false;
        }
        
        // 检查文件扩展名
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }
        
        return true;
    }
}
