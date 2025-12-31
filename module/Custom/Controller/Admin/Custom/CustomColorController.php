<?php

/**
 * 商品定制颜色管理控制器
 * 
 * 本控制器提供后台颜色管理的API接口
 * 
 * @package Custom\Controller\Admin\Custom
 * @author Custom Development Team
 * @version 1.0
 */

namespace Custom\Controller\Admin\Custom;

use Custom\Component\CustomColor;
use Custom\Component\CustomExtraOption;
use Custom\Component\CustomSize;
use Custom\Component\CustomExtraImage;
use Custom\Component\Database\CustomPDO;
use Framework\Debug\Exception\AlertBackException;
use Framework\StaticProxy\Proxy\UserFilePath;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 颜色管理控制器
 */
class CustomColorController extends \Controller\Admin\Controller
{
    /**
     * 颜色组件实例
     * @var CustomColor
     */
    private $customColor;

    /**
     * 附加选项组件实例
     * @var CustomExtraOption
     */
    private $customExtraOption;

    /**
     * 自定义尺寸组件实例
     * @var CustomSize
     */
    private $customSize;

    /**
     * 附加图片组件实例
     * @var CustomExtraImage
     */
    private $customExtraImage;

    /**
     * 自定义PDO实例（用于模板与裁剪区域）
     * @var CustomPDO
     */
    private $pdo;

    /**
     * 构造函数
     */
    public function __construct()
    {
        try {
            // 尝试调用父类构造函数来初始化Godomall框架
            parent::__construct();
        } catch (Exception $e) {
            // 如果父类构造函数失败，记录错误但不中断执行
            error_log('Godomall框架初始化失败: ' . $e->getMessage());
        }
        
        $this->customColor = new CustomColor();
        $this->customExtraOption = new CustomExtraOption();
        $this->customSize = new CustomSize();
        $this->customExtraImage = new CustomExtraImage();

        // 初始化自定义 PDO，用于 custom_template / custom_template_clip_region 等表
        $this->pdo = new CustomPDO();
    }

    /**
     * 发送JSON响应
     */
    private function sendJsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 控制器入口方法
     */
    public function index()
    {
        // 获取请求参数，支持mode和action两种参数名（向后兼容）
        $action = Request::request()->get('action') ?: Request::request()->get('mode');
        
        if (empty($action)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => '缺少action或mode参数'
            ]);
            return;
        }
        
        // 根据action调用相应的方法
        switch ($action) {
            case 'getColorsAPI':
                $this->getColorsAPI();
                break;
            case 'addColorAPI':
                $this->addColorAPI();
                break;
            case 'updateColorAPI':
                $this->updateColorAPI();
                break;
            case 'deleteColorAPI':
                $this->deleteColorAPI();
                break;
            case 'updateColorStockAPI':
                $this->updateColorStockAPI();
                break;
            case 'uploadSvgAPI':
                $this->uploadSvgAPI();
                break;
            case 'getCustomizationStatusAPI':
                $this->getCustomizationStatusAPI();
                break;
            case 'updateCustomizationStatusAPI':
                $this->updateCustomizationStatusAPI();
                break;
            case 'getCustomSizeAPI':
                $this->getCustomSizeAPI();
                break;
            case 'updateCustomSizeAPI':
                $this->updateCustomSizeAPI();
                break;
            case 'getWorkGuideAPI':
                $this->getWorkGuideAPI();
                break;
            case 'uploadWorkGuideAPI':
                $this->uploadWorkGuideAPI();
                break;
            // ======== 템플릿 & 커스텀 영역 API ========
            case 'getCustomTemplateAPI':
                $this->getCustomTemplateAPI();
                break;
            case 'saveCustomTemplateViewBoxAPI':
                $this->saveCustomTemplateViewBoxAPI();
                break;
            case 'addClipRegionAPI':
                $this->addClipRegionAPI();
                break;
            case 'updateClipRegionAPI':
                $this->updateClipRegionAPI();
                break;
            case 'deleteClipRegionAPI':
                $this->deleteClipRegionAPI();
                break;
            // ======== 新增：附加选项管理API ========
            case 'getExtraOptionsAPI':
                $this->getExtraOptionsAPI();
                break;
            case 'addExtraOptionAPI':
                $this->addExtraOptionAPI();
                break;
            case 'updateExtraOptionAPI':
                $this->updateExtraOptionAPI();
                break;
            case 'deleteExtraOptionAPI':
                $this->deleteExtraOptionAPI();
                break;
            case 'getExtraOptionColorsAPI':
                $this->getExtraOptionColorsAPI();
                break;
            case 'addExtraOptionColorAPI':
                $this->addExtraOptionColorAPI();
                break;
            case 'updateExtraOptionColorAPI':
                $this->updateExtraOptionColorAPI();
                break;
            case 'deleteExtraOptionColorAPI':
                $this->deleteExtraOptionColorAPI();
                break;
            // ======== 自定义尺寸（多尺寸）管理API ========
            case 'getCustomSizesAPI':
                $this->getCustomSizesAPI();
                break;
            case 'addCustomSizeAPI':
                $this->addCustomSizeAPI();
                break;
            case 'updateCustomSizeListAPI':
                $this->updateCustomSizeListAPI();
                break;
            case 'deleteCustomSizeAPI':
                $this->deleteCustomSizeAPI();
                break;
            // ======== 附加图片管理API ========
            case 'getExtraImagesAPI':
                $this->getExtraImagesAPI();
                break;
            case 'uploadExtraImageAPI':
                $this->uploadExtraImageAPI();
                break;
            case 'updateExtraImageAPI':
                $this->updateExtraImageAPI();
                break;
            case 'deleteExtraImageAPI':
                $this->deleteExtraImageAPI();
                break;
            default:
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => '未知的action: ' . $action
                ]);
                break;
        }
    }

    /**
     * 获取商品颜色列表API
     */
    public function getColorsAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id');
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            $result = $this->customColor->getGoodsColors($goodsId);
            
            $this->sendJsonResponse($result);
            
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品的模板 viewBox + 커스텀 영역列表 API
     */
    public function getCustomTemplateAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');
            if (empty($goodsId) || !is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('goods_id无效');
            }
            $goodsId = (int)$goodsId;

            // 使用自定义 PDO
            $db = $this->pdo;

            // 1) 模板主表
            $tplSql = "SELECT id, viewbox_width, viewbox_height
                         FROM custom_template
                        WHERE goods_no = ?
                          AND use_yn = 'y'
                        ORDER BY id DESC
                        LIMIT 1";
            $tpl    = $db->fetch($tplSql, [$goodsId]);

            if (!$tpl) {
                $this->sendJsonResponse([
                    'success' => true,
                    'data'    => null,
                ]);
            }

            // 2) 区域子表
            $regionSql = "SELECT sno AS clip_region_id,
                                 type,
                                 x,
                                 y,
                                 w,
                                 h,
                                 d,
                                 sort AS sort_order
                            FROM custom_template_clip_region
                           WHERE template_id = ?
                           ORDER BY sort ASC, sno ASC";
            $regions   = $db->fetchAll($regionSql, [(int)$tpl['id']]);

            $this->sendJsonResponse([
                'success' => true,
                'data'    => [
                    'template' => [
                        'id'            => (int)$tpl['id'],
                        'viewbox_width' => (float)$tpl['viewbox_width'],
                        'viewbox_height'=> (float)$tpl['viewbox_height'],
                    ],
                    'regions'  => $regions ?: [],
                ],
            ]);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 保存/更新模板 viewBox API
     */
    public function saveCustomTemplateViewBoxAPI()
    {
        try {
            $goodsId = Request::post()->get('goods_id');
            $vw      = Request::post()->get('viewbox_width');
            $vh      = Request::post()->get('viewbox_height');

            if (empty($goodsId) || !is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('goods_id无效');
            }
            $goodsId = (int)$goodsId;

            if ($vw === '' || $vh === '' || !is_numeric($vw) || !is_numeric($vh) || (float)$vw <= 0 || (float)$vh <= 0) {
                throw new Exception('viewBox宽高必须为正数');
            }

            $db = $this->pdo;

            $tpl = $db->fetch("SELECT id FROM custom_template WHERE goods_no = ? LIMIT 1", [$goodsId]);
            if ($tpl) {
                $db->execute(
                    "UPDATE custom_template
                        SET viewbox_width = ?, viewbox_height = ?
                      WHERE id = ?",
                    [(float)$vw, (float)$vh, (int)$tpl['id']]
                );
            } else {
                $db->execute(
                    "INSERT INTO custom_template (goods_no, template_name, svg_path, viewbox_width, viewbox_height, use_yn)
                     VALUES (?, '', '', ?, ?, 'y')",
                    [$goodsId, (float)$vw, (float)$vh]
                );
            }

            $this->sendJsonResponse([
                'success' => true,
                'message' => 'viewBox已保存',
            ]);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 新增 커스텀 영역 API
     */
    public function addClipRegionAPI()
    {
        $this->saveOrUpdateClipRegion(false);
    }

    /**
     * 更新 커스텀 영역 API
     */
    public function updateClipRegionAPI()
    {
        $this->saveOrUpdateClipRegion(true);
    }

    /**
     * 删除 커스텀 영역 API
     */
    public function deleteClipRegionAPI()
    {
        try {
            $clipId = Request::post()->get('clip_region_id');
            if (empty($clipId) || !is_numeric($clipId) || (int)$clipId <= 0) {
                throw new Exception('clip_region_id无效');
            }

            $db = $this->pdo;
            $db->execute("DELETE FROM custom_template_clip_region WHERE sno = ?", [(int)$clipId]);

            $this->sendJsonResponse([
                'success' => true,
                'message' => '커스텀 영역已删除',
            ]);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 내부共用：新增/更新 커스텀 영역
     */
    private function saveOrUpdateClipRegion($isUpdate)
    {
        try {
            $goodsId = Request::post()->get('goods_id');
            $type    = Request::post()->get('type');
            $x       = Request::post()->get('x');
            $y       = Request::post()->get('y');
            $w       = Request::post()->get('w');
            $h       = Request::post()->get('h');
            $d       = Request::post()->get('d');
            $sort    = Request::post()->get('sort_order', 0);
            $clipId  = Request::post()->get('clip_region_id');

            if (empty($goodsId) || !is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('goods_id无效');
            }
            $goodsId = (int)$goodsId;

            if (!in_array($type, ['rect', 'path'], true)) {
                throw new Exception('type无效');
            }

            if ($type === 'rect') {
                if ($x === '' || $y === '' || $w === '' || $h === '') {
                    throw new Exception('rect 类型需要填写 X/Y/W/H');
                }
                if (!is_numeric($x) || !is_numeric($y) || !is_numeric($w) || !is_numeric($h)) {
                    throw new Exception('X/Y/W/H 必须是数字');
                }
            } else { // path
                if ($d === null || $d === '') {
                    throw new Exception('path 类型需要填写 d');
                }
            }

            $db = $this->pdo;

            $tpl = $db->fetch("SELECT id FROM custom_template WHERE goods_no = ? LIMIT 1", [$goodsId]);
            if (!$tpl) {
                throw new Exception('请先保存模板 viewBox');
            }
            $templateId = (int)$tpl['id'];

            $xVal = ($x === '' ? null : (float)$x);
            $yVal = ($y === '' ? null : (float)$y);
            $wVal = ($w === '' ? null : (float)$w);
            $hVal = ($h === '' ? null : (float)$h);
            $sortVal = (int)$sort;

            if ($isUpdate && !empty($clipId) && is_numeric($clipId) && (int)$clipId > 0) {
                $db->execute(
                    "UPDATE custom_template_clip_region
                        SET type = ?, x = ?, y = ?, w = ?, h = ?, d = ?, sort = ?
                      WHERE sno = ? AND template_id = ?",
                    [$type, $xVal, $yVal, $wVal, $hVal, $d, $sortVal, (int)$clipId, $templateId]
                );
            } else {
                $db->execute(
                    "INSERT INTO custom_template_clip_region (template_id, type, x, y, w, h, d, sort)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$templateId, $type, $xVal, $yVal, $wVal, $hVal, $d, $sortVal]
                );
            }

            $this->sendJsonResponse([
                'success' => true,
                'message' => '커스텀 영역已保存',
            ]);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    

    /**
     * 添加颜色API
     */
    public function addColorAPI()
    {
        try {
            $goodsId = Request::post()->get('goods_id');
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            $colorName = Request::post()->get('color_name');
            $colorCode = Request::post()->get('color_code');
            $colorHex = Request::post()->get('color_hex');
            $svgImagePath = Request::post()->get('svg_image_path');
            $sortOrder = Request::post()->get('sort_order', 0);
            $stockStatus = Request::post()->get('stock_status', 1);
            
            if (empty($colorName)) {
                throw new Exception('颜色名称不能为空');
            }
            
            if (empty($colorCode)) {
                throw new Exception('颜色代码不能为空');
            }
            
            if (empty($colorHex)) {
                throw new Exception('颜色HEX值不能为空');
            }
            
            $colorData = [
                'goods_id' => (int)$goodsId,
                'color_name' => $colorName,
                'color_code' => $colorCode,
                'color_hex' => $colorHex,
                'svg_image_path' => $svgImagePath ?? '',
                'sort_order' => (int)$sortOrder,
                'stock_status' => (int)$stockStatus ? 1 : 0,
            ];
            
            $result = $this->customColor->addGoodsColor($colorData);
            
            $this->sendJsonResponse($result);
            
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    

    /**
     * 更新颜色API
     */
    public function updateColorAPI()
    {
        try {
            $colorId = Request::post()->get('color_id');
            $goodsId = Request::post()->get('goods_id');
            
            if (empty($colorId)) {
                throw new Exception('颜色ID不能为空');
            }
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            $updateData = [];
            
            if (Request::post()->has('color_name')) {
                $updateData['color_name'] = Request::post()->get('color_name');
            }
            
            if (Request::post()->has('color_code')) {
                $updateData['color_code'] = Request::post()->get('color_code');
            }
            
            if (Request::post()->has('color_hex')) {
                $updateData['color_hex'] = Request::post()->get('color_hex');
            }
            
            if (Request::post()->has('svg_image_path')) {
                $updateData['svg_image_path'] = Request::post()->get('svg_image_path');
            }
            
            if (Request::post()->has('sort_order')) {
                $updateData['sort_order'] = (int)Request::post()->get('sort_order');
            }
            
            if (Request::post()->has('stock_status')) {
                $updateData['stock_status'] = (int)Request::post()->get('stock_status') ? 1 : 0;
            }
            
            if (empty($updateData)) {
                throw new Exception('没有要更新的数据');
            }
            
            $result = $this->customColor->updateGoodsColor($colorId, $updateData);
            
            $this->sendJsonResponse($result);
            
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 删除颜色API
     */
    public function deleteColorAPI()
    {
        try {
            $colorId = Request::post()->get('color_id');
            $goodsId = Request::post()->get('goods_id');
            
            if (empty($colorId)) {
                throw new Exception('颜色ID不能为空');
            }
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            $result = $this->customColor->deleteGoodsColor($colorId);
            
            $this->sendJsonResponse($result);
            
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新颜色库存状态API
     */
    public function updateColorStockAPI()
    {
        try {
            $colorId = Request::post()->get('color_id');
            $stockStatus = Request::post()->get('stock_status');

            if (empty($colorId) || !is_numeric($colorId) || (int)$colorId <= 0) {
                throw new Exception('颜色ID无效');
            }
            if ($stockStatus === null) {
                throw new Exception('缺少库存状态参数');
            }

            $result = $this->customColor->updateColorStockStatus((int)$colorId, (int)$stockStatus);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 获取商品附加选项列表API
     */
    public function getExtraOptionsAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            $result = $this->customExtraOption->getGoodsExtraOptions((int)$goodsId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 新增商品附加选项API
     */
    public function addExtraOptionAPI()
    {
        try {
            $goodsId = Request::post()->get('goods_id');
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            $optionGroup = Request::post()->get('option_group');
            $optionLabel = Request::post()->get('option_label');
            $optionDesc  = Request::post()->get('option_desc');
            $inputType   = Request::post()->get('input_type', 'checkbox');
            $extraPrice  = Request::post()->get('extra_price', 0);
            $priceType   = Request::post()->get('price_type', 'per_unit');
            $isRequired  = Request::post()->get('is_required', 0);
            $status      = Request::post()->get('status', 1);
            $sortOrder   = Request::post()->get('sort_order', 0);
            
            if (empty($optionGroup)) {
                throw new Exception('option_group不能为空');
            }
            if (empty($optionLabel)) {
                throw new Exception('option_label不能为空');
            }
            
            $allowedInputTypes = ['checkbox', 'radio', 'color_palette'];
            if (!in_array($inputType, $allowedInputTypes, true)) {
                $inputType = 'checkbox';
            }
            $allowedPriceTypes = ['per_unit', 'per_order'];
            if (!in_array($priceType, $allowedPriceTypes, true)) {
                $priceType = 'per_unit';
            }
            
            $optionData = [
                'goods_id'     => (int)$goodsId,
                'option_group' => $optionGroup,
                'option_label' => $optionLabel,
                'option_desc'  => $optionDesc,
                'input_type'   => $inputType,
                'extra_price'  => (int)$extraPrice,
                'price_type'   => $priceType,
                'is_required'  => (int)$isRequired,
                'status'       => (int)$status,
                'sort_order'   => (int)$sortOrder,
            ];
            
            $result = $this->customExtraOption->addGoodsExtraOption($optionData);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 更新商品附加选项API
     */
    public function updateExtraOptionAPI()
    {
        try {
            $optionId = Request::post()->get('option_id');
            if (empty($optionId) || !is_numeric($optionId) || (int)$optionId <= 0) {
                throw new Exception('option_id无效');
            }
            
            $updateData = [];
            if (Request::post()->has('option_group')) {
                $updateData['option_group'] = Request::post()->get('option_group');
            }
            if (Request::post()->has('option_label')) {
                $updateData['option_label'] = Request::post()->get('option_label');
            }
            if (Request::post()->has('option_desc')) {
                $updateData['option_desc'] = Request::post()->get('option_desc');
            }
            if (Request::post()->has('input_type')) {
                $inputType = Request::post()->get('input_type');
                $allowedInputTypes = ['checkbox', 'radio', 'color_palette'];
                if (!in_array($inputType, $allowedInputTypes, true)) {
                    throw new Exception('input_type无效');
                }
                $updateData['input_type'] = $inputType;
            }
            if (Request::post()->has('extra_price')) {
                $updateData['extra_price'] = (int)Request::post()->get('extra_price');
            }
            if (Request::post()->has('price_type')) {
                $priceType = Request::post()->get('price_type');
                $allowedPriceTypes = ['per_unit', 'per_order'];
                if (!in_array($priceType, $allowedPriceTypes, true)) {
                    throw new Exception('price_type无效');
                }
                $updateData['price_type'] = $priceType;
            }
            if (Request::post()->has('is_required')) {
                $updateData['is_required'] = (int)Request::post()->get('is_required');
            }
            if (Request::post()->has('status')) {
                $updateData['status'] = (int)Request::post()->get('status');
            }
            if (Request::post()->has('sort_order')) {
                $updateData['sort_order'] = (int)Request::post()->get('sort_order');
            }
            
            if (empty($updateData)) {
                throw new Exception('没有要更新的数据');
            }
            
            $result = $this->customExtraOption->updateGoodsExtraOption((int)$optionId, $updateData);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 删除商品附加选项API
     */
    public function deleteExtraOptionAPI()
    {
        try {
            $optionId = Request::post()->get('option_id');
            if (empty($optionId) || !is_numeric($optionId) || (int)$optionId <= 0) {
                throw new Exception('option_id无效');
            }
            $result = $this->customExtraOption->deleteGoodsExtraOption((int)$optionId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 获取附加选项颜色列表API
     */
    public function getExtraOptionColorsAPI()
    {
        try {
            $optionId = Request::get()->get('option_id') ?: Request::post()->get('option_id');
            if (empty($optionId) || !is_numeric($optionId) || (int)$optionId <= 0) {
                throw new Exception('option_id无效');
            }
            $result = $this->customExtraOption->getOptionColors((int)$optionId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 新增附加选项颜色API
     */
    public function addExtraOptionColorAPI()
    {
        try {
            $optionId  = Request::post()->get('option_id');
            $colorName = Request::post()->get('color_name');
            $colorCode = Request::post()->get('color_code');
            $colorHex  = Request::post()->get('color_hex');
            $sortOrder = Request::post()->get('sort_order', 0);
            
            if (empty($optionId) || !is_numeric($optionId) || (int)$optionId <= 0) {
                throw new Exception('option_id无效');
            }
            if (empty($colorName)) {
                throw new Exception('颜色名称不能为空');
            }
            if (empty($colorHex)) {
                throw new Exception('颜色HEX值不能为空');
            }
            
            $colorData = [
                'option_id'  => (int)$optionId,
                'color_name' => $colorName,
                'color_code' => $colorCode,
                'color_hex'  => $colorHex,
                'sort_order' => (int)$sortOrder,
            ];
            
            $result = $this->customExtraOption->addOptionColor($colorData);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 更新附加选项颜色API
     */
    public function updateExtraOptionColorAPI()
    {
        try {
            $colorId = Request::post()->get('color_id');
            if (empty($colorId) || !is_numeric($colorId) || (int)$colorId <= 0) {
                throw new Exception('color_id无效');
            }
            
            $updateData = [];
            if (Request::post()->has('color_name')) {
                $updateData['color_name'] = Request::post()->get('color_name');
            }
            if (Request::post()->has('color_code')) {
                $updateData['color_code'] = Request::post()->get('color_code');
            }
            if (Request::post()->has('color_hex')) {
                $updateData['color_hex'] = Request::post()->get('color_hex');
            }
            if (Request::post()->has('sort_order')) {
                $updateData['sort_order'] = (int)Request::post()->get('sort_order');
            }
            if (Request::post()->has('status')) {
                $updateData['status'] = (int)Request::post()->get('status');
            }
            
            if (empty($updateData)) {
                throw new Exception('没有要更新的数据');
            }
            
            $result = $this->customExtraOption->updateOptionColor((int)$colorId, $updateData);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 删除附加选项颜色API
     */
    public function deleteExtraOptionColorAPI()
    {
        try {
            $colorId = Request::post()->get('color_id');
            if (empty($colorId) || !is_numeric($colorId) || (int)$colorId <= 0) {
                throw new Exception('color_id无效');
            }
            
            $result = $this->customExtraOption->deleteOptionColor((int)$colorId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }


    /**
     * SVG上传API（使用PHP原生文件操作）
     */
    public function uploadSvgAPI()
    {
        try {
            // 使用Godomall框架的标准方式获取上传文件
            $uploadedFile = Request::files()->get('svg_file');
            
            if (!$uploadedFile) {
                throw new Exception('请选择要上传的SVG文件');
            }
            
            // 检查上传错误
            if ((int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败 (错误代码: ' . $uploadedFile['error'] . ')');
            }
            
            // 获取文件信息（数组格式）
            $tmpPath = $uploadedFile['tmp_name'];
            $originalName = $uploadedFile['name'];
            $mimeType = $uploadedFile['type'];
            $fileSize = $uploadedFile['size'];
            
            $goodsId = Request::post()->get('goods_id');
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            Logger::info('颜色SVG上传开始', [
                'goods_id' => $goodsId,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'tmp_path' => $tmpPath,
                'tmp_exists' => file_exists($tmpPath)
            ]);
            
            // 基本验证
            if ($fileSize > 20 * 1024 * 1024) {
                throw new Exception('文件大小超过20MB限制');
            }
            
            if ($fileSize === 0) {
                throw new Exception('文件大小为0');
            }
            
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== 'svg') {
                throw new Exception('仅支持SVG格式图片，当前格式: ' . $extension);
            }
            
            // 生成唯一文件名
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.svg';
            
            // 构建目标路径 - 使用 Godomall 官方方法
            $goodsDataPath = UserFilePath::data('goods');  // 获取 data/goods 绝对路径
            $targetDir = (string)$goodsDataPath . '/colors/' . $goodsId;
            $targetFile = $targetDir . '/' . $uniqueFileName;
            $httpPath = '/data/goods/colors/' . $goodsId . '/' . $uniqueFileName;
            
            Logger::info('目标路径信息', [
                'goods_data_path' => (string)$goodsDataPath,
                'target_dir' => $targetDir,
                'target_file' => $targetFile,
                'http_path' => $httpPath,
                'target_dir_exists' => is_dir($targetDir)
            ]);
            
            // 确保目录存在
            if (!is_dir($targetDir)) {
                if (!@mkdir($targetDir, 0777, true)) {
                    $error = error_get_last();
                    Logger::error('无法创建目标目录', [
                        'dir' => $targetDir,
                        'error' => $error ? $error['message'] : 'unknown'
                    ]);
                    throw new Exception('无法创建目标目录');
                }
                Logger::info('创建目标目录成功', ['dir' => $targetDir]);
            }
            
            // 复制文件到目标位置
            if (!@copy($tmpPath, $targetFile)) {
                $error = error_get_last();
                Logger::error('文件复制失败', [
                    'source' => $tmpPath,
                    'target' => $targetFile,
                    'source_exists' => file_exists($tmpPath),
                    'source_readable' => is_readable($tmpPath),
                    'target_dir_writable' => is_writable($targetDir),
                    'error' => $error ? $error['message'] : 'unknown'
                ]);
                throw new Exception('文件复制失败');
            }
            
            // 设置文件权限
            @chmod($targetFile, 0644);
            
            // 验证文件已成功写入
            if (!file_exists($targetFile)) {
                Logger::error('文件写入后不存在', ['target' => $targetFile]);
                throw new Exception('文件写入失败');
            }
            
            $finalSize = filesize($targetFile);
            
            Logger::info('颜色SVG上传成功', [
                'goods_id' => $goodsId,
                'original_name' => $originalName,
                'target_file' => $targetFile,
                'http_path' => $httpPath,
                'file_size' => $finalSize,
                'original_size' => $fileSize
            ]);
            
            $this->sendJsonResponse([
                'success' => true,
                'data' => ['svg_url' => $httpPath],
                'message' => 'SVG上传成功'
            ]);
            
        } catch (Exception $e) {
            Logger::error('颜色SVG上传异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null,
                'original_name' => $originalName ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品附加图片列表API
     */
    public function getExtraImagesAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');
            if (empty($goodsId) || !is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }

            Logger::info('查询商品附加图片列表', ['goods_id' => $goodsId]);

            $result = $this->customExtraImage->getGoodsExtraImages((int)$goodsId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            Logger::error('获取商品附加图片列表异常', [
                'goods_id' => $goodsId ?? null,
                'error'    => $e->getMessage(),
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'data'    => [],
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 附加图片上传API（SVG）
     */
    public function uploadExtraImageAPI()
    {
        $goodsId = null;
        $originalName = null;

        try {
            $goodsId = Request::post()->get('goods_id');
            if (empty($goodsId) || !is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }

            $uploadedFile = Request::files()->get('extra_image_file');
            if (!$uploadedFile) {
                throw new Exception('请选择要上传的SVG文件');
            }
            if ((int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败 (错误代码: ' . $uploadedFile['error'] . ')');
            }

            $tmpPath      = $uploadedFile['tmp_name'];
            $originalName = $uploadedFile['name'];
            $mimeType     = $uploadedFile['type'];
            $fileSize     = $uploadedFile['size'];

            if ($fileSize <= 0) {
                throw new Exception('文件大小为0');
            }
            if ($fileSize > 20 * 1024 * 1024) {
                throw new Exception('文件大小超过20MB限制');
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== 'svg') {
                throw new Exception('仅支持SVG格式图片，当前格式: ' . $extension);
            }

            Logger::info('附加图片SVG上传开始', [
                'goods_id'      => $goodsId,
                'original_name' => $originalName,
                'mime_type'     => $mimeType,
                'file_size'     => $fileSize,
                'tmp_path'      => $tmpPath,
                'tmp_exists'    => file_exists($tmpPath),
            ]);

            $baseName       = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.svg';

            $goodsDataPath = UserFilePath::data('goods');
            $targetDir     = (string)$goodsDataPath . '/extra_images/' . $goodsId;
            $targetFile    = $targetDir . '/' . $uniqueFileName;
            $httpPath      = '/data/goods/extra_images/' . $goodsId . '/' . $uniqueFileName;

            Logger::info('附加图片目标路径信息', [
                'goods_data_path' => (string)$goodsDataPath,
                'target_dir'      => $targetDir,
                'target_file'     => $targetFile,
                'http_path'       => $httpPath,
                'target_dir_exists' => is_dir($targetDir),
            ]);

            if (!is_dir($targetDir)) {
                if (!@mkdir($targetDir, 0777, true)) {
                    $error = error_get_last();
                    Logger::error('无法创建附加图片目标目录', [
                        'dir'   => $targetDir,
                        'error' => $error ? $error['message'] : 'unknown',
                    ]);
                    throw new Exception('无法创建目标目录');
                }
            }

            if (!@copy($tmpPath, $targetFile)) {
                $error = error_get_last();
                Logger::error('附加图片文件复制失败', [
                    'source'             => $tmpPath,
                    'target'             => $targetFile,
                    'source_exists'      => file_exists($tmpPath),
                    'source_readable'    => is_readable($tmpPath),
                    'target_dir_writable'=> is_writable($targetDir),
                    'error'              => $error ? $error['message'] : 'unknown',
                ]);
                throw new Exception('文件复制失败');
            }

            @chmod($targetFile, 0644);

            if (!file_exists($targetFile)) {
                Logger::error('附加图片文件写入后不存在', ['target' => $targetFile]);
                throw new Exception('文件写入失败');
            }

            // 写入数据库
            $imageName = $baseName;
            $result    = $this->customExtraImage->addGoodsExtraImage([
                'goods_id'   => (int)$goodsId,
                'image_type' => 'package',
                'image_name' => $imageName,
                'image_path' => $httpPath,
                'status'     => 1,
            ]);

            if (!$result['success']) {
                throw new Exception($result['message'] ?? '数据库写入失败');
            }

            Logger::info('附加图片SVG上传成功', [
                'goods_id'      => $goodsId,
                'image_id'      => $result['data']['image_id'] ?? null,
                'http_path'     => $httpPath,
                'original_name' => $originalName,
            ]);

            $this->sendJsonResponse([
                'success' => true,
                'data'    => [
                    'image_id'   => $result['data']['image_id'] ?? null,
                    'image_name' => $imageName,
                    'image_path' => $httpPath,
                ],
                'message' => '附加图片上传成功',
            ]);
        } catch (Exception $e) {
            Logger::error('附加图片SVG上传异常', [
                'goods_id'      => $goodsId ?? null,
                'original_name' => $originalName ?? null,
                'error'         => $e->getMessage(),
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 更新附加图片元数据API
     */
    public function updateExtraImageAPI()
    {
        try {
            $imageId = Request::post()->get('image_id');
            if (empty($imageId) || !is_numeric($imageId) || (int)$imageId <= 0) {
                throw new Exception('图片ID无效');
            }

            $data = [];
            if (Request::post()->has('image_name')) {
                $data['image_name'] = Request::post()->get('image_name');
            }
            if (Request::post()->has('image_type')) {
                $data['image_type'] = Request::post()->get('image_type');
            }
            if (Request::post()->has('status')) {
                $data['status'] = (int)Request::post()->get('status');
            }

            $result = $this->customExtraImage->updateGoodsExtraImage((int)$imageId, $data);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            Logger::error('更新附加图片异常', [
                'image_id' => $imageId ?? null,
                'error'    => $e->getMessage(),
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 删除附加图片API（软删除）
     */
    public function deleteExtraImageAPI()
    {
        try {
            $imageId = Request::post()->get('image_id');
            if (empty($imageId) || !is_numeric($imageId) || (int)$imageId <= 0) {
                throw new Exception('图片ID无效');
            }

            $result = $this->customExtraImage->deleteGoodsExtraImage((int)$imageId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            Logger::error('删除附加图片异常', [
                'image_id' => $imageId ?? null,
                'error'    => $e->getMessage(),
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 获取商品定制化状态API
     * 
     * @return void
     */
    public function getCustomizationStatusAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            Logger::info('查询商品定制化状态', [
                'goods_id' => $goodsId
            ]);
            
            // 从 custom_goods_extension 表获取定制化状态
            $status = $this->customColor->getCustomizationStatus($goodsId);
            
            $this->sendJsonResponse([
                'success' => true,
                'data' => [
                    'goods_id' => $goodsId,
                    'is_customizable' => $status
                ],
                'message' => '获取成功'
            ]);
            
        } catch (Exception $e) {
            Logger::error('获取商品定制化状态异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新商品定制化状态API
     * 
     * @return void
     */
    public function updateCustomizationStatusAPI()
    {
        try {
            $goodsId = Request::post()->get('goods_id');
            $isCustomizable = Request::post()->get('is_customizable');
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            // 验证定制化状态值
            if (!isset($isCustomizable) || !in_array($isCustomizable, ['0', '1', 0, 1], true)) {
                throw new Exception('定制化状态值无效，必须为0或1');
            }
            
            Logger::info('更新商品定制化状态', [
                'goods_id' => $goodsId,
                'is_customizable' => $isCustomizable
            ]);
            
            // 更新 custom_goods_extension 表的定制化状态
            $result = $this->customColor->updateCustomizationStatus($goodsId, (int)$isCustomizable);
            
            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'goods_id' => $goodsId,
                        'is_customizable' => (int)$isCustomizable
                    ],
                    'message' => '更新成功'
                ]);
            } else {
                throw new Exception('更新失败，请重试');
            }
            
        } catch (Exception $e) {
            Logger::error('更新商品定制化状态异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null,
                'is_customizable' => $isCustomizable ?? null
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品自定义尺寸API
     * 
     * @return void
     */
    public function getCustomSizeAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            Logger::info('查询商品自定义尺寸', [
                'goods_id' => $goodsId
            ]);
            
            // 从 custom_goods_extension 表获取自定义尺寸
            $sizeData = $this->customColor->getCustomSize($goodsId);
            
            $this->sendJsonResponse([
                'success' => true,
                'data' => $sizeData,
                'message' => '获取成功'
            ]);
            
        } catch (Exception $e) {
            Logger::error('获取商品自定义尺寸异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新商品自定义尺寸API
     * 
     * @return void
     */
    public function updateCustomSizeAPI()
    {
        try {
            $goodsId = Request::post()->get('goods_id');
            $customWidth = Request::post()->get('custom_width');
            $customHeight = Request::post()->get('custom_height');
            
            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            
            // 验证商品ID必须是正整数
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            
            // 验证宽度和高度（允许为空或null）
            if ($customWidth !== null && $customWidth !== '') {
                if (!is_numeric($customWidth) || floatval($customWidth) < 0 || floatval($customWidth) > 999.99) {
                    throw new Exception('宽度必须在 0-999.99 范围内');
                }
                $customWidth = floatval($customWidth);
            } else {
                $customWidth = null;
            }
            
            if ($customHeight !== null && $customHeight !== '') {
                if (!is_numeric($customHeight) || floatval($customHeight) < 0 || floatval($customHeight) > 999.99) {
                    throw new Exception('高度必须在 0-999.99 范围内');
                }
                $customHeight = floatval($customHeight);
            } else {
                $customHeight = null;
            }
            
            Logger::info('更新商品自定义尺寸', [
                'goods_id' => $goodsId,
                'custom_width' => $customWidth,
                'custom_height' => $customHeight
            ]);
            
            // 更新 custom_goods_extension 表的自定义尺寸
            $result = $this->customColor->updateCustomSize($goodsId, $customWidth, $customHeight);
            
            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'goods_id' => $goodsId,
                        'custom_width' => $customWidth,
                        'custom_height' => $customHeight
                    ],
                    'message' => '更新成功'
                ]);
            } else {
                throw new Exception('更新失败，请重试');
            }
            
        } catch (Exception $e) {
            Logger::error('更新商品自定义尺寸异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null,
                'custom_width' => $customWidth ?? null,
                'custom_height' => $customHeight ?? null
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品작업 가이드API
     *
     * @return void
     */
    public function getWorkGuideAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');

            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }

            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }

            Logger::info('查询商品작업 가이드', [
                'goods_id' => $goodsId
            ]);

            $path = $this->customColor->getWorkGuidePath($goodsId);

            $this->sendJsonResponse([
                'success' => true,
                'data' => [
                    'goods_id' => (int)$goodsId,
                    'work_guide_path' => $path
                ],
                'message' => '获取成功'
            ]);
        } catch (Exception $e) {
            Logger::error('获取商品작업 가이드异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId ?? null
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 上传/清除商品작업 가이드API
     *
     * @return void
     */
    public function uploadWorkGuideAPI()
    {
        $goodsId = null;
        $originalName = null;

        try {
            $goodsId = Request::post()->get('goods_id');
            $clearFlag = Request::post()->get('clear_work_guide', 0);

            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }

            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }

            if ((int)$clearFlag === 1) {
                Logger::info('清除商品작업 가이드', [
                    'goods_id' => $goodsId
                ]);

                $result = $this->customColor->updateWorkGuidePath($goodsId, null);
                if ($result) {
                    $this->sendJsonResponse([
                        'success' => true,
                        'data' => [
                            'goods_id' => (int)$goodsId,
                            'work_guide_path' => null
                        ],
                        'message' => '작업 가이드已清除'
                    ]);
                } else {
                    throw new Exception('清除작업 가이드失败，请重试');
                }
            }

            $uploadedFile = Request::files()->get('work_guide_file');

            if (!$uploadedFile) {
                throw new Exception('请选择要上传的文件');
            }

            if ((int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败 (错误代码: ' . $uploadedFile['error'] . ')');
            }

            $tmpPath = $uploadedFile['tmp_name'];
            $originalName = $uploadedFile['name'];
            $fileSize = $uploadedFile['size'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'svg'];
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new Exception('仅支持 PDF/JPG/PNG/SVG 格式，当前格式: ' . $extension);
            }

            if ($fileSize <= 0) {
                throw new Exception('文件大小为0');
            }

            if ($fileSize > 20 * 1024 * 1024) {
                throw new Exception('文件大小超过20MB限制');
            }

            Logger::info('작업 가이드上传开始', [
                'goods_id' => $goodsId,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'tmp_path' => $tmpPath
            ]);

            $goodsDataPath = UserFilePath::data('goods');
            $targetDir = (string)$goodsDataPath . '/work_guides/' . $goodsId;

            if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
                $error = error_get_last();
                Logger::error('无法创建작업 가이드目录', [
                    'dir' => $targetDir,
                    'error' => $error ? $error['message'] : 'unknown'
                ]);
                throw new Exception('无法创建目标目录');
            }

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $targetFile = $targetDir . '/' . $uniqueFileName;

            if (!@copy($tmpPath, $targetFile)) {
                $error = error_get_last();
                Logger::error('작업 가이드文件复制失败', [
                    'source' => $tmpPath,
                    'target' => $targetFile,
                    'error' => $error ? $error['message'] : 'unknown'
                ]);
                throw new Exception('文件复制失败');
            }

            @chmod($targetFile, 0644);

            if (!file_exists($targetFile)) {
                Logger::error('작업 가이드文件写入后不存在', [
                    'target' => $targetFile
                ]);
                throw new Exception('文件写入失败');
            }

            $httpPath = '/data/goods/work_guides/' . $goodsId . '/' . $uniqueFileName;

            $updateResult = $this->customColor->updateWorkGuidePath($goodsId, $httpPath);

            if (!$updateResult) {
                throw new Exception('작업 가이드路径更新失败，请重试');
            }

            $this->sendJsonResponse([
                'success' => true,
                'data' => [
                    'goods_id' => (int)$goodsId,
                    'work_guide_path' => $httpPath
                ],
                'message' => '작업 가이드上传成功'
            ]);
        } catch (Exception $e) {
            Logger::error('上传商品작업 가이드异常', [
                'error' => $e->getMessage(),
                'goods_id' => $goodsId,
                'original_name' => $originalName
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取商品自定义尺寸列表API（多尺寸）
     */
    public function getCustomSizesAPI()
    {
        try {
            $goodsId = Request::get()->get('goods_id') ?: Request::post()->get('goods_id');

            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }

            Logger::info('查询商品自定义尺寸列表', [
                'goods_id' => $goodsId,
            ]);

            $result = $this->customSize->getGoodsSizes((int)$goodsId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            Logger::error('获取商品自定义尺寸列表异常', [
                'error'    => $e->getMessage(),
                'goods_id' => $goodsId ?? null,
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'data'    => [],
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 新增商品自定义尺寸API（多尺寸）
     */
    public function addCustomSizeAPI()
    {
        try {
            $goodsId   = Request::post()->get('goods_id');
            $sizeName  = Request::post()->get('size_name');
            $sizeWidth = Request::post()->get('size_width');
            $sizeHeight= Request::post()->get('size_height');
            $status    = Request::post()->get('status', 1);

            if (empty($goodsId)) {
                throw new Exception('商品ID不能为空');
            }
            if (!is_numeric($goodsId) || (int)$goodsId <= 0) {
                throw new Exception('商品ID必须是有效的正整数');
            }
            if (empty($sizeName)) {
                throw new Exception('尺寸名称不能为空');
            }
            if ($sizeWidth === '' || $sizeHeight === '') {
                throw new Exception('너비와 높이를 모두 입력해주세요');
            }
            if (!is_numeric($sizeWidth) || floatval($sizeWidth) < 0 || floatval($sizeWidth) > 9999.99) {
                throw new Exception('너비는 0-9999.99 범위여야 합니다');
            }
            if (!is_numeric($sizeHeight) || floatval($sizeHeight) < 0 || floatval($sizeHeight) > 9999.99) {
                throw new Exception('높이는 0-9999.99 범위여야 합니다');
            }

            $data = [
                'goods_id'   => (int)$goodsId,
                'size_name'  => $sizeName,
                'size_width' => (float)$sizeWidth,
                'size_height'=> (float)$sizeHeight,
                'size_unit'  => 'mm',
                'status'     => (int)$status,
            ];

            $result = $this->customSize->addGoodsSize($data);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            Logger::error('新增商品自定义尺寸异常', [
                'error'    => $e->getMessage(),
                'goods_id' => $goodsId ?? null,
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 批量更新商品自定义尺寸排序/状态API（可选）
     */
    public function updateCustomSizeListAPI()
    {
        try {
            $sizes = Request::post()->get('sizes');
            if (!is_array($sizes) || empty($sizes)) {
                throw new Exception('尺寸列表不能为空');
            }

            foreach ($sizes as $row) {
                if (empty($row['custom_size_id'])) {
                    continue;
                }
                $update = [];
                if (isset($row['size_name'])) {
                    $update['size_name'] = $row['size_name'];
                }
                if (isset($row['size_width'])) {
                    $update['size_width'] = $row['size_width'];
                }
                if (isset($row['size_height'])) {
                    $update['size_height'] = $row['size_height'];
                }
                if (isset($row['size_unit'])) {
                    $update['size_unit'] = $row['size_unit'];
                }
                if (isset($row['status'])) {
                    $update['status'] = (int)$row['status'];
                }
                if (!empty($update)) {
                    $this->customSize->updateGoodsSize((int)$row['custom_size_id'], $update);
                }
            }

            $this->sendJsonResponse([
                'success' => true,
                'message' => '尺寸列表已更新',
            ]);
        } catch (Exception $e) {
            Logger::error('批量更新商品自定义尺寸异常', [
                'error' => $e->getMessage(),
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 删除商品自定义尺寸API（多尺寸）
     */
    public function deleteCustomSizeAPI()
    {
        try {
            $customSizeId = Request::post()->get('custom_size_id');

            if (empty($customSizeId) || !is_numeric($customSizeId) || (int)$customSizeId <= 0) {
                throw new Exception('尺寸ID无效');
            }

            $result = $this->customSize->deleteGoodsSize((int)$customSizeId);
            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            Logger::error('删除商品自定义尺寸异常', [
                'error'         => $e->getMessage(),
                'custom_size_id'=> $customSizeId ?? null,
            ]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

