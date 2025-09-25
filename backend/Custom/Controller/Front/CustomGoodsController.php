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
use Custom\Component\CustomErrorHandler;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\SkinUtils;
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
     * 商品数据
     * @var array
     */
    private $goodsData;
    
    /**
     * 商品编号
     * @var string
     */
    private $goodsNo;

    /**
     * 定制图案组件
     * @var CustomPattern
     */
    private $customPattern;

    /**
     * 错误处理器
     * @var CustomErrorHandler
     */
    private $errorHandler;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->customGoods = new CustomGoods();
        $this->customPattern = new CustomPattern();
        $this->errorHandler = new CustomErrorHandler();
    }

    /**
     * 商品定制页面
     */
    public function index()
    {
        try {
            $goodsNo = Request::get()->get('goodsNo');
            
            // 参数验证
            if (!$goodsNo) {
                $this->errorHandler->logError('商品定制页面', '商品编号不能为空', ['goodsNo' => $goodsNo]);
                throw new AlertBackException('商品编号不能为空');
            }

            // 获取商品基本信息
            $goodsInfo = $this->getGoodsInfo($goodsNo);
            if (!$goodsInfo) {
                $this->errorHandler->logError('商品定制页面', '商品不存在', ['goodsNo' => $goodsNo]);
                throw new AlertBackException('商品不存在');
            }
            
            // 保存商品数据供buildImageUrl使用
            $this->goodsData = $goodsInfo;
            $this->goodsNo = $goodsNo;

            // 检查商品是否支持定制
            if (!$goodsInfo['is_customizable']) {
                $this->errorHandler->logError('商品定制页面', '商品不支持定制', ['goodsNo' => $goodsNo, 'is_customizable' => $goodsInfo['is_customizable']]);
                throw new AlertBackException('该商品不支持定制功能');
            }

            // 获取商品定制图案
            $patternsResult = $this->customGoods->getGoodsPatterns($goodsNo, ['status' => 1]);
            if (!$patternsResult['success']) {
                $this->errorHandler->logError('商品定制页面', '获取图案失败', ['goodsNo' => $goodsNo, 'error' => $patternsResult['message']]);
            }
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

            // 设置模板变量 - 统一使用Godomall官方字段命名
            $this->setData('goodsNo', $goodsInfo['goodsNo']);
            $this->setData('goodsNm', $goodsInfo['goodsNm']);
            $this->setData('goodsPriceDisplay', number_format($goodsInfo['goodsPrice']) . '원');
            $this->setData('goodsBasePrice', number_format($goodsInfo['goodsPrice']) . '원');
            $this->setData('goodsRating', $goodsInfo['rating'] ?? '4.5');
            $this->setData('goodsReviewCount', $goodsInfo['review_count'] ?? '0');
            $this->setData('goodsImage', $goodsInfo['goods_image_url']);
            $this->setData('is_customizable', $goodsInfo['is_customizable']);
            $this->setData('maxUploadSize', 5 * 1024 * 1024);
            $this->setData('allowedImageTypes', ['jpg', 'jpeg', 'png', 'gif', 'svg']);
            
            // 设置颜色和尺寸数据 - 统一字段命名
            $this->setData('goodsColors', $this->buildColorsHtml($colors));
            $this->setData('goodsSizes', $this->buildSizesHtml($sizes));
            $this->setData('selectedColorName', $goodsInfo['default_color'] ?? 'Navy');
            
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
            
            $this->setData('patternsData', json_encode($formattedPatterns));
            $this->setData('hasPatterns', !empty($patterns));
            
            // 设置CSRF令牌
            $this->setData('csrfToken', Session::get('csrf_token') ?: '');
            
            $this->setData('page_title', '상품 맞춤 제작 - ' . $goodsInfo['goodsNm']);
            
            // 设置模板页面名称（Godomall规范要求）
            $this->getView()->setPageName('custom/goods_custom.html');

        } catch (AlertBackException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->errorHandler->logError('商品定制页面', '页面加载异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            throw new AlertBackException('加载商品定制页面失败，请稍后重试');
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
            $goodsNo = $postData['goodsNo'] ?? null;
            
            // 参数验证
            $validation = $this->validateGoodsParams(['goodsNo' => $goodsNo]);
            if (!$validation['valid']) {
                $this->errorHandler->logError('获取商品尺寸', $validation['message'], ['postData' => $postData]);
                $this->json([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['details']
                ]);
                return;
            }

            $result = $this->customGoods->getGoodsAllSizes($goodsNo);
            
            if ($result['success']) {
                $this->json([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => '상품 크기 정보를 성공적으로 가져왔습니다'
                ]);
            } else {
                $this->errorHandler->logError('获取商品尺寸', '获取尺寸失败', ['goodsNo' => $goodsNo, 'error' => $result['message']]);
                $this->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => null
                ]);
            }

        } catch (Exception $e) {
            $this->errorHandler->logError('获取商品尺寸', 'API调用异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            
            $this->json([
                'success' => false,
                'message' => '크기 정보를 가져오는 중 오류가 발생했습니다',
                'data' => null
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
                    'message' => '패턴 정보를 성공적으로 가져왔습니다',
                    'data' => [
                        'patterns' => $formattedPatterns,
                        'total' => count($formattedPatterns)
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $patternsResult['message'],
                    'data' => null
                ]);
            }

        } catch (Exception $e) {
            Logger::error('获取商品图案异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '获取图案失败：' . $e->getMessage(),
                'data' => null
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
            
            // 数据验证
            $validation = $this->validateCustomizationData($postData);
            if (!$validation['valid']) {
                $this->json([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['details']
                ]);
                return;
            }

            $customizationData = [
                'goodsNo' => $postData['goodsNo'],
                'customizationData' => $postData['customizationData'],
                'selectedColor' => $postData['selectedColor'] ?? null,
                'selectedSize' => $postData['selectedSize'] ?? null,
                'userId' => Session::get('member.sno') ?? 0
            ];

            $result = $this->customGoods->saveCustomization($customizationData);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => '定制数据保存成功',
                    'data' => [
                        'customizationId' => $result['data']['customization_id']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => null
                ]);
            }

        } catch (Exception $e) {
            Logger::error('保存定制数据异常', [
                'error' => $e->getMessage(),
                'post_data' => $postData ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '保存失败：' . $e->getMessage(),
                'data' => null
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
            
            if (empty($postData['goodsNo']) || empty($postData['quantity'])) {
                throw new Exception('参数不完整');
            }

            $cartData = [
                'goodsNo' => $postData['goodsNo'],
                'quantity' => $postData['quantity'],
                'customizationData' => $postData['customizationData'] ?? null,
                'selectedColor' => $postData['selectedColor'] ?? null,
                'selectedSize' => $postData['selectedSize'] ?? null,
                'userId' => Session::get('member.sno') ?? 0
            ];

            $result = $this->customGoods->addCustomGoodsToCart($cartData);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => '定制商品已添加到购物车',
                    'data' => [
                        'cartItemId' => $result['data']['cart_item_id'] ?? null
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => null
                ]);
            }

        } catch (Exception $e) {
            Logger::error('添加到购物车异常', [
                'error' => $e->getMessage(),
                'post_data' => $postData ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '添加到购物车失败：' . $e->getMessage(),
                'data' => null
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
                    'data' => [
                        'imageUrl' => $result['data']['image_url'],
                        'thumbnailUrl' => $result['data']['thumbnail_url']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => null
                ]);
            }

        } catch (Exception $e) {
            Logger::error('上传定制图片异常', [
                'error' => $e->getMessage(),
                'files' => $files ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '图片上传失败：' . $e->getMessage(),
                'data' => null
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

        // 使用Godomall官方方法生成图片URL，与商品详情页保持一致
        $goods = \App::load('\\Component\\Goods\\Goods');
        $goodsImage = $goods->getGoodsImage($this->goodsNo, 'main');
        if ($goodsImage) {
            $goodsImageName = $goodsImage[0]['goodsImageStorage'] == 'obs' ? 
                $goodsImage[0]['imageUrl'] : $goodsImage[0]['imageName'];
            $goodsImageSize = $goodsImage[0]['imageSize'];
            $_imageInfo = pathinfo($goodsImageName);
            if (!$goodsImageSize) {
                $goodsImageSize = SkinUtils::getGoodsImageSize($_imageInfo['extension']);
                $goodsImageSize = $goodsImageSize['size1'];
            }
            
            $goodsImageSrc = SkinUtils::imageViewStorageConfig(
                $goodsImageName, 
                $this->goodsData['imagePath'], 
                $this->goodsData['imageStorage'], 
                $goodsImageSize, 
                'goods', 
                false
            )[0];
            
            if($this->goodsData['imageStorage'] == 'local'){
                $goodsImageSrc = str_replace('https', 'http', $goodsImageSrc);
                $goodsImageSrc = str_replace(':443', '', $goodsImageSrc);
            }
            
            return $goodsImageSrc;
        }

        return '';
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

    /**
     * 验证商品参数
     * 
     * @param array $params 请求参数
     * @return array 验证结果
     */
    private function validateGoodsParams($params)
    {
        $errors = [];

        // 商品编号验证
        if (empty($params['goodsNo'])) {
            $errors[] = '商品编号不能为空';
        } elseif (!is_numeric($params['goodsNo']) || $params['goodsNo'] <= 0) {
            $errors[] = '商品编号格式无效';
        }

        if (empty($errors)) {
            return ['valid' => true, 'message' => '', 'details' => []];
        }

        return [
            'valid' => false, 
            'message' => '参数验证失败', 
            'details' => $errors
        ];
    }

    /**
     * 验证定制数据
     * 
     * @param array $data 定制数据
     * @return array 验证结果
     */
    private function validateCustomizationData($data)
    {
        $errors = [];

        // 商品编号验证
        if (empty($data['goodsNo'])) {
            $errors[] = '商品编号不能为空';
        } elseif (!is_numeric($data['goodsNo']) || $data['goodsNo'] <= 0) {
            $errors[] = '商品编号格式无效';
        }

        // 定制数据验证
        if (empty($data['customizationData'])) {
            $errors[] = '定制数据不能为空';
        } elseif (!is_array($data['customizationData']) && !is_string($data['customizationData'])) {
            $errors[] = '定制数据格式无效';
        }

        // 颜色验证
        if (isset($data['selectedColor']) && !empty($data['selectedColor'])) {
            if (strlen($data['selectedColor']) > 50) {
                $errors[] = '颜色名称不能超过50个字符';
            }
        }

        // 尺寸验证
        if (isset($data['selectedSize']) && !empty($data['selectedSize'])) {
            if (strlen($data['selectedSize']) > 50) {
                $errors[] = '尺寸名称不能超过50个字符';
            }
        }

        if (empty($errors)) {
            return ['valid' => true, 'message' => '', 'details' => []];
        }

        return [
            'valid' => false, 
            'message' => '数据验证失败', 
            'details' => $errors
        ];
    }
}
