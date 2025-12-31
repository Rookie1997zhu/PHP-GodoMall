<?php

/**
 * 前台商品定制控制器（基于 Godomall 标准）
 * 
 * 本控制器使用 CustomGoods 组件获取商品定制数据，
 * 遵循 Godomall 官方开发规范，类似 GoodsViewController 的设计
 * 
 * 【重要】根据Godomall官方规范和路由机制：
 * - URL路径：/custom/goods_custom.php (SnakeCase)
 * - 文件位置：module/Controller/Front/Custom/GoodsCustomController.php
 * - 命名空间：Controller\Front\Custom (符合目录结构)
 * - Controller类名：GoodsCustomController (CamelCase，对应goods_custom.php)
 * - 模板文件：custom/goods_custom.html (SnakeCase)
 * 
 * 【数据获取方式】
 * - 使用 CustomGoods->getCustomGoodsView() 获取完整数据
 * - 数据结构类似官方 goodsView，便于模板使用
 * - 遵循 SSR（服务端渲染）模式，提升性能和SEO
 * 
 * @package Controller\Front\Custom
 * @author Custom Development Team
 * @version 3.0 (Based on Godomall Standard)
 */

namespace Controller\Front\Custom;

use Framework\Debug\Exception\AlertBackException;
use Exception;
use Request;
use Session;
use Custom\Component\Database\CustomPDO;

/**
 * 前台商品定制控制器
 */
class GoodsCustomController extends \Controller\Front\Controller
{
    /**
     * 数据库连接对象
     * @var object
     */
    private $db;

    /**
     * CustomGoods 组件
     * @var \Custom\Component\CustomGoods
     */
    private $customGoods;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = \App::load('DB');
        
        // ✅ 加载 CustomGoods 组件
        $this->customGoods = \App::load('\\Custom\\Component\\CustomGoods');
        
        error_log('[GoodsCustomController] Initialized with CustomGoods component');
    }

    /**
     * 默认页面 - 商品定制页面
     * 
     * 类似官方 GoodsViewController::index() 的设计
     */
    public function index()
    {
        try {
            error_log('[GoodsCustomController] index() method called');
            // 优先处理 AJAX 动作分发（例如 ?mode=addToCart）
            $modeGet = Request::get()->get('mode');
            $modePost = \Request::post()->get('mode');
            $mode = $modeGet ?: $modePost;
            if ($mode === 'addToCart') {
                // 直接进入 JSON 处理方法，避免继续渲染页面导致返回HTML
                $this->addToCart();
                return;
            }
            
            // 处理订单图片 Ajax 请求
            if ($mode === 'orderImage') {
                $this->orderImage();
                return;
            }

            // 处理购物车附加费用 Ajax 请求
            if ($mode === 'cartExtraPrice') {
                $this->cartExtraPrice();
                return;
            }

            // 处理获取画布数据 Ajax 请求（用于「편집하기」从数据库还原）
            if ($mode === 'getCanvasData') {
                $this->getCanvasData();
                return;
            }

            // 处理删除定制数据 Ajax 请求
            if ($mode === 'deleteCustomData') {
                $this->deleteCustomData();
                return;
            }
            
            // 获取商品编号
            $goodsNo = Request::get()->get('goodsNo');
            error_log('[GoodsCustomController] goodsNo: ' . $goodsNo);
            
            // 参数验证
            if (!$goodsNo) {
                throw new AlertBackException('상품 정보가 없습니다.');
            }

            // ✅ 使用 CustomGoods 组件获取真实数据
            $customGoodsView = $this->customGoods->getCustomGoodsView($goodsNo);
            
            if (!$customGoodsView) {
                throw new AlertBackException('상품을 찾을 수 없습니다.');
            }
            
            error_log('[GoodsCustomController] Got customGoodsView from CustomGoods component');
            error_log('[GoodsCustomController] Colors count: ' . $customGoodsView['color_count']);
            error_log('[GoodsCustomController] Custom size: ' . $customGoodsView['custom_size_display']);

            // 验证是否支持定制
            if (empty($customGoodsView['is_customizable'])) {
                throw new AlertBackException('이 상품은 맞춤 제작이 불가능합니다.');
            }

            // ✅ 使用 Godomall 标准方式传递数据到模板
            // 传递整个 customGoodsView 对象（类似官方的 goodsView）
            $this->setData('customGoodsView', $customGoodsView);
            
            // ✅ 生成颜色选项HTML
            $goodsColorsHtml = $this->customGoods->renderColorOptionsHtml($customGoodsView['colors']);
            $this->setData('goodsColors', $goodsColorsHtml);
            
            // ✅ 设置选中的颜色名称（默认第一个）
            $selectedColorName = !empty($customGoodsView['colors']) 
                ? $customGoodsView['colors'][0]['color_name'] 
                : '';
            $this->setData('selectedColorName', $selectedColorName);
            
            // ✅ 其他页面配置
            $this->setData('page_title', '상품 맞춤 제작 - ' . $customGoodsView['goodsNm']);
            $this->setData('goodsRating', '4.5'); // 示例评分
            $this->setData('goodsReviewCount', '128'); // 示例评论数
            $this->setData('goods_base_price', $customGoodsView['goods_price_display']);
            $this->setData('has_patterns', false); // 暂时不使用图案功能
            $this->setData('patterns_data', '[]'); // 空数组
            $this->setData('max_upload_size', 5242880); // 5MB
            $this->setData('allowed_image_types', json_encode(['image/jpeg', 'image/png', 'image/gif']));
            $this->setData('csrf_token', Session::get('csrf_token') ?? '');
            
            error_log('[GoodsCustomController] Template data set, rendering view');
            
            // 添加前端调试信息
            $debugInfo = [
                'controller' => 'GoodsCustomController',
                'method' => 'index',
                'timestamp' => date('Y-m-d H:i:s'),
                'goodsNo' => $goodsNo,
                'goodsNm' => $customGoodsView['goodsNm'],
                'color_count' => $customGoodsView['color_count'],
                'custom_size' => $customGoodsView['custom_size_display'],
                'templatePath' => 'data/skin/front/mplshop/custom/goods_custom.html'
            ];
            
            $debugScript = '<script>console.log("[GoodsCustomController] 调试信息:", ' . json_encode($debugInfo, JSON_UNESCAPING_UNICODE | JSON_PRETTY_PRINT) . ');</script>';
            $this->setData('debugScript', $debugScript);

        } catch (AlertBackException $e) {
            error_log('[GoodsCustomController] AlertBackException: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            error_log('[GoodsCustomController] Exception: ' . $e->getMessage());
            error_log('[GoodsCustomController] Trace: ' . $e->getTraceAsString());
            throw new AlertBackException('페이지를 로드하는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 定制商品加入购物车
     * 
     * 接收AJAX POST请求，处理定制商品数据并保存到购物车扩展表
     * 
     * 【功能说明】
     * 1. 接收前端定制数据（包含Base64图片）
     * 2. 验证客户是否上传了图案（customer_pattern_image必需）
     * 3. 调用官方Cart组件将商品加入购物车
     * 4. 将定制数据保存到custom_cart_extension表
     * 5. 返回JSON响应
     * 
     * 【调用方式】
     * URL: /custom/goods_custom.php?mode=addToCart
     * Method: POST
     * Content-Type: application/json
     * 
     * @return void
     */
    public function addToCart()
    {
        // 设置JSON响应头
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            error_log('[GoodsCustomController] addToCart() method called');
            
            // 获取POST数据
            $rawInput = file_get_contents('php://input');
            if (empty($rawInput)) {
                throw new Exception('请求数据为空');
            }
            
            $requestData = json_decode($rawInput, true);
            if (!$requestData) {
                throw new Exception('无效的JSON数据');
            }
            
            error_log('[GoodsCustomController] Request data received');
            
            // 验证必需字段
            $goodsNo = $requestData['goods_no'] ?? null;
            $quantity = intval($requestData['quantity'] ?? 1);
            $selectedColor = $requestData['selected_color'] ?? null;
            $selectedSize = $requestData['selected_size'] ?? null;
            $customizationData = $requestData['customization_data'] ?? null;
            $customizationImage = $requestData['customization_image'] ?? null;
            $customerPatternImage = $requestData['customer_pattern_image'] ?? null;
            // ⭐ 新增：接收前端传来的附加费用总额
            $extraPriceTotal = isset($requestData['extra_price_total'])
                ? (int)$requestData['extra_price_total']
                : 0;
            if ($extraPriceTotal < 0) {
                $extraPriceTotal = 0;
            }

            // ⭐ 新增：接收前端传来的官方选项 sno
            $optionSnoFromRequest = isset($requestData['option_sno'])
                ? (int)$requestData['option_sno']
                : 0;

            // ⭐ 新增：接收前端传来的文本选项
            $optionTextsFromRequest = [];
            if (isset($requestData['option_texts']) && is_array($requestData['option_texts'])) {
                foreach ($requestData['option_texts'] as $textSno => $textValue) {
                    $textSno = (int)$textSno;
                    if ($textSno <= 0) {
                        continue;
                    }
                    $optionTextsFromRequest[$textSno] = trim((string)$textValue);
                }
            }

            // ✅ 新增：检查是否为编辑模式（有 cart_sno 则为更新现有购物车项）
            $existingCartSno = isset($requestData['cart_sno']) ? (int)$requestData['cart_sno'] : 0;
            
            // 验证商品编号
            if (empty($goodsNo) || !is_numeric($goodsNo)) {
                throw new Exception('商品编号无效');
            }
            
            // 验证数量
            if ($quantity < 1) {
                throw new Exception('商品数量必须大于0');
            }
            
            // 验证至少有一种定制内容（图案或合成图），允许“仅文字 + 商品背景”场景
            if (empty($customerPatternImage) && empty($customizationImage)) {
                throw new Exception('请先添加文字或图案后再加入购物车');
            }
            
            // ✅ 仅当存在客户图案时才验证图案格式（支持单个Base64或JSON数组）
            if (!empty($customerPatternImage)) {
            $isValidFormat = false;
            
            // 1. 检查是否为JSON数组
            if (is_string($customerPatternImage) && strpos(trim($customerPatternImage), '[') === 0) {
                try {
                    $imagesArray = json_decode($customerPatternImage, true);
                    if (is_array($imagesArray) && count($imagesArray) > 0) {
                        // 验证数组中每个元素都是Base64格式
                        $allValid = true;
                        foreach ($imagesArray as $img) {
                            if (!preg_match('/^data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,/', $img)) {
                                $allValid = false;
                                break;
                            }
                        }
                        $isValidFormat = $allValid;
                        error_log('[GoodsCustomController] 客户图案为JSON数组，包含 ' . count($imagesArray) . ' 张图片');
                    }
                } catch (Exception $e) {
                    error_log('[GoodsCustomController] JSON解析失败: ' . $e->getMessage());
                    $isValidFormat = false;
                }
            }
            
            // 2. 如果不是JSON数组，检查是否为单个Base64
            if (!$isValidFormat) {
                $isValidFormat = preg_match('/^data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,/', $customerPatternImage);
                if ($isValidFormat) {
                    error_log('[GoodsCustomController] 客户图案为单个Base64图片');
                }
            }
            
            if (!$isValidFormat) {
                throw new Exception('客户图案格式无效');
                }
            }
            
            // ✅ 若存在合成图，则支持「JSON数组（多套设计）」和「单个Base64」两种格式（宽松校验）
            if (!empty($customizationImage)) {
                $isValidCustomization = false;

                // 1) JSON 数组：多套设计（前端传入的格式）
                if (is_string($customizationImage) && strpos(ltrim($customizationImage), '[') === 0) {
                    try {
                        $customImages = json_decode($customizationImage, true);
                        if (is_array($customImages) && count($customImages) > 0) {
                            $allValid = true;
                            foreach ($customImages as $img) {
                                // 只要是字符串且以 data:image/ 开头就认为有效
                                if (!is_string($img) || strpos($img, 'data:image/') !== 0) {
                                    $allValid = false;
                                    break;
                                }
                            }
                            $isValidCustomization = $allValid;
                            if ($isValidCustomization) {
                                error_log('[GoodsCustomController] 定制图片为JSON数组，包含 ' . count($customImages) . ' 张图片');
                            }
                        }
                    } catch (Exception $e) {
                        error_log('[GoodsCustomController] 定制图片JSON解析失败: ' . $e->getMessage());
                        $isValidCustomization = false;
                    }
                }

                // 2) 单个 Base64：兼容旧格式
                if (!$isValidCustomization && is_string($customizationImage)) {
                    if (strpos($customizationImage, 'data:image/') === 0) {
                        $isValidCustomization = true;
                        error_log('[GoodsCustomController] 定制图片为单个Base64图片');
                    }
                }

                if (!$isValidCustomization) {
                throw new Exception('定制图片格式无效');
                }
            }
            
            error_log('[GoodsCustomController] Validation passed');

            // ✅ 如果是编辑模式，直接更新现有购物车项的定制数据
            if ($existingCartSno > 0) {
                error_log('[GoodsCustomController] 编辑模式：更新现有购物车项 cartSno=' . $existingCartSno);
                
                // 直接更新定制数据到扩展表（不创建新购物车项）
                $customCartExt = \App::Load('\\Custom\\Component\\CustomCartExtension');
                
                $customData = [
                    'customization_data'     => $customizationData,
                    'selected_color'         => $selectedColor,
                    'selected_size'          => $selectedSize,
                    'customer_pattern_image' => $customerPatternImage,
                    'customization_image'    => $customizationImage,
                    'extra_price_total'      => $extraPriceTotal,
                ];
                
                $saveResult = $customCartExt->saveCustomizationData($existingCartSno, $customData);
                
                if (!$saveResult) {
                    throw new Exception('更新定制数据失败');
                }
                
                // ✅ 同时更新 es_cart 表中的数量（如果需要）
                if ($quantity > 0) {
                    $pdo = new CustomPDO();
                    $pdo->execute(
                        "UPDATE es_cart SET goodsCnt = :cnt WHERE sno = :sno",
                        ['cnt' => $quantity, 'sno' => $existingCartSno]
                    );
                }
                
                error_log('[GoodsCustomController] 编辑模式：定制数据更新成功');
                
                // 返回成功响应（使用原有的 cartSno）
                echo json_encode([
                    'success' => true,
                    'message' => '커스텀 상품이 수정되었습니다.',
                    'cart_sno' => $existingCartSno,
                    'goods_no' => $goodsNo,
                    'quantity' => $quantity,
                    'is_update' => true
                ], JSON_UNESCAPED_UNICODE);
                
                exit;
            }

            // ===== 以下为原有的新建购物车项逻辑 =====
            
            // 1. 读取商品信息，判断选项使用情况
            $goods = \App::load('\\Component\\Goods\\Goods');
            $goodsView = $goods->getGoodsView($goodsNo);
            if (empty($goodsView)) {
                throw new Exception('无法读取商品信息');
            }

            // ✅ 如果前端传递了官方选项 sno，则优先使用；否则取默认选项
            $optionSno = 0;
            if ($optionSnoFromRequest > 0) {
                // 用户在 goods_custom.html 选择了官方选项
                $optionSno = $optionSnoFromRequest;
                error_log('[GoodsCustomController] 使用前端传递的官方选项 sno: ' . $optionSno);
            } elseif (gd_isset($goodsView['optionFl']) === 'y') {
                // 商品有选项但前端未传，取默认第一个选项
                $optionSno = gd_isset($goodsView['optionInfo'][0]['sno'], 0);
                error_log('[GoodsCustomController] 使用默认选项 sno: ' . $optionSno);
            }

            $goodsPrice = (int)gd_isset($goodsView['goodsPrice'], gd_isset($goodsView['goodsPriceSum'], 0));

            // ✅ 文本选项校验（参考官方逻辑）
            $optionTextPayload = [];
            if (gd_isset($goodsView['optionTextFl']) === 'y' && isset($goodsView['optionText']) && is_array($goodsView['optionText'])) {
                foreach ($goodsView['optionText'] as $textRow) {
                    $textSno = (int)gd_isset($textRow['sno']);
                    $inputLimit = (int)gd_isset($textRow['inputLimit'], 100);
                    $isRequired = gd_isset($textRow['mustFl']) === 'y';
                    $rawValue = $optionTextsFromRequest[$textSno] ?? '';
                    if ($isRequired && $rawValue === '') {
                        throw new Exception(sprintf('%s 옵션을 입력해 주세요.', gd_isset($textRow['optionName'], '텍스트')));
                    }
                    if ($rawValue !== '') {
                        if ($inputLimit > 0) {
                            $rawValue = mb_substr($rawValue, 0, $inputLimit, 'UTF-8');
                        }
                        $optionTextPayload[$textSno] = $rawValue;
                    }
                }
            }

            // 2. 调用官方Cart组件添加商品到购物车
            $cart = \App::Load('\\Component\\Cart\\Cart');
            $cartData = [
                'goodsNo'   => (int)$goodsNo,
                'optionSno' => (int)$optionSno,
                'goodsCnt'  => (int)$quantity,
                'goodsPrice'=> $goodsPrice,
            ];

            if (!empty($optionTextPayload)) {
                $cartData['optionText'] = $optionTextPayload;
            }

            error_log('[GoodsCustomController] Adding to cart: ' . json_encode($cartData));

            $cartSno = $cart->saveGoodsToCart($cartData);
            if (empty($cartSno) || (int)$cartSno <= 0) {
                throw new Exception('加入购物车失败，请检查商品状态');
            }

            error_log('[GoodsCustomController] Cart sno: ' . $cartSno);
            
            // 3. 保存定制数据到custom_cart_extension表
            $customCartExt = \App::Load('\\Custom\\Component\\CustomCartExtension');
            
            $customData = [
                'customization_data'     => $customizationData,
                'selected_color'         => $selectedColor,
                'selected_size'          => $selectedSize,
                'customer_pattern_image' => $customerPatternImage,
                'customization_image'    => $customizationImage,
                // ⭐ 新增：本行购物车的附加费用总额
                'extra_price_total'      => $extraPriceTotal,
            ];
            
            $saveResult = $customCartExt->saveCustomizationData($cartSno, $customData);
            
            if (!$saveResult) {
                error_log('[GoodsCustomController] Warning: 定制数据保存失败，但商品已加入购物车');
            }
            
            error_log('[GoodsCustomController] Custom data saved successfully');
            
            // 4. 返回成功响应
            echo json_encode([
                'success' => true,
                'message' => '커스텀 상품이 장바구니에 추가되었습니다.',
                'cart_sno' => $cartSno,
                'goods_no' => $goodsNo,
                'quantity' => $quantity
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            error_log('[GoodsCustomController] addToCart error: ' . $e->getMessage());
            error_log('[GoodsCustomController] Error trace: ' . $e->getTraceAsString());
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }

    /**
     * 获取最新的购物车序列号
     * 
     * 遍历购物车数据，找到指定商品的sno
     * 
     * @param object $cart Cart组件实例
     * @param int $goodsNo 商品编号
     * @return int|null 购物车序列号
     */
    private function getLatestCartSno($cart, $goodsNo)
    {
        try {
            $cartInfo = $cart->getCartGoodsData();
            
            if (!empty($cartInfo)) {
                // 遍历三层嵌套数组
                foreach ($cartInfo as $scmData) {
                    foreach ($scmData as $deliveryData) {
                        foreach ($deliveryData as $item) {
                            if (isset($item['goodsNo']) && $item['goodsNo'] == $goodsNo) {
                                return $item['sno'];
                            }
                        }
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('[GoodsCustomController] getLatestCartSno error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * AJAX：获取购物车项的附加费用
     *
     * @return void
     */
    public function cartExtraPrice()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rawInput = file_get_contents('php://input');
            $cartSnos = [];

            if (!empty($rawInput)) {
                $decoded = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['cart_snos'])) {
                    $cartSnos = $decoded['cart_snos'];
                }
            }

            if (empty($cartSnos)) {
                $cartSnos = Request::post()->get('cart_snos');
            }

            if (empty($cartSnos)) {
                throw new Exception('장바구니 정보가 없습니다.');
            }

            if (is_string($cartSnos)) {
                $cartSnos = array_filter(array_map('trim', explode(',', $cartSnos)));
            }

            $cartSnos = array_values(array_unique(array_filter(array_map(function ($sno) {
                return is_numeric($sno) ? (int)$sno : 0;
            }, (array)$cartSnos), function ($sno) {
                return $sno > 0;
            })));

            if (empty($cartSnos)) {
                throw new Exception('장바구니 정보가 없습니다.');
            }

            $customCartExt = \App::load('\\Custom\\Component\\CustomCartExtension');
            $extensionData = $customCartExt->getBatchCustomizationImages($cartSnos);

            $optionMetaByCart = [];
            $optionIds = [];
            $colorOptionIds = [];

            foreach ($cartSnos as $sno) {
                $optionMetaByCart[$sno] = [
                    'optionIds' => [],
                    'colorSelections' => [],
                ];

                if (empty($extensionData[$sno]['customization_data'])) {
                    continue;
                }

                $customizationData = json_decode($extensionData[$sno]['customization_data'], true);
                if (!is_array($customizationData)) {
                    continue;
                }

                $extraOptions = $customizationData['extraOptions'] ?? [];
                $optionList = isset($extraOptions['options']) && is_array($extraOptions['options'])
                    ? $extraOptions['options']
                    : [];
                foreach ($optionList as $optionRow) {
                    $optId = isset($optionRow['option_id']) ? (int)$optionRow['option_id'] : 0;
                    if ($optId <= 0) {
                        continue;
                    }
                    $optionMetaByCart[$sno]['optionIds'][] = $optId;
                    $optionIds[$optId] = $optId;
                }

                $colorPalettes = isset($extraOptions['colorPalettes']) && is_array($extraOptions['colorPalettes'])
                    ? $extraOptions['colorPalettes']
                    : [];
                foreach ($colorPalettes as $paletteRow) {
                    $optId = isset($paletteRow['option_id']) ? (int)$paletteRow['option_id'] : 0;
                    $selectedColor = isset($paletteRow['selected_color'])
                        ? trim((string)$paletteRow['selected_color'])
                        : '';
                    if ($optId <= 0 || $selectedColor === '') {
                        continue;
                    }
                    $optionMetaByCart[$sno]['colorSelections'][$optId] = $selectedColor;
                    $optionIds[$optId] = $optId;
                    $colorOptionIds[$optId] = $optId;
                }
            }

            $pdo = new CustomPDO();

            $optionLabelMap = [];
            if (!empty($optionIds)) {
                $placeholders = implode(',', array_fill(0, count($optionIds), '?'));
                $rows = $pdo->fetchAll(
                    "SELECT option_id, option_label 
                     FROM custom_goods_extra_options 
                     WHERE option_id IN ({$placeholders})",
                    array_values($optionIds)
                );
                foreach ($rows as $row) {
                    $optionId = (int)($row['option_id'] ?? 0);
                    if ($optionId > 0) {
                        $optionLabelMap[$optionId] = (string)($row['option_label'] ?? '');
                    }
                }
            }

            $colorLookup = [];
            $normalizeColorKey = function ($value) {
                $value = strtolower(trim((string)$value));
                if ($value === '') {
                    return [];
                }
                $keys = [$value];
                $noHash = ltrim($value, '#');
                if ($noHash !== $value) {
                    $keys[] = $noHash;
                    $keys[] = '#' . $noHash;
                }
                return array_values(array_unique(array_filter($keys, function ($item) {
                    return $item !== '';
                })));
            };

            if (!empty($colorOptionIds)) {
                $placeholders = implode(',', array_fill(0, count($colorOptionIds), '?'));
                $rows = $pdo->fetchAll(
                    "SELECT option_id, color_name, color_code, color_hex 
                     FROM custom_goods_extra_option_colors 
                     WHERE option_id IN ({$placeholders})",
                    array_values($colorOptionIds)
                );
                foreach ($rows as $row) {
                    $optionId = (int)($row['option_id'] ?? 0);
                    if ($optionId <= 0) {
                        continue;
                    }
                    if (!isset($colorLookup[$optionId])) {
                        $colorLookup[$optionId] = [];
                    }
                    $keys = array_merge(
                        $normalizeColorKey($row['color_code'] ?? ''),
                        $normalizeColorKey($row['color_hex'] ?? '')
                    );
                    foreach ($keys as $key) {
                        $colorLookup[$optionId][$key] = (string)($row['color_name'] ?? '');
                    }
                }
            }

            $responseData = [];
            foreach ($cartSnos as $sno) {
                $extensionRow = $extensionData[$sno] ?? [];
                $meta = $optionMetaByCart[$sno] ?? ['optionIds' => [], 'colorSelections' => []];

                $labels = [];
                $colorNames = [];

                foreach ($meta['optionIds'] as $optId) {
                    if (!empty($optionLabelMap[$optId])) {
                        $labels[] = $optionLabelMap[$optId];
                    }

                    if (!empty($meta['colorSelections'][$optId])) {
                        $selected = strtolower(trim($meta['colorSelections'][$optId]));
                        $colorName = '';
                        if (isset($colorLookup[$optId][$selected])) {
                            $colorName = $colorLookup[$optId][$selected];
                        } else {
                            $altKey = ltrim($selected, '#');
                            if ($altKey !== $selected && isset($colorLookup[$optId][$altKey])) {
                                $colorName = $colorLookup[$optId][$altKey];
                            } elseif (isset($colorLookup[$optId]['#' . $altKey])) {
                                $colorName = $colorLookup[$optId]['#' . $altKey];
                            }
                        }
                        if ($colorName !== '') {
                            $colorNames[] = $colorName;
                        }
                    }
                }

                $responseData[$sno] = [
                    'extra_price_total' => isset($extensionRow['extra_price_total'])
                        ? (int)$extensionRow['extra_price_total']
                        : 0,
                    'option_label' => implode(' / ', array_values(array_unique(array_filter($labels, function ($label) {
                        return trim($label) !== '';
                    })))),
                    'color_name' => implode(' / ', array_values(array_unique(array_filter($colorNames, function ($label) {
                        return trim($label) !== '';
                    })))),
                    // ✅ 新增：返回定制图片字段，供前端 AJAX 渲染「내 디자인 / 업로드 이미지」
                    'customization_image' => $extensionRow['customization_image'] ?? null,
                    'customer_pattern_image' => $extensionRow['customer_pattern_image'] ?? null,
                ];
            }

            echo json_encode([
                'success' => true,
                'data'    => $responseData,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /**
     * 处理订单定制图片的 Ajax 请求
     * 
     * 【功能说明】
     * 1. 保存订单图片（action=save）：从订单完成页接收Base64图片数据，写入custom_order_extension表
     * 2. 读取订单图片（action=get）：从后台订单列表页查询多个订单的图片数据
     * 
     * 【调用方式】
     * - 保存：POST /custom/goods_custom.php?mode=orderImage&action=save
     * - 读取：GET  /custom/goods_custom.php?mode=orderImage&action=get&orderNos=xxx,yyy,zzz
     * 
     * @return void
     */
    public function orderImage()
    {
        // 设置JSON响应头
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            // 获取操作类型（save 或 get）
            $action = Request::request()->get('action') ?: 'get';
            
            // 加载 CustomOrderExtension 组件
            $extension = \App::load('\\Custom\\Component\\CustomOrderExtension');
            
            if ($action === 'save') {
                // ===== 保存订单图片 =====
                error_log('[GoodsCustomController] orderImage action=save');
                
                // 获取POST数据
                $orderNo = trim((string) Request::post()->get('orderNo'));
                $customizationImage = (string) Request::post()->get('customizationImage');
                $customerPatternImage = (string) Request::post()->get('customerPatternImage');
                // 整单定制数据（JSON字符串，可为空）
                $customizationData = (string) Request::post()->get('customizationData');
                
                // 验证订单号
                if ($orderNo === '') {
                    throw new Exception('订单号不能为空');
                }
                
                // 调用组件保存
                $extension->saveOrderImages($orderNo, [
                    'customization_image'    => $customizationImage,
                    'customer_pattern_image' => $customerPatternImage,
                    'customization_data'     => $customizationData,
                ]);
                
                error_log('[GoodsCustomController] Order images/data saved for orderNo: ' . $orderNo);
                
                // 返回成功响应
                echo json_encode([
                    'success' => true,
                    'message' => '订单图片保存成功'
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'get') {
                // ===== 读取订单图片 =====
                error_log('[GoodsCustomController] orderImage action=get');
                
                // 收集订单号
                $orderNos = $this->collectOrderNosFromRequest();
                
                if (empty($orderNos)) {
                    throw new Exception('未提供订单号');
                }
                
                // 调用组件查询
                $data = $extension->getImagesByOrderNos($orderNos);
                
                error_log('[GoodsCustomController] Retrieved images for ' . count($orderNos) . ' orders');
                
                // 返回数据
                echo json_encode([
                    'success' => true,
                    'data' => $data
                ], JSON_UNESCAPED_UNICODE);
                
            } else {
                throw new Exception('无效的操作类型: ' . $action);
            }
            
        } catch (Exception $e) {
            error_log('[GoodsCustomController] orderImage error: ' . $e->getMessage());
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }

    /**
     * 从请求中收集订单号列表
     * 
     * 支持两种方式：
     * 1. 单个订单号：?orderNo=xxx
     * 2. 多个订单号：?orderNos=xxx,yyy,zzz
     * 
     * @return array 订单号数组
     */
    private function collectOrderNosFromRequest()
    {
        $orderNos = [];
        
        // 方式1：单个订单号
        $single = (string) Request::request()->get('orderNo');
        if ($single !== '') {
            $orderNos[] = $single;
        }
        
        // 方式2：多个订单号（逗号分隔）
        $list = (string) Request::request()->get('orderNos');
        if ($list !== '') {
            foreach (explode(',', $list) as $orderNo) {
                $orderNo = trim($orderNo);
                if ($orderNo !== '') {
                    $orderNos[] = $orderNo;
                }
            }
        }
        
        // 去重并返回
        return array_values(array_unique($orderNos));
    }

    /**
     * AJAX：获取购物车项的画布数据（用于「편집하기」从数据库还原）
     * 
     * 【调用方式】
     * URL: /custom/goods_custom.php?mode=getCanvasData&cartSno=xxx
     * Method: GET
     * 
     * 【返回数据】
     * {
     *     success: true,
     *     data: {
     *         customization_data: { canvasJSON, designStates, currentDesignId, customerPatternImages, ... }
     *     }
     * }
     *
     * @return void
     */
    public function getCanvasData()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $cartSno = Request::get()->get('cartSno');

            if (empty($cartSno) || !is_numeric($cartSno)) {
                throw new Exception('购物车编号无效');
            }

            $cartSno = (int)$cartSno;

            // 从 custom_cart_extension 获取完整定制数据
            $customCartExt = \App::load('\\Custom\\Component\\CustomCartExtension');
            $extensionData = $customCartExt->getFullExtensionData($cartSno);

            if (empty($extensionData)) {
                throw new Exception('未找到定制数据');
            }

            // 解析 customization_data JSON
            $customizationData = null;
            if (!empty($extensionData['customization_data'])) {
                $customizationData = json_decode($extensionData['customization_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $customizationData = null;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'customization_data' => $customizationData,
                    'selected_color' => $extensionData['selected_color'] ?? null,
                    'selected_size' => $extensionData['selected_size'] ?? null,
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log('[GoodsCustomController] getCanvasData error: ' . $e->getMessage());

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /**
     * AJAX：删除购物车项的定制数据
     * 
     * 【调用方式】
     * URL: /custom/goods_custom.php?mode=deleteCustomData
     * Method: POST
     * Content-Type: application/x-www-form-urlencoded
     * 
     * @return void
     */
    public function deleteCustomData()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $cartSnos = Request::post()->get('cart_snos');

            if (empty($cartSnos) || !is_array($cartSnos)) {
                throw new Exception('购物车编号不能为空');
            }

            $customCartExt = \App::load('\\Custom\\Component\\CustomCartExtension');
            $deletedCount = 0;

            foreach ($cartSnos as $cartSno) {
                $sno = (int)$cartSno;
                if ($sno > 0) {
                    $customCartExt->deleteCustomizationData($sno);
                    $deletedCount++;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => '定制数据删除成功',
                'deleted_count' => $deletedCount
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log('[GoodsCustomController] deleteCustomData error: ' . $e->getMessage());

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
