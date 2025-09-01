/**
 * 商品定制页面JavaScript
 * 使用Fabric.js实现画布功能
 */

class CustomGoodsEditor {
    constructor() {
        this.canvas = null;
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 20;
        this.selectedColor = 'navy';
        this.selectedSize = '4.7x2.8';
        this.quantity = 1;
        this.customizationData = {
            elements: [],
            backgroundImage: null,
            customImage: null
        };
        
        this.init();
    }
    
    init() {
        this.initCanvas();
        this.initEventListeners();
        this.loadProductBackground();
        this.loadPatternLibrary();
    }
    
    /**
     * 初始化Fabric.js画布
     */
    initCanvas() {
        this.canvas = new fabric.Canvas('customCanvas', {
            width: 400,
            height: 400,
            backgroundColor: '#ffffff',
            selection: true,
            preserveObjectStacking: true
        });
        
        // 设置画布事件
        this.canvas.on('object:added', (e) => {
            this.addToHistory();
        });
        
        this.canvas.on('object:modified', (e) => {
            this.addToHistory();
        });
        
        this.canvas.on('object:removed', (e) => {
            this.addToHistory();
        });
        
        this.canvas.on('selection:created', (e) => {
            this.updateToolbarState();
        });
        
        this.canvas.on('selection:cleared', (e) => {
            this.updateToolbarState();
        });
        
        this.canvas.on('selection:updated', (e) => {
            this.updateToolbarState();
        });
    }
    
    /**
     * 初始化事件监听器
     */
    initEventListeners() {
        // 工具栏按钮
        this.bindToolbarEvents();
        
        // 定制选项按钮
        this.bindCustomOptionEvents();
        
        // 商品信息事件
        this.bindProductInfoEvents();
        
        // 弹窗事件
        this.bindModalEvents();
    }
    
    /**
     * 绑定工具栏事件
     */
    bindToolbarEvents() {
        // 撤销/重做
        document.getElementById('btnUndo').addEventListener('click', () => this.undo());
        document.getElementById('btnRedo').addEventListener('click', () => this.redo());
        document.getElementById('btnRevert').addEventListener('click', () => this.revert());
        
        // 删除
        document.getElementById('btnDelete').addEventListener('click', () => this.deleteSelected());
        
        // 图层操作
        document.getElementById('btnForward').addEventListener('click', () => this.bringForward());
        document.getElementById('btnBackward').addEventListener('click', () => this.sendBackward());
        
        // 组合操作
        document.getElementById('btnGroup').addEventListener('click', () => this.groupObjects());
        document.getElementById('btnUngroup').addEventListener('click', () => this.ungroupObjects());
        
        // 对齐操作
        document.getElementById('btnAlignLeft').addEventListener('click', () => this.alignObjects('left'));
        document.getElementById('btnAlignCenter').addEventListener('click', () => this.alignObjects('center'));
        document.getElementById('btnAlignRight').addEventListener('click', () => this.alignObjects('right'));
        document.getElementById('btnAlignTop').addEventListener('click', () => this.alignObjects('top'));
        document.getElementById('btnAlignMiddle').addEventListener('click', () => this.alignObjects('middle'));
        document.getElementById('btnAlignBottom').addEventListener('click', () => this.alignObjects('bottom'));
        
        // 检查按钮
        document.getElementById('btnCheck').addEventListener('click', () => this.checkCustomization());
    }
    
    /**
     * 绑定定制选项事件
     */
    bindCustomOptionEvents() {
        // 上传图片
        document.getElementById('btnUploadImage').addEventListener('click', () => {
            document.getElementById('imageUpload').click();
        });
        
        // 添加文字
        document.getElementById('btnAddText').addEventListener('click', () => {
            this.showTextEditor();
        });
        
        // 图案库
        const patternLibraryBtn = document.getElementById('btnPatternLibrary');
        patternLibraryBtn.addEventListener('click', () => {
            // 检查按钮是否被禁用
            if (!patternLibraryBtn.disabled) {
                this.showPatternLibrary();
            } else {
                alert('该商品暂无可用图案，请联系管理员添加');
            }
        });
        
        // 文件上传处理
        document.getElementById('imageUpload').addEventListener('change', (e) => {
            this.handleImageUpload(e.target.files[0]);
        });
    }
    
    /**
     * 绑定商品信息事件
     */
    bindProductInfoEvents() {
        // 颜色选择
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', () => {
                const color = option.dataset.color;
                this.selectColor(color);
            });
        });

        // 尺寸选择
        document.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', () => {
                const size = option.dataset.size;
                this.selectSize(size);
            });
        });

        // 数量控制
        document.getElementById('btnDecrease').addEventListener('click', () => {
            this.changeQuantity(-1);
        });

        document.getElementById('btnIncrease').addEventListener('click', () => {
            this.changeQuantity(1);
        });

        document.getElementById('quantityInput').addEventListener('change', (e) => {
            this.setQuantity(parseInt(e.target.value) || 1);
        });

        // 添加到购物车
        document.getElementById('btnAddToCart').addEventListener('click', () => {
            this.addToCart();
        });

        // 更换商品
        document.getElementById('btnChangeProduct').addEventListener('click', () => {
            this.showProductSelector();
        });

        // 分享和收藏
        document.getElementById('btnShare').addEventListener('click', () => {
            this.shareProduct();
        });

        document.getElementById('btnFavorite').addEventListener('click', () => {
            this.toggleFavorite();
        });
    }
    
    /**
     * 绑定弹窗事件
     */
    bindModalEvents() {
        // 图案库弹窗
        document.getElementById('btnCloseModal').addEventListener('click', () => {
            this.hidePatternLibrary();
        });
        
        // 文字编辑弹窗
        document.getElementById('btnCloseTextModal').addEventListener('click', () => {
            this.hideTextEditor();
        });
        
        document.getElementById('btnCancelText').addEventListener('click', () => {
            this.hideTextEditor();
        });
        
        document.getElementById('btnConfirmText').addEventListener('click', () => {
            this.addTextToCanvas();
        });
        
        // 图案搜索
        document.getElementById('patternSearch').addEventListener('input', (e) => {
            this.filterPatterns(e.target.value);
        });
        
        // 点击弹窗外部关闭
        document.getElementById('patternModal').addEventListener('click', (e) => {
            if (e.target.id === 'patternModal') {
                this.hidePatternLibrary();
            }
        });
        
        document.getElementById('textModal').addEventListener('click', (e) => {
            if (e.target.id === 'textModal') {
                this.hideTextEditor();
            }
        });
    }
    
    /**
     * 加载商品背景图片
     */
    loadProductBackground() {
        // 从页面配置中获取商品图片URL
        const pageConfigElement = document.getElementById('pageConfig');
        let productImageUrl = null;
        
        if (pageConfigElement) {
            try {
                const pageConfig = JSON.parse(pageConfigElement.textContent);
                productImageUrl = pageConfig.goods_image_url;
            } catch (e) {
                console.error('解析页面配置失败:', e);
            }
        }
        
        // 如果没有获取到图片URL，使用默认图片
        if (!productImageUrl) {
            productImageUrl = '/assets/images/default-product.jpg';
        }
        
        // 设置HTML中的背景图片元素
        const backgroundImageElement = document.getElementById('backgroundImage');
        if (backgroundImageElement) {
            backgroundImageElement.src = productImageUrl;
            backgroundImageElement.alt = '상품 미리보기';
            backgroundImageElement.onload = () => {
                this.setCanvasBackgroundFromImage(backgroundImageElement);
            };
            backgroundImageElement.onerror = () => {
                console.warn('상품 이미지 로드 실패, 기본 배경 사용');
                this.setDefaultCanvasBackground();
            };
        } else {
            // 如果没有找到背景图片元素，直接加载到canvas
            this.loadImageToCanvas(productImageUrl);
        }
    }
    
    /**
     * 从图片元素设置canvas背景
     */
    setCanvasBackgroundFromImage(imageElement) {
        const fabricImage = new fabric.Image(imageElement, {
            left: 0,
            top: 0,
            width: 400,
            height: 400,
            selectable: false,
            evented: false,
            originX: 'center',
            originY: 'center'
        });
        
        this.canvas.setBackgroundImage(fabricImage, this.canvas.renderAll.bind(this.canvas));
        this.customizationData.backgroundImage = fabricImage;
        this.addToHistory();
    }
    
    /**
     * 直接加载图片到canvas
     */
    loadImageToCanvas(imageUrl) {
        const productImage = new Image();
        productImage.onload = () => {
            this.setCanvasBackgroundFromImage(productImage);
        };
        
        productImage.onerror = () => {
            console.warn('상품 이미지 로드 실패, 기본 배경 사용');
            this.setDefaultCanvasBackground();
        };
        
        productImage.src = imageUrl;
    }
    
    /**
     * 设置默认canvas背景
     */
    setDefaultCanvasBackground() {
        // 使用默认的SVG占位图
        const defaultBackground = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400"><rect width="400" height="400" fill="%231e3a8a"/><circle cx="200" cy="150" r="80" fill="%23ffffff" opacity="0.1"/><text x="200" y="220" text-anchor="middle" fill="%23ffffff" font-size="16">상품 미리보기</text></svg>';
        
        const productImage = new Image();
        productImage.onload = () => {
            this.setCanvasBackgroundFromImage(productImage);
        };
        productImage.src = defaultBackground;
    }
    
    /**
     * 加载图案库
     */
    loadPatternLibrary() {
        // 从页面获取图案数据（由后端渲染）
        const patternsDataElement = document.getElementById('patternsData');
        if (patternsDataElement) {
            try {
                const patterns = JSON.parse(patternsDataElement.textContent);
                if (patterns && patterns.length > 0) {
                    this.renderPatternGrid(patterns);
                } else {
                    this.showNoPatternsMessage();
                }
            } catch (e) {
                console.error('解析图案数据失败:', e);
                this.showNoPatternsMessage();
            }
        } else {
            // 如果没有后端数据，显示无图案提示
            this.showNoPatternsMessage();
        }
    }
    
    /**
     * 显示无图案提示
     */
    showNoPatternsMessage() {
        const grid = document.getElementById('patternGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="no-patterns-message">
                    <div class="no-patterns-icon">🎨</div>
                    <div class="no-patterns-text">暂无可用图案</div>
                    <div class="no-patterns-hint">请联系管理员为该商品添加定制图案</div>
                </div>
            `;
        }
    }
    
    /**
     * 渲染图案网格
     */
    renderPatternGrid(patterns) {
        const grid = document.getElementById('patternGrid');
        grid.innerHTML = '';
        
        patterns.forEach(pattern => {
            const item = document.createElement('div');
            item.className = 'pattern-item';
            item.innerHTML = `
                <img src="${pattern.url}" alt="${pattern.name}">
                <div class="pattern-name">${pattern.name}</div>
            `;
            
            item.addEventListener('click', () => {
                this.addPatternToCanvas(pattern);
                this.hidePatternLibrary();
            });
            
            grid.appendChild(item);
        });
    }
    
    /**
     * 添加图案到画布
     */
    addPatternToCanvas(pattern) {
        fabric.loadSVGFromURL(pattern.url, (objects, options) => {
            const group = new fabric.Group(objects, {
                left: 100,
                top: 100,
                scaleX: 0.5,
                scaleY: 0.5,
                selectable: true,
                hasControls: true,
                hasBorders: true
            });
            
            this.canvas.add(group);
            this.canvas.setActiveObject(group);
            this.canvas.renderAll();
        });
    }
    
    /**
     * 显示图案库
     */
    showPatternLibrary() {
        document.getElementById('patternModal').style.display = 'block';
    }
    
    /**
     * 隐藏图案库
     */
    hidePatternLibrary() {
        document.getElementById('patternModal').style.display = 'none';
    }
    
    /**
     * 过滤图案
     */
    filterPatterns(searchTerm) {
        // 获取原始图案数据
        const patternsDataElement = document.getElementById('patternsData');
        if (!patternsDataElement) {
            return;
        }
        
        try {
            const allPatterns = JSON.parse(patternsDataElement.textContent);
            if (!allPatterns || allPatterns.length === 0) {
                this.showNoPatternsMessage();
                return;
            }
            
            // 过滤图案
            const filteredPatterns = allPatterns.filter(pattern => 
                pattern.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (pattern.tags && pattern.tags.some(tag => tag.toLowerCase().includes(searchTerm.toLowerCase())))
            );
            
            if (filteredPatterns.length > 0) {
                this.renderPatternGrid(filteredPatterns);
            } else {
                this.showNoSearchResultsMessage(searchTerm);
            }
        } catch (e) {
            console.error('搜索图案失败:', e);
            this.showNoPatternsMessage();
        }
    }
    
    /**
     * 显示无搜索结果提示
     */
    showNoSearchResultsMessage(searchTerm) {
        const grid = document.getElementById('patternGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="no-patterns-message">
                    <div class="no-patterns-icon">🔍</div>
                    <div class="no-patterns-text">未找到相关图案</div>
                    <div class="no-patterns-hint">搜索关键词："${searchTerm}"</div>
                </div>
            `;
        }
    }
    
    /**
     * 显示文字编辑器
     */
    showTextEditor() {
        document.getElementById('textModal').style.display = 'block';
        document.getElementById('textContent').focus();
    }
    
    /**
     * 隐藏文字编辑器
     */
    hideTextEditor() {
        document.getElementById('textModal').style.display = 'none';
        document.getElementById('textContent').value = '';
    }
    
    /**
     * 添加文字到画布
     */
    addTextToCanvas() {
        const text = document.getElementById('textContent').value.trim();
        if (!text) return;
        
        const fontSize = parseInt(document.getElementById('textSize').value);
        const fontColor = document.getElementById('textColor').value;
        const fontFamily = document.getElementById('textFont').value;
        
        const fabricText = new fabric.Text(text, {
            left: 100,
            top: 100,
            fontSize: fontSize,
            fill: fontColor,
            fontFamily: fontFamily,
            selectable: true,
            hasControls: true,
            hasBorders: true
        });
        
        this.canvas.add(fabricText);
        this.canvas.setActiveObject(fabricText);
        this.canvas.renderAll();
        
        this.hideTextEditor();
    }
    
    /**
     * 处理图片上传
     */
    handleImageUpload(file) {
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            alert('请选择图片文件');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                img.scaleToWidth(100);
                img.set({
                    left: 100,
                    top: 100,
                    selectable: true,
                    hasControls: true,
                    hasBorders: true
                });
                
                this.canvas.add(img);
                this.canvas.setActiveObject(img);
                this.canvas.renderAll();
            });
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * 撤销操作
     */
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.loadFromHistory();
        }
    }
    
    /**
     * 重做操作
     */
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.loadFromHistory();
        }
    }
    
    /**
     * 恢复到初始状态
     */
    revert() {
        this.canvas.clear();
        this.loadProductBackground();
        this.history = [];
        this.historyIndex = -1;
    }
    
    /**
     * 删除选中对象
     */
    deleteSelected() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.remove(activeObject);
            this.canvas.renderAll();
        }
    }
    
    /**
     * 前移图层
     */
    bringForward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.bringForward(activeObject);
            this.canvas.renderAll();
        }
    }
    
    /**
     * 后移图层
     */
    sendBackward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.sendBackward(activeObject);
            this.canvas.renderAll();
        }
    }
    
    /**
     * 组合对象
     */
    groupObjects() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type === 'activeSelection') {
            activeObject.toGroup();
            this.canvas.requestRenderAll();
        }
    }
    
    /**
     * 解组对象
     */
    ungroupObjects() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type === 'group') {
            activeObject.toActiveSelection();
            this.canvas.requestRenderAll();
        }
    }
    
    /**
     * 对齐对象
     */
    alignObjects(alignment) {
        const activeObject = this.canvas.getActiveObject();
        if (!activeObject) return;
        
        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();
        
        switch (alignment) {
            case 'left':
                activeObject.set('left', 0);
                break;
            case 'center':
                activeObject.set('left', canvasWidth / 2 - activeObject.getScaledWidth() / 2);
                break;
            case 'right':
                activeObject.set('left', canvasWidth - activeObject.getScaledWidth());
                break;
            case 'top':
                activeObject.set('top', 0);
                break;
            case 'middle':
                activeObject.set('top', canvasHeight / 2 - activeObject.getScaledHeight() / 2);
                break;
            case 'bottom':
                activeObject.set('top', canvasHeight - activeObject.getScaledHeight());
                break;
        }
        
        this.canvas.renderAll();
    }
    
    /**
     * 更新工具栏状态
     */
    updateToolbarState() {
        const activeObject = this.canvas.getActiveObject();
        const hasSelection = !!activeObject;
        
        // 更新按钮状态
        document.getElementById('btnDelete').disabled = !hasSelection;
        document.getElementById('btnForward').disabled = !hasSelection;
        document.getElementById('btnBackward').disabled = !hasSelection;
        document.getElementById('btnGroup').disabled = !hasSelection;
        document.getElementById('btnUngroup').disabled = !hasSelection;
        
        // 对齐按钮
        const alignButtons = ['btnAlignLeft', 'btnAlignCenter', 'btnAlignRight', 'btnAlignTop', 'btnAlignMiddle', 'btnAlignBottom'];
        alignButtons.forEach(btnId => {
            document.getElementById(btnId).disabled = !hasSelection;
        });
    }
    
    /**
     * 添加到历史记录
     */
    addToHistory() {
        const json = JSON.stringify(this.canvas.toJSON());
        
        // 移除当前位置之后的历史记录
        this.history = this.history.slice(0, this.historyIndex + 1);
        
        // 添加新的状态
        this.history.push(json);
        this.historyIndex++;
        
        // 限制历史记录数量
        if (this.history.length > this.maxHistory) {
            this.history.shift();
            this.historyIndex--;
        }
    }
    
    /**
     * 从历史记录加载
     */
    loadFromHistory() {
        if (this.historyIndex >= 0 && this.historyIndex < this.history.length) {
            this.canvas.loadFromJSON(this.history[this.historyIndex], () => {
                this.canvas.renderAll();
            });
        }
    }
    
    /**
     * 检查定制结果
     */
    checkCustomization() {
        const objects = this.canvas.getObjects();
        if (objects.length === 0) {
            alert('请添加一些定制元素');
            return;
        }
        
        // 这里可以添加更多的验证逻辑
        alert('定制检查通过！');
    }
    
    /**
     * 选择颜色
     * 
     * @param {string} color 颜色代码
     */
    selectColor(color) {
        this.selectedColor = color;
        
        // 更新颜色选择状态
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
            if (option.dataset.color === color) {
                option.classList.add('selected');
            }
        });
        
        // 更新商品图片（如果该颜色有对应的图片）
        this.updateProductImageForColor(color);
        
        // 更新画布背景（如果支持颜色切换）
        this.updateCanvasBackgroundForColor(color);
    }
    
    /**
     * 根据颜色更新商品图片
     * 
     * @param {string} color 颜色代码
     */
    updateProductImageForColor(color) {
        const productImage = document.getElementById('productImage');
        if (productImage) {
            // 这里可以根据颜色获取对应的商品图片
            // 暂时保持原图片，后续可以扩展为根据颜色获取不同图片
            console.log('颜色切换为:', color);
        }
    }
    
    /**
     * 根据颜色更新画布背景
     * 
     * @param {string} color 颜色代码
     */
    updateCanvasBackgroundForColor(color) {
        // 这里可以根据颜色更新画布背景
        // 暂时保持原背景，后续可以扩展为根据颜色获取不同背景
        console.log('画布背景颜色切换为:', color);
    }
    
    /**
     * 选择尺寸
     */
    selectSize(size) {
        this.selectedSize = size;
        
        // 更新UI
        document.querySelectorAll('.size-option').forEach(option => {
            option.classList.remove('active');
        });
        document.querySelector(`[data-size="${size}"]`).classList.add('active');
        
        console.log('选择尺寸:', size);
    }
    
    /**
     * 改变数量
     */
    changeQuantity(delta) {
        const newQuantity = Math.max(1, this.quantity + delta);
        this.setQuantity(newQuantity);
    }
    
    /**
     * 设置数量
     */
    setQuantity(quantity) {
        this.quantity = Math.max(1, quantity);
        document.getElementById('quantityInput').value = this.quantity;
        
        // 更新价格显示
        const basePrice = 14.47;
        const totalPrice = (basePrice * this.quantity).toFixed(2);
        document.querySelector('.summary-price').textContent = `$${totalPrice}`;
        document.querySelector('.summary-quantity').textContent = `${this.quantity}EA`;
    }
    
    /**
     * 显示商品选择器
     */
    showProductSelector() {
        // 这里应该显示商品选择弹窗
        alert('商品选择功能待实现');
    }
    
    /**
     * 添加到购物车
     */
    addToCart() {
        // 生成定制图片
        const customImageData = this.canvas.toDataURL({
            format: 'png',
            quality: 0.8
        });
        
        // 准备定制数据
        const customizationData = {
            elements: this.canvas.getObjects().map(obj => ({
                type: obj.type,
                left: obj.left,
                top: obj.top,
                scaleX: obj.scaleX,
                scaleY: obj.scaleY,
                angle: obj.angle,
                data: obj.toObject()
            })),
            customImage: customImageData,
            color: this.selectedColor,
            size: this.selectedSize,
            quantity: this.quantity
        };
        
        // 获取商品ID
        const goodsId = document.getElementById('btnAddToCart').dataset.goodsId;
        
        // 调用后端API添加到购物车
        this.callAddToCartAPI(goodsId, customizationData);
    }
    
    /**
     * 调用添加到购物车API
     */
    async callAddToCartAPI(goodsId, customizationData) {
        try {
            const response = await fetch('/custom/add-to-cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    goods_id: goodsId,
                    quantity: this.quantity,
                    customization_data: customizationData,
                    selected_color: this.selectedColor,
                    selected_size: this.selectedSize
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('定制商品已添加到购物车！');
                // 可以在这里跳转到购物车页面
                // window.location.href = '/cart';
            } else {
                alert('添加到购物车失败：' + result.message);
            }
            
        } catch (error) {
            console.error('添加到购物车失败:', error);
            alert('添加到购物车失败，请稍后重试');
        }
    }

    /**
     * 分享商品
     */
    shareProduct() {
        const goodsId = document.getElementById('btnShare').dataset.goodsId;
        console.log('分享商品:', goodsId);
        // TODO: 实现分享功能
        alert('分享功能开发中...');
    }

    /**
     * 切换收藏状态
     */
    toggleFavorite() {
        const goodsId = document.getElementById('btnFavorite').dataset.goodsId;
        const btn = document.getElementById('btnFavorite');
        const isFavorited = btn.classList.contains('favorited');
        
        if (isFavorited) {
            btn.classList.remove('favorited');
            console.log('取消收藏商品:', goodsId);
        } else {
            btn.classList.add('favorited');
            console.log('收藏商品:', goodsId);
        }
        
        // TODO: 实现收藏功能
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    new CustomGoodsEditor();
}); 