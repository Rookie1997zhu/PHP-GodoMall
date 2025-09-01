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
            backgroundColor: 'transparent',
            selection: true,
            preserveObjectStacking: true,
            allowTouchScrolling: false,
            enableRetinaScaling: true,
            centeredScaling: false,
            centeredRotation: true,
            selectionBorderColor: '#4285f4',
            selectionLineWidth: 2,
            interactive: true,
            moveCursor: 'move',
            hoverCursor: 'move',
            defaultCursor: 'default'
        });
        
        // 确保canvas元素能接收事件
        this.canvas.upperCanvasEl.style.zIndex = '10';
        this.canvas.lowerCanvasEl.style.zIndex = '9';
        
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
        
        // 确保对象可以被移动和缩放
        this.canvas.on('object:added', (e) => {
            const obj = e.target;
            if (obj && obj !== this.canvas.backgroundImage) {
                obj.set({
                    selectable: true,
                    hasControls: true,
                    hasBorders: true,
                    hasRotatingPoint: true,
                    transparentCorners: false,
                    cornerColor: '#4285f4',
                    cornerStrokeColor: '#4285f4',
                    borderColor: '#4285f4',
                    moveCursor: 'move',
                    hoverCursor: 'move'
                });
                console.log('对象已添加到画布:', obj.type, obj);
            }
        });
        
        // 设置默认的控制器样式
        fabric.Object.prototype.set({
            transparentCorners: false,
            cornerColor: '#4285f4',
            cornerStrokeColor: '#4285f4',
            borderColor: '#4285f4',
            cornerSize: 12,
            borderOpacityWhenMoving: 0.8
        });
        
        // 添加调试事件
        this.canvas.on('mouse:down', (e) => {
            console.log('鼠标按下事件:', e);
        });
        
        this.canvas.on('object:selected', (e) => {
            console.log('对象被选中:', e.target);
        });
        
        this.canvas.on('selection:created', (e) => {
            console.log('选择创建:', e.target);
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
        // 检查按钮是否存在
        const deleteBtn = document.getElementById('btnDelete');
        console.log('删除按钮元素:', deleteBtn);
        
        // 撤销/重做
        document.getElementById('btnUndo').addEventListener('click', () => this.undo());
        document.getElementById('btnRedo').addEventListener('click', () => this.redo());
        
        // 查看功能
        document.getElementById('btnView').addEventListener('click', () => this.toggleView());
        
        // 删除
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('删除按钮被点击');
                this.deleteSelected();
            });
        } else {
            console.error('删除按钮未找到！');
        }
        
        // 翻转操作
        document.getElementById('btnFlipH').addEventListener('click', () => this.flipHorizontal());
        document.getElementById('btnFlipV').addEventListener('click', () => this.flipVertical());
        
        // 图层操作
        document.getElementById('btnBringForward').addEventListener('click', () => this.bringForward());
        document.getElementById('btnSendBackward').addEventListener('click', () => this.sendBackward());
        document.getElementById('btnBringToFront').addEventListener('click', () => this.bringToFront());
        document.getElementById('btnSendToBack').addEventListener('click', () => this.sendToBack());
        
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
        
        // 键盘删除功能
        document.addEventListener('keydown', (e) => {
            // 只在没有输入框获得焦点时响应删除键
            const activeElement = document.activeElement;
            const isInputElement = activeElement && (
                activeElement.tagName === 'INPUT' || 
                activeElement.tagName === 'TEXTAREA' || 
                activeElement.isContentEditable
            );
            
            if (!isInputElement && (e.key === 'Delete' || e.key === 'Backspace')) {
                e.preventDefault();
                this.deleteSelected();
            }
        });
    }
    
    /**
     * 绑定定制选项事件
     */
    bindCustomOptionEvents() {
        // 上传图片
        const uploadBtn = document.getElementById('btnUploadImage');
        const fileInput = document.getElementById('imageUpload');
        
        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('click', () => {
                console.log('上传图片按钮被点击');
                fileInput.click();
            });
            
            // 文件上传处理
            fileInput.addEventListener('change', (e) => {
                console.log('文件选择事件触发:', e.target.files);
                if (e.target.files && e.target.files.length > 0) {
                    this.handleImageUpload(e.target.files[0]);
                    // 清空文件输入框，允许重复选择同一文件
                    e.target.value = '';
                } else {
                    console.warn('没有选择文件');
                }
            });
        } else {
            console.error('上传图片相关元素未找到:', { uploadBtn, fileInput });
        }
        
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
    }
    
    /**
     * 绑定商品信息事件
     */
    bindProductInfoEvents() {
        // 颜色选择
        document.querySelectorAll('.color-item').forEach(option => {
            option.addEventListener('click', () => {
                const color = option.dataset.color;
                this.selectColor(color);
            });
        });

        // 尺寸选择
        document.querySelectorAll('.size-item').forEach(option => {
            option.addEventListener('click', () => {
                const size = option.dataset.size;
                this.selectSize(size);
            });
        });

        // 数量控制
        const decreaseBtn = document.getElementById('btnDecrease');
        const increaseBtn = document.getElementById('btnIncrease');
        const quantityInput = document.getElementById('quantityInput');
        
        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', () => {
                this.changeQuantity(-1);
            });
        }

        if (increaseBtn) {
            increaseBtn.addEventListener('click', () => {
                this.changeQuantity(1);
            });
        }

        if (quantityInput) {
            quantityInput.addEventListener('change', (e) => {
                this.setQuantity(parseInt(e.target.value) || 1);
            });
        }
    }

    /**
     * 绑定弹窗事件
     */
    bindModalEvents() {
        // 关闭弹窗
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModal(btn.closest('.modal'));
            });
        });

        // 点击弹窗外部关闭
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        });
    }

    /**
     * 加载商品背景图片
     */
    loadProductBackground() {
        // 这里可以根据商品ID加载对应的背景图片
        // 暂时使用默认背景
        console.log('加载商品背景图片');
    }

    /**
     * 加载图案库
     */
    loadPatternLibrary() {
        // 这里可以从服务器加载图案库
        console.log('加载图案库');
    }

    /**
     * 选择颜色
     */
    selectColor(color) {
        this.selectedColor = color;
        document.querySelectorAll('.color-item').forEach(item => {
            item.classList.remove('selected');
        });
        document.querySelector(`[data-color="${color}"]`).classList.add('selected');
        console.log('选择颜色:', color);
    }

    /**
     * 选择尺寸
     */
    selectSize(size) {
        this.selectedSize = size;
        document.querySelectorAll('.size-item').forEach(item => {
            item.classList.remove('selected');
        });
        document.querySelector(`[data-size="${size}"]`).classList.add('selected');
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
        const quantityInput = document.getElementById('quantityInput');
        if (quantityInput) {
            quantityInput.value = this.quantity;
        }
        console.log('设置数量:', this.quantity);
    }

    /**
     * 显示文字编辑器
     */
    showTextEditor() {
        const modal = document.getElementById('textEditorModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    /**
     * 显示图案库
     */
    showPatternLibrary() {
        const modal = document.getElementById('patternLibraryModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    /**
     * 关闭弹窗
     */
    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * 添加文字到画布
     */
    addTextToCanvas(text, fontFamily = 'Arial', fontSize = 20, color = '#000000') {
        const textObject = new fabric.Text(text, {
            left: 100,
            top: 100,
            fontFamily: fontFamily,
            fontSize: fontSize,
            fill: color,
            selectable: true,
            hasControls: true,
            hasBorders: true,
            hasRotatingPoint: true
        });

        this.canvas.add(textObject);
        this.canvas.setActiveObject(textObject);
        this.canvas.renderAll();
        console.log('添加文字到画布:', text);
    }

    /**
     * 处理图片上传
     */
    handleImageUpload(file) {
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                // 调整图片大小以适应画布
                const scale = Math.min(200 / img.width, 200 / img.height);
                img.scale(scale);
                
                // 居中放置
                img.set({
                    left: (this.canvas.width - img.width * scale) / 2,
                    top: (this.canvas.height - img.height * scale) / 2,
                    selectable: true,
                    hasControls: true,
                    hasBorders: true,
                    hasRotatingPoint: true
                });

                this.canvas.add(img);
                this.canvas.setActiveObject(img);
                this.canvas.renderAll();
                console.log('图片已添加到画布');
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
            this.restoreCanvasState(this.history[this.historyIndex]);
        }
    }

    /**
     * 重做操作
     */
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.restoreCanvasState(this.history[this.historyIndex]);
        }
    }

    /**
     * 添加历史记录
     */
    addToHistory() {
        const state = this.canvas.toJSON();
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(state);
        
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        } else {
            this.historyIndex++;
        }
    }

    /**
     * 恢复画布状态
     */
    restoreCanvasState(state) {
        this.canvas.loadFromJSON(state, () => {
            this.canvas.renderAll();
        });
    }

    /**
     * 切换查看模式
     */
    toggleView() {
        const viewBtn = document.getElementById('btnView');
        if (viewBtn.classList.contains('active')) {
            viewBtn.classList.remove('active');
            this.canvas.selection = true;
            this.canvas.forEachObject(obj => {
                obj.selectable = true;
                obj.hasControls = true;
                obj.hasBorders = true;
            });
        } else {
            viewBtn.classList.add('active');
            this.canvas.selection = false;
            this.canvas.forEachObject(obj => {
                obj.selectable = false;
                obj.hasControls = false;
                obj.hasBorders = false;
            });
        }
        this.canvas.renderAll();
    }

    /**
     * 删除选中对象
     */
    deleteSelected() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            if (activeObject.type === 'activeSelection') {
                const activeSelection = activeObject;
                activeSelection.forEachObject(obj => {
                    this.canvas.remove(obj);
                });
            } else {
                this.canvas.remove(activeObject);
            }
            this.canvas.discardActiveObject();
            this.canvas.renderAll();
            console.log('删除选中对象');
        }
    }

    /**
     * 水平翻转
     */
    flipHorizontal() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            activeObject.set('flipX', !activeObject.flipX);
            this.canvas.renderAll();
        }
    }

    /**
     * 垂直翻转
     */
    flipVertical() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            activeObject.set('flipY', !activeObject.flipY);
            this.canvas.renderAll();
        }
    }

    /**
     * 图层操作
     */
    bringForward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.bringForward(activeObject);
        }
    }

    sendBackward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.sendBackward(activeObject);
        }
    }

    bringToFront() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.bringToFront(activeObject);
        }
    }

    sendToBack() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.sendToBack(activeObject);
        }
    }

    /**
     * 组合对象
     */
    groupObjects() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type === 'activeSelection') {
            const group = activeObject.toGroup();
            this.canvas.setActiveObject(group);
            this.canvas.renderAll();
        }
    }

    /**
     * 解组对象
     */
    ungroupObjects() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type === 'group') {
            const items = activeObject.toActiveSelection();
            this.canvas.discardActiveObject();
            this.canvas.setActiveSelection(items);
            this.canvas.renderAll();
        }
    }

    /**
     * 对齐对象
     */
    alignObjects(alignment) {
        const activeObject = this.canvas.getActiveObject();
        if (!activeObject) return;

        const canvas = this.canvas;
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;

        switch (alignment) {
            case 'left':
                activeObject.set('left', 0);
                break;
            case 'center':
                activeObject.set('left', centerX - activeObject.width / 2);
                break;
            case 'right':
                activeObject.set('left', canvas.width - activeObject.width);
                break;
            case 'top':
                activeObject.set('top', 0);
                break;
            case 'middle':
                activeObject.set('top', centerY - activeObject.height / 2);
                break;
            case 'bottom':
                activeObject.set('top', canvas.height - activeObject.height);
                break;
        }

        canvas.renderAll();
    }

    /**
     * 检查定制内容
     */
    checkCustomization() {
        const objects = this.canvas.getObjects();
        if (objects.length === 0) {
            this.showErrorMessage('请先添加一些定制内容');
            return false;
        }
        return true;
    }

    /**
     * 添加到购物车
     */
    async addToCart() {
        if (!this.checkCustomization()) {
            return;
        }

        try {
            // 获取定制数据
            const customizationData = this.getCustomizationData();
            
            // 构建请求数据
            const requestData = {
                goodsNo: this.getGoodsNo(),
                quantity: this.quantity,
                color: this.selectedColor,
                size: this.selectedSize,
                customizationData: customizationData
            };

            console.log('添加到购物车请求数据:', requestData);

            // 发送请求
            const response = await fetch('../../Custom/Controller/Front/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage(result.message || '정제 상품이 장바구니에 성공적으로 추가되었습니다');
                console.log('添加到购物车成功:', result);
                
                // 延迟跳转到购物车
                setTimeout(() => {
                    this.goToCart();
                }, 2000);
            } else {
                this.showErrorMessage(result.message || '장바구니 추가에 실패했습니다');
                console.error('添加到购物车失败:', result);
            }
        } catch (error) {
            console.error('添加到购物车出错:', error);
            this.showErrorMessage('네트워크 오류가 발생했습니다. 다시 시도해 주세요.');
        }
    }

    /**
     * 获取定制数据
     */
    getCustomizationData() {
        const objects = this.canvas.getObjects();
        const elements = objects.map(obj => {
            return {
                type: obj.type,
                left: obj.left,
                top: obj.top,
                width: obj.width,
                height: obj.height,
                angle: obj.angle,
                scaleX: obj.scaleX,
                scaleY: obj.scaleY,
                flipX: obj.flipX,
                flipY: obj.flipY,
                fill: obj.fill,
                text: obj.text,
                fontFamily: obj.fontFamily,
                fontSize: obj.fontSize
            };
        });

        return {
            elements: elements,
            backgroundImage: this.customizationData.backgroundImage,
            customImage: this.customizationData.customImage
        };
    }

    /**
     * 获取商品编号
     */
    getGoodsNo() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('goodsNo') || '1';
    }

    /**
     * 测试画布状态
     */
    testCanvasState() {
        console.log('画布状态:', {
            objects: this.canvas.getObjects(),
            activeObject: this.canvas.getActiveObject(),
            history: this.history,
            historyIndex: this.historyIndex
        });
    }

    /**
     * 显示错误消息
     */
    showErrorMessage(message) {
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');
        
        // 隐藏成功消息
        if (successDiv) {
            successDiv.style.display = 'none';
        }
        
        // 显示错误消息
        if (errorDiv) {
            const errorText = errorDiv.querySelector('.error-text');
            if (errorText) {
                errorText.textContent = message;
            }
            errorDiv.style.display = 'block';
            
            // 3秒后自动隐藏
            setTimeout(() => {
                this.hideErrorMessage();
            }, 3000);
        } else {
            // 如果没有错误消息容器，使用弹窗
            alert('오류: ' + message);
        }
    }

    /**
     * 显示成功消息
     */
    showSuccessMessage(message) {
        const successDiv = document.getElementById('successMessage');
        const errorDiv = document.getElementById('errorMessage');
        
        // 隐藏错误消息
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
        
        // 显示成功消息
        if (successDiv) {
            const successText = successDiv.querySelector('.success-text');
            if (successText) {
                successText.textContent = message;
            }
            successDiv.style.display = 'block';
            
            // 5秒后自动隐藏
            setTimeout(() => {
                this.hideSuccessMessage();
            }, 5000);
        } else {
            // 如果没有成功消息容器，使用弹窗
            alert('성공: ' + message);
        }
    }

    /**
     * 隐藏错误消息
     */
    hideErrorMessage() {
        const errorDiv = document.getElementById('errorMessage');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    /**
     * 隐藏成功消息
     */
    hideSuccessMessage() {
        const successDiv = document.getElementById('successMessage');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    /**
     * 跳转到购物车
     */
    goToCart() {
        window.location.href = '/order/cart.php';
    }

    /**
     * 分享商品
     */
    shareProduct() {
        const goodsId = document.getElementById('btnShare').dataset.goodsId;
        console.log('分享商品:', goodsId);
        // TODO: 实现分享功能
        alert('공유 기능 개발 중...');
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
    const editor = new CustomGoodsEditor();
    
    // 在控制台暴露编辑器实例，方便调试
    window.customEditor = editor;
    console.log('定制编辑器已初始化，可以通过 window.customEditor 访问');
    console.log('使用 customEditor.testCanvasState() 测试画布状态');
});
