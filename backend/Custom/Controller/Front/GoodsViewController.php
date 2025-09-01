<?php

/**
 * 扩展商品详情页控制器
 * 
 * 继承官方GoodsViewController，添加商品定制和购买功能
 * 严格遵循Godomall开发规范，所有自定义功能通过继承和扩展实现
 * 
 * @package Controller\Front\Goods
 * @author Custom Development Team
 * @version 1.0
 */

namespace Controller\Front\Goods;

use Custom\Component\CustomGoods;
use Custom\Component\CustomPattern;
use Framework\Debug\Exception\AlertBackException;
use Exception;
use Logger;
use Request;
use Session;

/**
 * 扩展商品详情页控制器
 */
class GoodsViewController extends \Bundle\Controller\Front\Goods\GoodsViewController
{
    /**
     * 商品定制组件
     * @var CustomGoods
     */
    private $customGoods;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->customGoods = new CustomGoods();
    }

    /**
     * 商品详情页面 - 继承并扩展父类功能
     */
    public function index()
    {
        // 调用父类的index方法
        parent::index();
        
        try {
            $goodsNo = Request::get()->get('goodsNo');
            
            if ($goodsNo) {
                // 检查商品是否支持定制
                $isCustomizable = $this->checkGoodsCustomizable($goodsNo);
                
                // 设置定制相关数据
                $this->setData('is_customizable', $isCustomizable);
                $this->setData('custom_url', $this->buildCustomUrl($goodsNo));
                
                // 如果支持定制，获取相关图案数量
                if ($isCustomizable) {
                    $patternCount = $this->getGoodsPatternsCount($goodsNo);
                    $this->setData('pattern_count', $patternCount);
                }
                
                // 设置购买按钮相关数据
                $this->setData('purchase_button_config', $this->getPurchaseButtonConfig($isCustomizable));
                
                // 添加定制页面相关的JavaScript和CSS
                if ($isCustomizable) {
                    $this->addScript(['custom_goods_purchase.js'], false, true);
                    $this->addCss(['custom_goods_purchase.css']);
                }
            }
            
        } catch (Exception $e) {
            Logger::error('扩展商品详情页异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo ?? null
            ]);
            // 不抛出异常，确保原有功能正常
        }
    }

    /**
     * 检查商品是否支持定制
     * 
     * @param int $goodsNo 商品编号
     * @return bool
     */
    private function checkGoodsCustomizable($goodsNo)
    {
        try {
            $db = \App::load('DB');
            $sql = "SELECT is_customizable FROM es_goods WHERE goodsNo = ? AND delFl = 'n'";
            $result = $db->query($sql, [$goodsNo]);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return (bool)$row['is_customizable'];
            }
            
            return false;
            
        } catch (Exception $e) {
            Logger::error('检查商品定制支持异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return false;
        }
    }

    /**
     * 获取商品定制图案数量
     * 
     * @param int $goodsNo 商品编号
     * @return int
     */
    private function getGoodsPatternsCount($goodsNo)
    {
        try {
            $db = \App::load('DB');
            $sql = "SELECT COUNT(*) as count 
                    FROM goods_custom_pattern gcp 
                    INNER JOIN custom_pattern cp ON gcp.pattern_id = cp.pattern_id 
                    WHERE gcp.goods_id = ? AND cp.status = 1";
            $result = $db->query($sql, [$goodsNo]);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return (int)$row['count'];
            }
            
            return 0;
            
        } catch (Exception $e) {
            Logger::error('获取商品图案数量异常', [
                'error' => $e->getMessage(),
                'goods_no' => $goodsNo
            ]);
            return 0;
        }
    }

    /**
     * 构建定制页面URL
     * 
     * @param int $goodsNo 商品编号
     * @return string
     */
    private function buildCustomUrl($goodsNo)
    {
        // 基于Godomall的路由规范构建URL
        return "/custom/custom_goods.php?goodsNo=" . $goodsNo;
    }

    /**
     * 获取购买按钮配置
     * 
     * @param bool $isCustomizable 是否支持定制
     * @return array
     */
    private function getPurchaseButtonConfig($isCustomizable)
    {
        return [
            'show_custom_button' => $isCustomizable,
            'show_direct_purchase' => !$isCustomizable,
            'custom_button_text' => '🎨 定制购买',
            'direct_purchase_text' => '💳 立即购买',
            'add_cart_text' => '🛒 加入购物车'
        ];
    }

    /**
     * AJAX: 获取商品定制状态
     */
    public function getCustomStatus()
    {
        try {
            $goodsNo = Request::get()->get('goodsNo');
            
            if (!$goodsNo) {
                throw new Exception('商品编号不能为空');
            }

            $isCustomizable = $this->checkGoodsCustomizable($goodsNo);
            $patternCount = 0;
            
            if ($isCustomizable) {
                $patternCount = $this->getGoodsPatternsCount($goodsNo);
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'is_customizable' => $isCustomizable,
                    'pattern_count' => $patternCount,
                    'custom_url' => $this->buildCustomUrl($goodsNo)
                ]
            ]);

        } catch (Exception $e) {
            Logger::error('获取商品定制状态异常', [
                'error' => $e->getMessage(),
                'goods_no' => Request::get()->get('goodsNo')
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '获取商品状态失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: 直接添加商品到购物车（非定制商品）
     */
    public function addToCartDirect()
    {
        try {
            $postData = Request::post()->toArray();
            
            if (empty($postData['goodsNo']) || empty($postData['quantity'])) {
                throw new Exception('参数不完整');
            }

            // 检查商品是否支持定制，定制商品不能直接加入购物车
            $isCustomizable = $this->checkGoodsCustomizable($postData['goodsNo']);
            if ($isCustomizable) {
                throw new Exception('定制商品请先进行定制后再添加到购物车');
            }

            // 调用官方的购物车功能
            $cart = \App::load('\\Component\\Cart\\Cart');
            
            // 准备购物车数据
            $cartData = [
                'goodsNo' => $postData['goodsNo'],
                'goodsCnt' => $postData['quantity'],
                'cartMode' => 'n', // 购物车模式
                'optionSno' => $postData['optionSno'] ?? '',
                'optionTextInput' => $postData['optionTextInput'] ?? []
            ];

            // 添加到购物车
            $cart->saveInfoCart($cartData);

            echo json_encode([
                'success' => true,
                'message' => '商品已添加到购物车',
                'redirect_url' => '/order/cart.php'
            ]);

        } catch (Exception $e) {
            Logger::error('直接添加到购物车异常', [
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
     * AJAX: 直接购买（非定制商品）
     */
    public function buyNowDirect()
    {
        try {
            $postData = Request::post()->toArray();
            
            if (empty($postData['goodsNo']) || empty($postData['quantity'])) {
                throw new Exception('参数不完整');
            }

            // 检查商品是否支持定制，定制商品不能直接购买
            $isCustomizable = $this->checkGoodsCustomizable($postData['goodsNo']);
            if ($isCustomizable) {
                throw new Exception('定制商品请先进行定制后再购买');
            }

            // 调用官方的购物车功能，但设置为直接购买模式
            $cart = \App::load('\\Component\\Cart\\Cart');
            
            // 清空直接购买商品
            $cart->setDeleteDirectCartCont();
            
            // 准备购物车数据
            $cartData = [
                'goodsNo' => $postData['goodsNo'],
                'goodsCnt' => $postData['quantity'],
                'cartMode' => 'd', // 直接购买模式
                'optionSno' => $postData['optionSno'] ?? '',
                'optionTextInput' => $postData['optionTextInput'] ?? []
            ];

            // 添加到购物车（直接购买模式）
            $cart->saveInfoCart($cartData);

            echo json_encode([
                'success' => true,
                'message' => '准备结算',
                'redirect_url' => '/order/order.php'
            ]);

        } catch (Exception $e) {
            Logger::error('直接购买异常', [
                'error' => $e->getMessage(),
                'post_data' => $postData ?? []
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '购买失败：' . $e->getMessage()
            ]);
        }
    }
}
