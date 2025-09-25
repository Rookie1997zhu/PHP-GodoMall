<?php

/**
 * 定制图案存储类
 * 
 * 本类扩展官方Storage类，为定制图案功能提供专门的存储路径和管理方法
 * 严格遵循Godomall开发规范，基于继承实现功能扩展
 * 
 * @package Component\Storage
 * @author Custom Development Team
 * @version 1.0
 */

namespace Component\Storage;

use Bundle\Component\Storage\Storage;
use Bundle\Component\Storage\LocalStorage;
use Framework\StaticProxy\Proxy\UserFilePath;
use Exception;
use Logger;

/**
 * 定制图案存储类
 */
class CustomStorage extends Storage
{
    /**
     * 定制图案存储路径代码
     */
    const PATH_CODE_CUSTOM_PATTERN = 32;

    /**
     * 获取定制图案存储路径
     * 
     * @param string $pathCodeDirName 路径代码目录名
     * @return string 存储路径
     */
    public function getPath($pathCodeDirName)
    {
        return UserFilePath::data('custom_pattern');
    }

    /**
     * 获取定制图案存储磁盘实例
     * 
     * @param string $storageName 存储名称，默认为local
     * @param bool $isSetAdapter 是否设置适配器
     * @return LocalStorage 存储实例
     * @throws Exception
     */
    public static function getCustomPatternDisk($storageName = 'local', $isSetAdapter = false)
    {
        return self::disk(self::PATH_CODE_CUSTOM_PATTERN, $storageName, $isSetAdapter);
    }

    /**
     * 上传定制图案文件
     * 
     * @param string $filePath 临时文件路径
     * @param string $fileName 文件名
     * @param array $options 上传选项
     * @return string|false 上传成功返回文件URL，失败返回false
     */
    public static function uploadCustomPattern($filePath, $fileName, $options = [])
    {
        try {
            $storage = self::getCustomPatternDisk();
            
            // 生成唯一文件名
            $uniqueFileName = self::generateUniqueFileName($fileName);
            
            // 设置默认选项
            $defaultOptions = [
                'width' => 0,
                'height' => 0,
                'quality' => 'high',
                'overWrite' => false
            ];
            
            $uploadOptions = array_merge($defaultOptions, $options);
            
            // 执行上传
            $result = $storage->upload($filePath, $uniqueFileName, $uploadOptions);
            
            if ($result) {
                Logger::info('定制图案上传成功', [
                    'original_name' => $fileName,
                    'saved_name' => $uniqueFileName,
                    'url' => $result
                ]);
                return $result;
            } else {
                Logger::error('定制图案上传失败', [
                    'original_name' => $fileName,
                    'file_path' => $filePath
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            Logger::error('定制图案上传异常', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 删除定制图案文件
     * 
     * @param string $fileName 文件名
     * @return bool 删除成功返回true，失败返回false
     */
    public static function deleteCustomPattern($fileName)
    {
        try {
            $storage = self::getCustomPatternDisk();
            
            if ($storage->isFileExists($fileName)) {
                $result = $storage->delete($fileName);
                
                if ($result) {
                    Logger::info('定制图案删除成功', ['file_name' => $fileName]);
                    return true;
                } else {
                    Logger::error('定制图案删除失败', ['file_name' => $fileName]);
                    return false;
                }
            } else {
                Logger::warning('定制图案文件不存在', ['file_name' => $fileName]);
                return true; // 文件不存在也算删除成功
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
     * @param string $fileName 文件名
     * @return bool 文件存在返回true，不存在返回false
     */
    public static function customPatternExists($fileName)
    {
        try {
            $storage = self::getCustomPatternDisk();
            return $storage->isFileExists($fileName);
        } catch (Exception $e) {
            Logger::error('检查定制图案文件存在性异常', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 获取定制图案文件URL
     * 
     * @param string $fileName 文件名
     * @return string|false 文件URL或false
     */
    public static function getCustomPatternUrl($fileName)
    {
        try {
            $storage = self::getCustomPatternDisk();
            
            if ($storage->isFileExists($fileName)) {
                return $storage->getHttpPath($fileName);
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            Logger::error('获取定制图案URL异常', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return false;
        }
    }

    /**
     * 生成唯一文件名
     * 
     * @param string $originalName 原始文件名
     * @return string 唯一文件名
     */
    private static function generateUniqueFileName($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        
        // 清理文件名，只保留字母、数字、下划线
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        
        // 生成时间戳和随机数
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $name . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * 获取定制图案存储目录信息
     * 
     * @return array 目录信息
     */
    public static function getCustomPatternDirectoryInfo()
    {
        try {
            $storage = self::getCustomPatternDisk();
            
            return [
                'base_path' => $storage->getBasePath(),
                'real_path' => $storage->getRealPath(''),
                'http_path' => $storage->getHttpPath(''),
                'storage_name' => $storage->storageName
            ];
            
        } catch (Exception $e) {
            Logger::error('获取定制图案目录信息异常', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 批量上传定制图案
     * 
     * @param array $files 文件数组，每个元素包含filePath和fileName
     * @param array $options 上传选项
     * @return array 上传结果
     */
    public static function batchUploadCustomPattern($files, $options = [])
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($files as $file) {
            if (isset($file['filePath']) && isset($file['fileName'])) {
                $result = self::uploadCustomPattern($file['filePath'], $file['fileName'], $options);
                
                if ($result) {
                    $results['success'][] = [
                        'original_name' => $file['fileName'],
                        'url' => $result
                    ];
                } else {
                    $results['failed'][] = [
                        'original_name' => $file['fileName'],
                        'error' => '上传失败'
                    ];
                }
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
     * 验证文件是否为有效的图片格式
     * 
     * @param string $filePath 文件路径
     * @return bool 有效返回true，无效返回false
     */
    public static function validateImageFile($filePath)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
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
        
        // 检查MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // SVG文件特殊处理 - 不需要getimagesize验证
        if ($extension === 'svg') {
            // 验证SVG文件基本格式
            $svgContent = file_get_contents($filePath);
            if (strpos($svgContent, '<svg') === false || strpos($svgContent, '</svg>') === false) {
                return false;
            }
            return true;
        }
        
        // 验证其他图片文件完整性
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }
        
        return true;
    }
} 