/**
 * 商品购买按钮功能
 * 
 * 支持定制商品和普通商品的购买逻辑
 * 严格遵循用户指定的购买流程：商品详情 → 定制页 → 购物车 → 结算页
 * 
 * @author Custom Development Team
 * @version 1.0
 */

class GoodsPurchaseManager {
    constructor() {
        this.goodsNo = null;
        this.isCustomizable = false;
        this.customUrl = '';
        this.init();
    }

    /**
     * 初始化
     */
    init() {
        // 从页面获取商品信息
        this.extractGoodsInfo();
        
        // 创建购买按钮
        this.createPurchaseButtons();
        
        // 绑定事件
        this.bindEvents();
        
        console.log('商品购买管理器已初始化', {
            goodsNo: this.goodsNo,
            isCustomizable: this.isCustomizable
        });
    }

    /**
     * 从页面提取商品信息
     */
    extractGoodsInfo() {
        // 从URL参数获取商品ID
        const urlParams = new URLSearchParams(window.location.search);
        this.goodsNo = urlParams.get('goodsNo');
        
        // 从页面数据获取定制信息
        if (window.goodsData) {
            this.isCustomizable = window.goodsData.is_customizable || false;
            this.customUrl = window.goodsData.custom_url || '';
        }
        
        // 如果没有window.goodsData，通过AJAX获取
        if (!this.goodsNo) {
            console.error('无法获取商品编号');
            return;
        }
        
        if (!window.goodsData) {
            this.fetchGoodsStatus();
        }
    }

    /**
     * 获取商品状态（AJAX）
     */
    async fetchGoodsStatus() {
        try {
            const response = await fetch(`/goods/goods_view.php?mode=getCustomStatus&goodsNo=${this.goodsNo}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.isCustomizable = result.data.is_customizable;
                this.customUrl = result.data.custom_url;
                this.updatePurchaseButtons();
            }
            
        } catch (error) {
            console.error('获取商品状态失败:', error);
        }
    }

    /**
     * 创建购买按钮
     */
    createPurchaseButtons() {
        // 查找价格信息下方的位置
        const priceSection = this.findPriceSection();
        if (!priceSection) {
            console.warn('未找到价格信息区域，无法添加购买按钮');
            return;
        }

        // 创建购买按钮容器
        const buttonContainer = this.createButtonContainer();
        
        // 插入到价格信息下方
        priceSection.insertAdjacentElement('afterend', buttonContainer);
        
        // 根据商品类型显示对应按钮
        this.updatePurchaseButtons();
    }

    /**
     * 查找价格信息区域
     */
    findPriceSection() {
        // 尝试多种选择器找到价格区域
        const selectors = [
            '.goods-price',
            '.price-info',
            '.goodsPrice',
            '.price',
            '.good-price',
            '[class*="price"]'
        ];

        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (element) {
                return element;
            }
        }

        // 如果找不到价格区域，尝试找到商品信息区域
        const infoSelectors = [
            '.goods-info',
            '.product-info',
            '.goodsInfo',
            '.item-info'
        ];

        for (const selector of infoSelectors) {
            const element = document.querySelector(selector);
            if (element) {
                return element;
            }
        }

        return null;
    }

    /**
     * 创建按钮容器
     */
    createButtonContainer() {
        const container = document.createElement('div');
        container.id = 'custom-purchase-buttons';
        container.className = 'custom-purchase-buttons';
        container.innerHTML = `
            <div class="purchase-buttons-wrapper">
                <!-- 定制商品按钮 -->
                <button type="button" 
                        id="btnCustomPurchase" 
                        class="btn btn-custom-purchase"
                        style="display: none;">
                    <i class="icon-palette"></i>
                    🎨 定制购买
                </button>
                
                <!-- 普通商品按钮 -->
                <div id="normalPurchaseButtons" class="normal-purchase-buttons" style="display: none;">
                    <button type="button" 
                            id="btnAddToCart" 
                            class="btn btn-add-cart">
                        <i class="icon-cart"></i>
                        🛒 加入购物车
                    </button>
                    
                    <button type="button" 
                            id="btnBuyNow" 
                            class="btn btn-buy-now">
                        <i class="icon-buy"></i>
                        💳 立即购买
                    </button>
                </div>
                
                <!-- 加载状态 -->
                <div id="purchaseLoading" class="purchase-loading" style="display: none;">
                    <span>处理中...</span>
                </div>
            </div>
        `;

        return container;
    }

    /**
     * 更新购买按钮显示
     */
    updatePurchaseButtons() {
        const customBtn = document.getElementById('btnCustomPurchase');
        const normalBtns = document.getElementById('normalPurchaseButtons');

        if (!customBtn || !normalBtns) return;

        if (this.isCustomizable) {
            // 显示定制按钮，隐藏普通按钮
            customBtn.style.display = 'block';
            normalBtns.style.display = 'none';
        } else {
            // 显示普通按钮，隐藏定制按钮
            customBtn.style.display = 'none';
            normalBtns.style.display = 'flex';
        }
    }

    /**
     * 绑定事件
     */
    bindEvents() {
        // 定制购买按钮
        document.addEventListener('click', (e) => {
            if (e.target.id === 'btnCustomPurchase' || e.target.closest('#btnCustomPurchase')) {
                e.preventDefault();
                this.goToCustomPage();
            }
        });

        // 加入购物车按钮
        document.addEventListener('click', (e) => {
            if (e.target.id === 'btnAddToCart' || e.target.closest('#btnAddToCart')) {
                e.preventDefault();
                this.addToCart();
            }
        });

        // 立即购买按钮
        document.addEventListener('click', (e) => {
            if (e.target.id === 'btnBuyNow' || e.target.closest('#btnBuyNow')) {
                e.preventDefault();
                this.buyNow();
            }
        });
    }

    /**
     * 跳转到定制页面
     */
    goToCustomPage() {
        if (!this.goodsNo) {
            this.showError('商品信息异常，无法进入定制页面');
            return;
        }

        if (!this.customUrl) {
            this.customUrl = `./custom/goods_custom.html?goodsNo=${this.goodsNo}`;
        }

        console.log('跳转到定制页面:', this.customUrl);
        
        // 跳转到定制页面
        window.location.href = this.customUrl;
    }

    /**
     * 添加到购物车
     */
    async addToCart() {
        if (this.isCustomizable) {
            this.showError('定制商品请先进行定制');
            return;
        }

        try {
            this.showLoading(true);

            // 获取商品数量和选项
            const quantity = this.getQuantity();
            const optionData = this.getOptionData();

            const response = await fetch('/goods/goods_view.php?mode=addToCartDirect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    goodsNo: this.goodsNo,
                    quantity: quantity,
                    ...optionData
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess('商品已添加到购物车');
                
                // 询问是否跳转到购物车
                if (confirm('商品已添加到购物车，是否立即查看购物车？')) {
                    window.location.href = result.redirect_url || '/order/cart.php';
                }
            } else {
                this.showError(result.message || '添加到购物车失败');
            }

        } catch (error) {
            console.error('添加到购物车异常:', error);
            this.showError('添加到购物车失败，请稍后重试');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * 立即购买
     */
    async buyNow() {
        if (this.isCustomizable) {
            this.showError('定制商品请先进行定制');
            return;
        }

        try {
            this.showLoading(true);

            // 获取商品数量和选项
            const quantity = this.getQuantity();
            const optionData = this.getOptionData();

            const response = await fetch('/goods/goods_view.php?mode=buyNowDirect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    goodsNo: this.goodsNo,
                    quantity: quantity,
                    ...optionData
                })
            });

            const result = await response.json();

            if (result.success) {
                // 直接跳转到结算页面
                window.location.href = result.redirect_url || '/order/order.php';
            } else {
                this.showError(result.message || '购买失败');
            }

        } catch (error) {
            console.error('立即购买异常:', error);
            this.showError('购买失败，请稍后重试');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * 获取商品数量
     */
    getQuantity() {
        // 尝试从多种可能的数量输入框获取
        const quantitySelectors = [
            'input[name="goodsCnt"]',
            'input[name="quantity"]',
            '.quantity-input',
            '#quantity',
            '#goodsCnt'
        ];

        for (const selector of quantitySelectors) {
            const input = document.querySelector(selector);
            if (input) {
                const value = parseInt(input.value) || 1;
                return value > 0 ? value : 1;
            }
        }

        return 1; // 默认数量
    }

    /**
     * 获取商品选项数据
     */
    getOptionData() {
        const optionData = {};

        // 获取选项数据
        const optionInputs = document.querySelectorAll('select[name^="option"], input[name^="option"]');
        optionInputs.forEach(input => {
            if (input.value) {
                optionData[input.name] = input.value;
            }
        });

        return optionData;
    }

    /**
     * 显示加载状态
     */
    showLoading(show) {
        const loading = document.getElementById('purchaseLoading');
        const buttons = document.querySelectorAll('.custom-purchase-buttons button');

        if (loading) {
            loading.style.display = show ? 'block' : 'none';
        }

        buttons.forEach(btn => {
            btn.disabled = show;
            if (show) {
                btn.style.opacity = '0.6';
            } else {
                btn.style.opacity = '1';
            }
        });
    }

    /**
     * 显示成功消息
     */
    showSuccess(message) {
        // 使用系统的提示方式或自定义提示
        if (typeof alert !== 'undefined') {
            alert(message);
        } else {
            console.log('成功:', message);
        }
    }

    /**
     * 显示错误消息
     */
    showError(message) {
        // 使用系统的错误提示方式或自定义提示
        if (typeof alert !== 'undefined') {
            alert('错误: ' + message);
        } else {
            console.error('错误:', message);
        }
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    // 检查是否在商品详情页
    if (window.location.pathname.includes('goods_view.php') || 
        document.querySelector('[class*="goods"], [class*="product"], [id*="goods"], [id*="product"]')) {
        
        window.goodsPurchaseManager = new GoodsPurchaseManager();
        console.log('商品购买功能已加载');
    }
});

// 导出全局访问
window.GoodsPurchaseManager = GoodsPurchaseManager;
