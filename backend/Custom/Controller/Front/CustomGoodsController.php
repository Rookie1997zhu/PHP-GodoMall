<?php

/**
 * 前台商品定制控制器
 * 
 * 本控制器提供前台商品定制页面的接口，包括商品定制信息获取、定制保存等功能
 * 严格遵循Godomall开发规范，继承官方Controller
 * 
 * @package Controller\Front\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Front\Custom;

use Custom\Component\CustomGoods;
use Custom\Component\CustomPattern;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 前台商品定制控制器
 */
class CustomGoodsController extends \Controller\Front\Controller
{
    /**
     * 商品定制组件
     * @var CustomGoods
     */
    private $customGoods;

    /**
     * 定制图案组件
     * @var CustomPattern
     */
    private $customPattern;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->customGoods = new CustomGoods();
        $this->customPattern = new CustomPattern();
    }

    /**
     * 商品定制页面
     */
    public function index()
    {
        try {
            $goodsNo = Request::get()->get('goodsNo');
            
            if (!$goodsNo) {
                throw new AlertBackException('商品编号不能为空');
            }

            // 获取商品基本信息
            $goodsInfo = $this->getGoodsInfo($goodsNo);
            if (!$goodsInfo) {
                throw new AlertBackException('商品不存在');
            }

            // 检查商品是否支持定制
            if (!$goodsInfo['is_customizable']) {
                throw new AlertBackException('该商品不支持定制功能');
            }

            // 获取商品定制图案
            $patternsResult = $this->customGoods->getGoodsPatterns($goodsNo, ['status' => 1]);
            $patterns = $patternsResult['success'] ? $patternsResult['data']['patterns'] : [];

            // 获取商品颜色选项
            $colors = $this->getGoodsColors($goodsNo);

            // 获取商品尺寸选项
            $sizes = $this->getGoodsSizes($goodsNo);

            // 准备模板数据
            $templateData = [
                'goods_info' => $goodsInfo,
                'patterns' => $patterns,
                'colors' => $colors,
                'sizes' => $sizes,
                'can_customize' => true,
                'max_upload_size' => 5 * 1024 * 1024, // 5MB
                'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif']
            ];

            // 设置模板变量
            $this->setData('goods_id', $goodsInfo['goodsNo']);
            $this->setData('goods_name', $goodsInfo['goodsNm']);
            $this->setData('goods_price_display', number_format($goodsInfo['goodsPrice']) . '원');
            $this->setData('goods_base_price', number_format($goodsInfo['goodsPrice']) . '원');
            $this->setData('goods_rating', $goodsInfo['rating'] ?? '4.5');
            $this->setData('goods_review_count', $goodsInfo['review_count'] ?? '0');
            $this->setData('goods_image_url', $goodsInfo['goods_image_url']);
            $this->setData('can_customize', $goodsInfo['is_customizable']);
            $this->setData('max_upload_size', 5 * 1024 * 1024);
            $this->setData('allowed_image_types', ['jpg', 'jpeg', 'png', 'gif']);
            
            // 设置颜色和尺寸数据
            $this->setData('goods_colors', $this->buildColorsHtml($colors));
            $this->setData('goods_sizes', $this->buildSizesHtml($sizes));
            $this->setData('selected_color_name', $goodsInfo['default_color'] ?? 'Navy');
            
            // 设置图案数据 - 修复字段映射
            $formattedPatterns = array_map(function($pattern) {
                return [
                    'id' => $pattern['pattern_id'],
                    'url' => $pattern['pattern_url'],
                    'name' => $pattern['pattern_name'],
                    'category' => $pattern['pattern_category'] ?? 'default',
                    'thumbnail' => $pattern['pattern_thumbnail'] ?? $pattern['pattern_url'],
                    'description' => $pattern['description'] ?? '',
                    'tags' => $pattern['tags'] ?? ''
                ];
            }, $patterns);
            
            $this->setData('patterns_data', json_encode($formattedPatterns));
            $this->setData('has_patterns', !empty($patterns));
            
            // 设置CSRF令牌
            $this->setData('csrf_token', Session::get('csrf_token') ?: '');
            
            $this->setData('page_title', '상품 맞춤 제작 - ' . $goodsInfo['goodsNm']);
            
            // 设置模板页面名称（Godomall规范要求）
            $this->getView()->setPageName('custom/goods_custom.html');

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error('商品定制页面异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            throw new AlertBackException('加载商品定制页面失败：' . $e->getMessage());
        }
    }

    /**
     * 获取商品所有尺寸（AJAX）
     * 
     * @return void
     */
    public function getGoodsAllSizes()
    {
        try {
            $this->validateCsrfToken();

            $postData = Request::post()->toArray();
            $goodsNo = $postData['goods_id'] ?? null;
            
            if (!$goodsNo) {
                $this->json([
                    'success' => false,
                    'message' => '상품 번호가 필요합니다'
                ]);
                return;
            }

            $result = $this->customGoods->getGoodsAllSizes($goodsNo);
            
            if ($result['success']) {
                $this->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('상품 크기 조회 예외', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            
            $this->json([
                'success' => false,
                'message' => '크기 정보를 가져오는 중 오류가 발생했습니다'
            ]);
        }
    }

    /**
     * 获取商品定制图案（AJAX）
     */
    public function getPatterns()
    {
        try {
            $goodsNo = Request::get()->get('goodsNo');
            
            if (!$goodsNo) {
                throw new Exception('商品编号不能为空');
            }

            $patternsResult = $this->customGoods->getGoodsPatterns($goodsNo, ['status' => 1]);
            
            if ($patternsResult['success']) {
                // 修复字段映射
                $formattedPatterns = array_map(function($pattern) {
                    return [
                        'id' => $pattern['pattern_id'],
                        'url' => $pattern['pattern_url'],
                        'name' => $pattern['pattern_name'],
                        'category' => $pattern['pattern_category'] ?? 'default',
                        'thumbnail' => $pattern['pattern_thumbnail'] ?? $pattern['pattern_url'],
                        'description' => $pattern['description'] ?? '',
                        'tags' => $pattern['pattern_tags'] ?? ''
                    ];
                }, $patternsResult['data']['patterns']);
                
                echo json_encode([
                    'success' => true,
                    'data' => $formattedPatterns
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $patternsResult['message']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('获取商品图案异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '获取图案失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 保存定制数据（AJAX）
     */
    public function saveCustomization()
    {
        try {
            $this->validateCsrfToken();

            $postData = Request::post()->toArray();
            
            if (empty($postData['goods_no']) || empty($postData['customization_data'])) {
                throw new Exception('参数不完整');
            }

            $customizationData = [
                'goods_no' => $postData['goods_no'],
                'customization_data' => $postData['customization_data'],
                'selected_color' => $postData['selected_color'] ?? null,
                'selected_size' => $postData['selected_size'] ?? null,
                'user_id' => Session::get('member.sno') ?? 0
            ];

            $result = $this->customGoods->saveCustomization($customizationData);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => '定制数据保存成功',
                    'customization_id' => $result['data']['customization_id']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('保存定制数据异常', [
                'error' => $e->getMessage(),
                'post_data' => $postData ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '保存失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 添加到购物车（AJAX）
     */
    public function addToCart()
    {
        try {
            $this->validateCsrfToken();

            $postData = Request::post()->toArray();
            
            if (empty($postData['goods_no']) || empty($postData['quantity'])) {
                throw new Exception('参数不完整');
            }

            $cartData = [
                'goods_no' => $postData['goods_no'],
                'quantity' => $postData['quantity'],
                'customization_data' => $postData['customization_data'] ?? null,
                'selected_color' => $postData['selected_color'] ?? null,
                'selected_size' => $postData['selected_size'] ?? null,
                'user_id' => Session::get('member.sno') ?? 0
            ];

            $result = $this->customGoods->addCustomGoodsToCart($cartData);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => '定制商品已添加到购物车',
                    'cart_item_id' => $result['data']['cart_item_id']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('添加到购物车异常', [
                'error' => $e->getMessage(),
                'post_data' => $postData ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '添加到购物车失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 上传定制图片（AJAX）
     */
    public function uploadImage()
    {
        try {
            $this->validateCsrfToken();

            $files = Request::files()->toArray();
            
            if (empty($files['custom_image'])) {
                throw new Exception('未选择图片文件');
            }

            $file = $files['custom_image'];
            
            // 验证文件
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败');
            }

            $result = $this->customGoods->uploadCustomImage($file);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => '图片上传成功',
                    'image_url' => $result['data']['image_url'],
                    'thumbnail_url' => $result['data']['thumbnail_url']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (Exception $e) {
            Logger::error('上传定制图片异常', [
                'error' => $e->getMessage(),
                'files' => $files ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '图片上传失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品基本信息
     * 
     * @param int $goodsNo 商品编号
     * @return array|null
     */
    private function getGoodsInfo($goodsNo)
    {
        try {
            $arrBind = [];
            $this->db->bind_param_push($arrBind, 'i', $goodsNo);
            
            $sql = "SELECT 
                        g.goodsNo,
                        g.goodsNm,
                        g.goodsNmDetail,
                        g.goodsPrice,
                        g.goodsImage,
                        g.goodsDescription,
                        g.is_customizable,
                        g.default_color,
                        g.default_size
                    FROM es_goods g
                    WHERE g.goodsNo = ? AND g.delFl = 'n'";

            $result = $this->db->query_fetch($sql, $arrBind, false);

            if ($result) {
                // 构建图片URL
                $result['goods_image_url'] = $this->buildImageUrl($result['goodsImage']);
                
                return $result;
            }

            return null;

        } catch (Exception $e) {
            Logger::error('获取商品信息异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return null;
        }
    }

    /**
     * 获取商品颜色选项
     * 
     * @param int $goodsNo 商品编号
     * @return array
     */
    private function getGoodsColors($goodsNo)
    {
        try {
            $arrBind = [];
            $this->db->bind_param_push($arrBind, 'i', $goodsNo);
            
            $sql = "SELECT 
                        gc.color_id,
                        gc.color_code,
                        gc.color_name,
                        gc.color_hex,
                        gc.sort_order
                    FROM es_goods_colors gc
                    INNER JOIN es_goods_color_relation gcr ON gc.color_id = gcr.color_id
                    WHERE gcr.goods_id = ? AND gc.status = 'active'
                    ORDER BY gc.sort_order ASC, gc.color_id ASC";

            $result = $this->db->query_fetch($sql, $arrBind, true);

            return $result ?: [];

        } catch (Exception $e) {
            Logger::error('获取商品颜色异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return [];
        }
    }

    /**
     * 获取商品尺寸选项
     * 
     * @param int $goodsNo 商品编号
     * @return array
     */
    private function getGoodsSizes($goodsNo)
    {
        try {
            $arrBind = [];
            $this->db->bind_param_push($arrBind, 'i', $goodsNo);
            
            $sql = "SELECT 
                        gs.size_id,
                        gs.size_code,
                        gs.size_name,
                        gs.size_display,
                        gs.sort_order
                    FROM es_goods_sizes gs
                    INNER JOIN es_goods_size_relation gsr ON gs.size_id = gsr.size_id
                    WHERE gsr.goods_id = ? AND gs.status = 'active'
                    ORDER BY gs.sort_order ASC, gs.size_id ASC";

            $result = $this->db->query_fetch($sql, $arrBind, true);

            return $result ?: [];

        } catch (Exception $e) {
            Logger::error('获取商品尺寸异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return [];
        }
    }

    /**
     * 构建图片URL
     * 
     * @param string $imagePath 图片路径
     * @return string
     */
    private function buildImageUrl($imagePath)
    {
        if (empty($imagePath)) {
            return '';
        }

        // 如果是完整URL，直接返回
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // 使用官方Request类获取主机名
        $host = Request::server()->get('HTTP_HOST');
        $scheme = Request::server()->get('REQUEST_SCHEME') ?: 'http';

        // 如果是相对路径，构建完整URL
        if (strpos($imagePath, '/') === 0) {
            return $scheme . '://' . $host . $imagePath;
        }

        // 默认图片目录
        return $scheme . '://' . $host . '/data/goods/' . $imagePath;
    }

    /**
     * 构建颜色选项HTML
     * 
     * @param array $colors 颜色数组
     * @return string
     */
    private function buildColorsHtml($colors)
    {
        if (empty($colors)) {
            return '<div class="color-item active" data-color="navy" style="background-color: #1e3a8a;"></div>';
        }

        $html = '';
        foreach ($colors as $color) {
            $isActive = ($color['color_code'] === 'navy') ? 'active' : '';
            $html .= sprintf(
                '<div class="color-item %s" data-color="%s" data-color-id="%s" style="background-color: %s;" title="%s"></div>',
                $isActive,
                $color['color_code'],
                $color['color_id'],
                $color['color_hex'] ?? '#1e3a8a',
                $color['color_name']
            );
        }
        
        return $html;
    }

    /**
     * 构建尺寸选项HTML
     * 
     * @param array $sizes 尺寸数组
     * @return string
     */
    private function buildSizesHtml($sizes)
    {
        if (empty($sizes)) {
            return '<div class="size-item active" data-size="4.7x2.8" data-size-id="default">4.7 x 2.8 inch</div>';
        }

        $html = '';
        foreach ($sizes as $size) {
            $isActive = ($size['size_code'] === '4.7x2.8') ? 'active' : '';
            $html .= sprintf(
                '<div class="size-item %s" data-size="%s" data-size-id="%s">%s</div>',
                $isActive,
                $size['size_code'],
                $size['size_id'],
                $size['size_display'] ?? $size['size_name']
            );
        }
        
        return $html;
    }

    /**
     * 验证CSRF令牌
     * 
     * @throws Exception
     */
    private function validateCsrfToken()
    {
        // 从请求头获取CSRF令牌
        $token = Request::server()->get('HTTP_X_CSRF_TOKEN') ?? 
                 Request::post()->get('csrf_token') ?? 
                 Request::get()->get('csrf_token');
        
        $sessionToken = Session::get('csrf_token');
        
        if (!$token || !$sessionToken || $token !== $sessionToken) {
            throw new Exception('CSRF 토큰 검증에 실패했습니다');
        }
    }
}
