/**
 * 商品定制页面JavaScript
 * 使用Fabric.js实现画布功能
 */

(function configureCustomEditorLogging() {
    try {
        if (typeof window === 'undefined') {
            return;
        }
        if (window.__CUSTOM_DEBUG__) {
            return;
        }
        const noop = function () {};
        ['log', 'info', 'debug'].forEach((method) => {
            if (window.console && typeof window.console[method] === 'function') {
                window.console[method] = noop;
            }
        });
    } catch (e) {
        // 忽略日志配置中的异常，避免影响主流程
    }
})();

class CustomGoodsEditor {
    constructor() {
        this.canvas = null;
        this.canvasContainer = null;
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 20;
        this.displayWidth = 0;
        this.displayHeight = 0;
        this.exportMultiplier = 1;
        this.isRestoringHistory = false;
        this.selectedColor = 'navy';
        this.selectedSize = '4.7x2.8';
        this.quantity = 1;
        this.selectedColorImage = null;
        this.currentBackgroundSrc = null;
        this.customizationData = {
            elements: [],
            backgroundImage: null,
            customImage: null
        };
        this.customerPatternImage = null; // ✅ 添加：追踪客户上传的原始图案（首张，兼容）
        this.customerPatternImages = [];  // ✅ 添加：追踪多张客户上传的图案（Base64数组）
        this.uploadedImages = [];         // ✅ 添加：追踪所有上传的图片
        this.editingTextObject = null;    // ✅ 当前正在编辑的文字对象
        this.layersListElement = null;
        this.layerIconPaths = {
            visible: '/data/skin/front/mplshop/_mplshop/images/icon/good_custom/check_layer_icon.png',
            hidden: '/data/skin/front/mplshop/_mplshop/images/icon/good_custom/hide_layer_icon.png'
        };
        this.imageControls = null;
        this.lockToggleButton = null;
        this.lockIcon = null;
        this.lockLabel = null;
        this.lockIconPaths = {
            locked: '/data/skin/front/mplshop/_mplshop/images/icon/good_custom/lock_icon.png',
            unlocked: '/data/skin/front/mplshop/_mplshop/images/icon/good_custom/unlock_icon.png'
        };
        this.customFontFamilies = [
            'Ongleaff Park Dahyeon',
            'Baedal Minjok Jua',
            'Baegle Fat',
            'PyeongChang Peace',
            'Hancom MallangMallang',
            'Coper Black',
            'Yeogiotte Jallanchae',
            'Pretendard',
            'Baemin Dohyeon',
            'NEXON Lv1 Gothic'
        ];
        this.loadedFontMap = {};
        this.objectIdCounter = 1;
        this.removeTagPadding = 8;
        // 定制区域裁剪配置（来自后台模板）
        this.clipConfig = null;
        this.totalClipPath = null;      // Fabric.Group 组合裁剪区域
        this.clipBorderShapes = [];     // 可见蓝色边框对象列表
        this.clipEnabled = true;        // 当前设计是否启用裁剪（例如 패키지 画布不启用）
        // ✅ 拖动对齐辅助线（在裁剪框内显示水平/垂直居中虚线）
        this._centerGuidesActive = false;
        this._centerGuideBoundsList = []; // [{ left, top, width, height }, ...]
        this._centerGuideStyle = {
            // 辅助线颜色：稍微灰一点（降低不透明度）
            stroke: 'rgba(0,0,0,0.45)',
            dash: [6, 4],
            lineWidth: 1
        };
        this.maxImageDimension = 3000;  // 上传图片最大边长限制
        this.allowedImageMimeTypes = ['image/png', 'image/jpeg'];
        this.allowedImageExtensions = ['png', 'jpg', 'jpeg'];
        this.imageWarningOverlay = null;
        this.imageWarningText = null;
        this.imageWarningDesc = null;
        this.fontPickerOutsideHandler = null;
        this.fontImageBasePath = '/data/skin/front/mplshop/_mplshop/images/font image/';
        // 缩略图文件命名与字体英文名并非完全一致，单独映射保证一一对应
        this.fontImageMap = {
            'Ongleaff Park Dahyeon': this.createFontImageEntry('Ongleaff Park Dahyeon'),
            'Baedal Minjok Jua': this.createFontImageEntry('Baemin Jua'),
            'Baegle Fat': this.createFontImageEntry('Bagel Fat'),
            'PyeongChang Peace': this.createFontImageEntry('PyeongChang Peace'),
            'Hancom MallangMallang': this.createFontImageEntry('Hancom MallangMallang'),
            'Coper Black': this.createFontImageEntry('Cooper Black'),
            'Yeogiotte Jallanchae': this.createFontImageEntry('YoegiOtte Jallanchae'),
            'Pretendard': this.createFontImageEntry('Pretendard'),
            'Baemin Dohyeon': this.createFontImageEntry('Baemin Dohyeon'),
            'NEXON Lv1 Gothic': this.createFontImageEntry('NEXON Lv1 Gotic')
        };
        
        // 각 전체 디자인(기본/부가 이미지) 별로 독립된 캔버스 상태를 저장
        this.designStates = {};        // { designId: canvasJSON }
        this.currentDesignId = 'base'; // 현재 선택된 디자인 ID
        
        this.init();
        this.preloadCustomFonts();
    }

    /**
     * ✅ Text 색상 UI(버튼/라벨/hidden 값) 동기화
     */
    syncTextColorUi(hex) {
        const safe = (typeof hex === 'string' && hex.trim()) ? hex.trim() : '#000000';
        const color = safe.startsWith('#') ? safe : ('#' + safe);

        const input = document.getElementById('textColor');
        const swatch = document.getElementById('textColorSwatch');
        const label = document.getElementById('textColorLabel');
        if (input) input.value = color;
        if (swatch) swatch.style.backgroundColor = color;
        if (label) label.textContent = color;
    }

    /**
     * ✅ Pickr(색상 선택기) 초기화: 취소/확인 버튼 포함
     */
    initTextColorPickr() {
        const input = document.getElementById('textColor');
        const btn = document.getElementById('textColorBtn');
        const modalBody = document.querySelector('#textModal .modal-body');
        if (!input || !btn || !modalBody) return;

        // Pickr 로드 전이면 스킵
        const PickrCtor = (typeof window !== 'undefined' && window.Pickr) ? window.Pickr : null;
        if (!PickrCtor) return;

        // 중복 초기화 방지
        if (this._textPickr) return;

        // 초기 UI 동기화
        this.syncTextColorUi(input.value || '#000000');

        this._textPickr = PickrCtor.create({
            el: btn,
            theme: 'classic',
            default: input.value || '#000000',
            useAsButton: true,
            // ✅ Pickr 패널을 textModal 내부에 렌더링 (폭을 모달과 동일하게 맞추기 위함)
            container: modalBody,
            position: 'bottom-start',
            components: {
                preview: true,
                opacity: false,
                hue: true,
                interaction: {
                    hex: true,
                    input: true,
                    save: true,
                    cancel: true
                }
            },
            i18n: {
                'btn:save': '확인',
                'btn:cancel': '취소'
            }
        });

        // ✅ 패널 오픈 시: 텍스트 모달(body) 폭에 맞추어 Pickr 패널 폭 강제 동기화
        this._textPickr.on('show', (instance) => {
            try {
                const root = instance && typeof instance.getRoot === 'function' ? instance.getRoot() : null;
                if (root && root.app && modalBody) {
                    root.app.style.width = modalBody.clientWidth + 'px';
                    root.app.style.maxWidth = 'none';
                    root.app.style.boxSizing = 'border-box';
                    // ✅ 좌/우 정렬 강제(테마 기본 오프셋/마진 제거)
                    root.app.style.margin = '0';
                    root.app.style.left = '0px';
                    root.app.style.right = '0px';
                    root.app.style.transform = 'none';
                }
            } catch (e) {}
        });

        // 확인(save): hidden 값에 반영 + 버튼 UI 업데이트
        this._textPickr.on('save', (color, instance) => {
            try {
                const hex = color ? color.toHEXA().toString() : (input.value || '#000000');
                this.syncTextColorUi(hex);
            } catch (e) {}
            try { instance && instance.hide && instance.hide(); } catch (e) {}
        });

        // 취소(cancel): hidden 값으로 되돌리고 닫기
        this._textPickr.on('cancel', (instance) => {
            try {
                const current = (input.value || '#000000');
                if (instance && typeof instance.setColor === 'function') {
                    instance.setColor(current, true);
                }
            } catch (e) {}
            try { instance && instance.hide && instance.hide(); } catch (e) {}
        });
    }
    
    createFontImageEntry(fileBase = '') {
        if (!fileBase) {
            return null;
        }
        return {
            normal: `${this.fontImageBasePath}${fileBase}.png`,
            hover: `${this.fontImageBasePath}${fileBase}(Hover).png`
        };
    }
    
    async init() {
        try {
            this.initCanvas();
            this.initEventListeners();
            
            // 异步加载商品背景图片
            await this.loadProductBackground();

            // 初始化定制区域裁剪配置（如果后台有配置）
            this.initClipRegionsFromPageConfig();
            
            this.loadPatternLibrary();
            this.addToHistory();
            this.updateToolbarState();
            console.log('商品定制编辑器初始化完成');
        } catch (error) {
            console.error('初始化失败:', error);
        }
    }
    
    /**
     * 初始化Fabric.js画布
     */
    initCanvas() {
        // 获取设备像素比，用于高分辨率显示
        const devicePixelRatio = window.devicePixelRatio || 1;
        
        // 基础显示尺寸
        const displayWidth = 860;
        const displayHeight = 860;

        this.displayWidth = displayWidth;
        this.displayHeight = displayHeight;
        
        // 实际canvas尺寸（按DPR放大）
        const actualWidth = displayWidth * devicePixelRatio;
        const actualHeight = displayHeight * devicePixelRatio;
        
        this.canvas = new fabric.Canvas('customCanvas', {
            width: actualWidth,
            height: actualHeight,
            backgroundColor: 'transparent',
            selection: true,
            preserveObjectStacking: true,
            allowTouchScrolling: false,
            enableRetinaScaling: false, // 手动控制缩放
            centeredScaling: false,
            centeredRotation: true,
            selectionBorderColor: '#4285f4',
            selectionLineWidth: 2,
            interactive: true,
            moveCursor: 'move',
            hoverCursor: 'move',
            defaultCursor: 'default'
        });

        this.exportMultiplier = actualWidth / displayWidth;
        
        // 设置CSS显示尺寸为860x860
        this.canvas.upperCanvasEl.style.width = displayWidth + 'px';
        this.canvas.upperCanvasEl.style.height = displayHeight + 'px';
        this.canvas.lowerCanvasEl.style.width = displayWidth + 'px';
        this.canvas.lowerCanvasEl.style.height = displayHeight + 'px';
        
        // 确保canvas元素能接收事件并重叠
        this.canvas.upperCanvasEl.style.zIndex = '10';
        this.canvas.lowerCanvasEl.style.zIndex = '9';
        
        // 设置canvas容器的CSS尺寸
        const canvasContainer = document.getElementById('customCanvas').parentElement;
        if (canvasContainer) {
            canvasContainer.style.width = displayWidth + 'px';
            canvasContainer.style.height = displayHeight + 'px';
            if (getComputedStyle(canvasContainer).position === 'static') {
                canvasContainer.style.position = 'relative';
            }
            this.canvasContainer = canvasContainer;
        }
        
        console.log('Canvas initialized:', {
            displaySize: `${displayWidth}x${displayHeight}`,
            actualSize: `${actualWidth}x${actualHeight}`,
            devicePixelRatio: devicePixelRatio
        });
        
        // 设置画布事件
        this.canvas.on('object:added', (e) => {
            if (!this.isRestoringHistory) {
                this.addToHistory();
            }
            const target = e ? e.target : null;

            if (target && target !== this.canvas.backgroundImage) {
                // ✅ 裁剪框(clipOverlay) 仅用于提示：禁止选中/拖动/缩放/旋转
                if (target.customType === 'clipOverlay') {
                    target.set({
                        selectable: false,
                        evented: false,
                        hasControls: false,
                        hasBorders: false,
                        lockMovementX: true,
                        lockMovementY: true,
                        lockScalingX: true,
                        lockScalingY: true,
                        lockRotation: true,
                        hoverCursor: 'default',
                        moveCursor: 'default',
                        excludeFromExport: true
                    });
                } else {
                target.set({
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
                }
                console.log('对象已添加到画布:', target.type, target);
            }

            this.assignCustomObjectId(target);
            this.refreshLayersPanel();
            if (target === this.canvas.getActiveObject()) {
                this.updateImageControls(target);
            }
            if (target && target.customType === 'removeTag') {
                const image = this.findImageById(target.linkedImageId);
                if (image) {
                    this.keepRemoveTagWithinImage(target, image, true);
                    this.updateRemoveTagRelativePosition(target, image);
                    target.bringToFront();
                }
            }

            // 对象新增后，根据是否有内容更新蓝色裁剪框显隐
            this.updateClipOverlayVisibility();
        });
        
        this.canvas.on('object:modified', (e) => {
            if (!this.isRestoringHistory) {
                this.addToHistory();
            }
            this.refreshLayersPanel();
            this.updateLayerSelection();
            this.handleActiveImageTransform(e);
            // 拖动结束后关闭辅助线
            this.clearCenterGuides();
        });
        
        this.canvas.on('object:removed', (e) => {
            if (!this.isRestoringHistory) {
                this.addToHistory();
            }
            const target = e ? e.target : null;
            if (target) {
                if (target.type === 'image') {
                    this.removeAssociatedRemoveTag(target);
                }
                if (target.customType === 'removeTag') {
                    const image = this.findImageById(target.linkedImageId);
                    if (image) {
                        delete image.removeTagId;
                        const active = this.canvas.getActiveObject();
                        if (active && active.type === 'image') {
                            this.updateBackgroundRemovalButtonState(active);
                        } else {
                            this.updateBackgroundRemovalButtonState(null);
                        }
                    }
                }
            }
            this.refreshLayersPanel();
            this.updateLayerSelection();
            this.updateImageControls(this.canvas.getActiveObject());

            // 对象删除后，同样更新蓝色裁剪框显隐
            this.updateClipOverlayVisibility();
        });
        
        this.canvas.on('selection:created', () => {
            const activeObject = this.canvas.getActiveObject();
            this.updateToolbarState();
            this.updateLayerSelection();
            this.updateImageControls(activeObject);
        });
        
        this.canvas.on('selection:cleared', () => {
            this.updateToolbarState();
            this.updateLayerSelection();
            this.updateImageControls(null);
            this.clearCenterGuides();
        });
        
        this.canvas.on('selection:updated', () => {
            const activeObject = this.canvas.getActiveObject();
            this.updateToolbarState();
            this.updateLayerSelection();
            this.updateImageControls(activeObject);
        });
        
        this.canvas.on('object:moving', (e) => {
            this.handleActiveImageTransform(e);
            this.updateCenterGuidesForTarget(e ? e.target : null);
        });
        this.canvas.on('object:scaling', (e) => {
            this.handleActiveImageTransform(e);
            this.clearCenterGuides();
        });
        this.canvas.on('object:rotating', (e) => {
            this.handleActiveImageTransform(e);
            this.clearCenterGuides();
        });

        // 鼠标松开时关闭辅助线（避免拖动后残留）
        this.canvas.on('mouse:up', () => {
            this.clearCenterGuides();
        });

        // 在渲染后用 canvas context 直接绘制辅助线（不作为对象加入 canvas）
        this.canvas.on('after:render', (opt) => {
            const ctx = (opt && opt.ctx) ? opt.ctx : (this.canvas ? this.canvas.contextContainer : null);
            if (!ctx) return;
            this.drawCenterGuides(ctx);
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
        
        // 添加调试事件 + 双击文字进入编辑模式
        this.canvas.on('mouse:down', (e) => {
            console.log('鼠标按下事件:', e);
            try {
                // 鼠标双击（detail === 2）
                if (e.e && e.e.detail === 2) {
                    const target = e.target;
                    if (target && (target.type === 'text' || target.type === 'i-text' || target.type === 'textbox')) {
                        this.startEditText(target);
                    }
                }
            } catch (err) {
                console.warn('处理文本双击编辑失败', err);
            }
        });
        
        this.canvas.on('object:selected', (e) => {
            console.log('对象被选中:', e.target);
        });
        
        this.canvas.on('selection:created', (e) => {
            console.log('选择创建:', e.target);
        });
    }

    /**
     * 从 pageConfig 初始化定制区域裁剪配置
     */
    initClipRegionsFromPageConfig() {
        if (!this.canvas) return;

        const cfgEl = document.getElementById('pageConfig');
        if (!cfgEl || !cfgEl.textContent || !cfgEl.textContent.trim()) {
            return;
        }

        let pageConfig;
        try {
            pageConfig = JSON.parse(cfgEl.textContent);
        } catch (e) {
            console.warn('解析 pageConfig 失败', e);
            return;
        }

        const viewBoxWidth = parseFloat(pageConfig.viewBoxWidth);
        const viewBoxHeight = parseFloat(pageConfig.viewBoxHeight);
        const clipRegions = Array.isArray(pageConfig.clipRegions) ? pageConfig.clipRegions : [];

        if (!viewBoxWidth || !viewBoxHeight || clipRegions.length === 0) {
            return;
        }

        this.clipConfig = { viewBoxWidth, viewBoxHeight, clipRegions };

        // 逻辑显示尺寸（与 CSS 中的背景容器一致，通常是 860x860）
        const logicalWidth  = this.displayWidth  || this.canvas.getWidth()  || 860;
        const logicalHeight = this.displayHeight || this.canvas.getHeight() || 860;

        // 和 <img object-fit="contain"> 保持一致：按宽/高最小比例缩放 viewBox
        const cssScale = Math.min(logicalWidth / viewBoxWidth, logicalHeight / viewBoxHeight);

        // 在逻辑坐标系中的偏移（以左上角为原点）
        const offsetXCss = (logicalWidth  - viewBoxWidth  * cssScale) / 2;
        const offsetYCss = (logicalHeight - viewBoxHeight * cssScale) / 2;

        // 画布内部像素与逻辑尺寸的比例（考虑设备像素比 / Fabric 内部缩放）
        const canvasWidth  = this.canvas.width  || logicalWidth;
        const canvasHeight = this.canvas.height || logicalHeight;
        const dpr = canvasWidth / logicalWidth;

        const clipShapes = [];
        const borderShapes = [];

        clipRegions.forEach((region) => {
            if (region.type === 'rect') {
                const x = parseFloat(region.x);
                const y = parseFloat(region.y);
                const w = parseFloat(region.w);
                const h = parseFloat(region.h);
                if (!isFinite(x) || !isFinite(y) || !isFinite(w) || !isFinite(h)) {
                    return;
                }

                const left   = (offsetXCss + x * cssScale) * dpr;
                const top    = (offsetYCss + y * cssScale) * dpr;
                const width  = w * cssScale * dpr;
                const height = h * cssScale * dpr;

                const rectClip = new fabric.Rect({
                    left,
                    top,
                    width,
                    height,
                    originX: 'left',
                    originY: 'top',
                    absolutePositioned: true,
                    fill: 'rgba(0,0,0,1)',
                    strokeWidth: 0,
                    selectable: false,
                    evented: false,
                    excludeFromExport: true
                });

                const rectBorder = new fabric.Rect({
                    left,
                    top,
                    width,
                    height,
                    originX: 'left',
                    originY: 'top',
                    fill: 'rgba(0,0,0,0)',
                    stroke: 'rgba(0,153,255,1)',
                    strokeWidth: 2,
                    strokeDashArray: [6, 4], // 蓝色虚线
                    visible: false,          // 默认隐藏，等有内容时再显示
                    selectable: false,
                    evented: false,
                    excludeFromExport: true,
                    customType: 'clipOverlay'
                });

                clipShapes.push(rectClip);
                borderShapes.push(rectBorder);
            } else if (region.type === 'path' && region.d) {
                try {
                    // 允许通过后台配置 x / y（viewBox 坐标）对整块 path 位置做微调
                    const offsetViewX = isFinite(parseFloat(region.x)) ? parseFloat(region.x) : 5.5;
                    const offsetViewY = isFinite(parseFloat(region.y)) ? parseFloat(region.y) : 10;

                    // 先在逻辑坐标系里做缩放+偏移，再乘 dpr 映射到 canvas 内部坐标
                    const left = (offsetXCss + offsetViewX * cssScale) * dpr;
                    const top  = (offsetYCss + offsetViewY * cssScale) * dpr;

                    const pathClip = new fabric.Path(region.d, {
                        left,
                        top,
                        originX: 'left',
                        originY: 'top',
                        scaleX: cssScale * dpr,
                        scaleY: cssScale * dpr,
                        absolutePositioned: true,
                        fill: 'rgba(0,0,0,1)',
                        strokeWidth: 0,
                        selectable: false,
                        evented: false,
                        excludeFromExport: true
                    });

                    const pathBorder = new fabric.Path(region.d, {
                        left,
                        top,
                        originX: 'left',
                        originY: 'top',
                        scaleX: cssScale * dpr,
                        scaleY: cssScale * dpr,
                        fill: 'rgba(0,0,0,0)',
                        stroke: 'rgba(0,153,255,1)',
                        strokeWidth: 2,
                        strokeDashArray: [6, 4], // 蓝色虚线
                        visible: false,           // 默认隐藏
                        selectable: false,
                        evented: false,
                        excludeFromExport: true,
                        customType: 'clipOverlay'
                    });

                    clipShapes.push(pathClip);
                    borderShapes.push(pathBorder);
                } catch (e) {
                    console.warn('解析 clip path 失败', e, region);
                }
            }
        });

        if (clipShapes.length === 0) {
            return;
        }

        this.totalClipPath = new fabric.Group(clipShapes, {
            absolutePositioned: true,
            selectable: false,
            evented: false,
            excludeFromExport: true
        });

        borderShapes.forEach((shape) => {
            this.canvas.add(shape);
        });
        this.clipBorderShapes = borderShapes;

        // 根据当前画布内容与设计类型，决定是否显示蓝色裁剪框
        this.updateClipOverlayVisibility();
    }

    /**
     * 如果已配置定制区域，则给对象套上 clipPath
     */
    applyClipPathIfAvailable(obj) {
        if (!obj) return;

        // 某些设计（如 패키지）不启用裁剪时，需移除 clipPath
        if (!this.clipEnabled || !this.totalClipPath) {
            obj.clipPath = null;
            return;
        }

        obj.clipPath = this.totalClipPath;
    }

    /**
     * 是否对当前拖动对象显示“裁剪框居中辅助线”
     */
    shouldShowCenterGuidesForTarget(target) {
        if (!this.canvas || !target) return false;
        if (!this.clipEnabled) return false;
        if (target === this.canvas.backgroundImage) return false;
        if (target.customType === 'clipOverlay') return false;
        if (target.customType === 'removeTag') return false;
        const t = String(target.type || '');
        // 图案/图片/文字/组 都允许显示；其它类型默认不显示
        return (t === 'image' || t === 'text' || t === 'i-text' || t === 'textbox' || t === 'group' || t === 'activeSelection');
    }

    /**
     * 在拖动过程中更新辅助线（以“面积最大的裁剪框”为基准）
     */
    updateCenterGuidesForTarget(target) {
        if (!this.shouldShowCenterGuidesForTarget(target)) {
            this.clearCenterGuides();
            return;
        }

        const shapes = Array.isArray(this.clipBorderShapes) ? this.clipBorderShapes : [];
        const boundsList = shapes
            .filter(s => s && s.customType === 'clipOverlay')
            .map((shape) => {
                try { return shape.getBoundingRect(true, true); } catch (e) { return null; }
            })
            .filter((b) =>
                b &&
                isFinite(b.left) && isFinite(b.top) && isFinite(b.width) && isFinite(b.height) &&
                b.width > 0 && b.height > 0
            );

        if (!boundsList.length) {
            this.clearCenterGuides();
            return;
        }
        this._centerGuidesActive = true;
        this._centerGuideBoundsList = boundsList;
        if (this.canvas) {
            this.canvas.requestRenderAll();
        }
    }

    clearCenterGuides() {
        if (!this._centerGuidesActive && (!this._centerGuideBoundsList || this._centerGuideBoundsList.length === 0)) {
            return;
        }
        this._centerGuidesActive = false;
        this._centerGuideBoundsList = [];
        if (this.canvas) {
            this.canvas.requestRenderAll();
        }
    }

    /**
     * 绘制黑色虚线辅助线（在裁剪框内的水平/垂直居中线）
     */
    drawCenterGuides(ctx) {
        if (
            !this._centerGuidesActive ||
            !Array.isArray(this._centerGuideBoundsList) ||
            this._centerGuideBoundsList.length === 0
        ) {
            return;
        }
        const style = this._centerGuideStyle || {};

        ctx.save();
        ctx.strokeStyle = style.stroke || 'rgba(0,0,0,0.9)';
        ctx.lineWidth = style.lineWidth || 1;
        try {
            ctx.setLineDash(Array.isArray(style.dash) ? style.dash : [6, 4]);
        } catch (e) {
            // ignore
        }

        this._centerGuideBoundsList.forEach((b) => {
            if (!b || !isFinite(b.left) || !isFinite(b.top) || !isFinite(b.width) || !isFinite(b.height)) {
                return;
            }
            const x = b.left + (b.width / 2);
            const y = b.top + (b.height / 2);

            // 垂直居中线
            ctx.beginPath();
            ctx.moveTo(x, b.top);
            ctx.lineTo(x, b.top + b.height);
            ctx.stroke();

            // 水平居中线
            ctx.beginPath();
            ctx.moveTo(b.left, y);
            ctx.lineTo(b.left + b.width, y);
            ctx.stroke();
        });

        ctx.restore();
    }

    /**
     * 获取面积最大的裁剪框(clipOverlay)边界，用于将新对象默认放入裁剪区域内
     * @returns {{left:number, top:number, width:number, height:number}|null}
     */
    getLargestClipOverlayBounds() {
        if (!this.canvas || !this.clipEnabled) return null;
        const shapes = Array.isArray(this.clipBorderShapes) ? this.clipBorderShapes : [];
        const candidates = shapes.filter(s => s && s.customType === 'clipOverlay');
        if (candidates.length === 0) return null;

        let best = null;
        let bestArea = -1;

        candidates.forEach((shape) => {
            try {
                const b = shape.getBoundingRect(true, true);
                if (!b || !isFinite(b.width) || !isFinite(b.height)) return;
                const area = b.width * b.height;
                if (area > bestArea) {
                    bestArea = area;
                    best = b;
                }
            } catch (e) {
                // ignore
            }
        });

        return best;
    }

    /**
     * 将对象默认放入“面积最大的裁剪框”内（居中 + 必要时等比缩放）
     * - 若没有裁剪框配置/当前设计不启用裁剪，则不处理
     */
    placeObjectInsideLargestClipRegion(obj, padding = 16) {
        if (!obj || !this.canvas) return false;

        const bounds = this.getLargestClipOverlayBounds();
        if (!bounds) return false;

        const innerLeft = bounds.left + padding;
        const innerTop = bounds.top + padding;
        const innerW = Math.max(1, bounds.width - padding * 2);
        const innerH = Math.max(1, bounds.height - padding * 2);

        // 获取对象当前缩放后的尺寸
        const scaleX = (typeof obj.scaleX === 'number') ? obj.scaleX : 1;
        const scaleY = (typeof obj.scaleY === 'number') ? obj.scaleY : 1;
        const objW = (typeof obj.getScaledWidth === 'function')
            ? obj.getScaledWidth()
            : (parseFloat(obj.width) || 0) * scaleX;
        const objH = (typeof obj.getScaledHeight === 'function')
            ? obj.getScaledHeight()
            : (parseFloat(obj.height) || 0) * scaleY;

        if (objW > 0 && objH > 0) {
            const fit = Math.min((innerW * 0.9) / objW, (innerH * 0.9) / objH, 1);
            if (fit < 1) {
                obj.scaleX = scaleX * fit;
                obj.scaleY = scaleY * fit;
            }
        }

        // 居中放置到裁剪框内
        obj.set({
            originX: 'center',
            originY: 'center',
            left: innerLeft + innerW / 2,
            top: innerTop + innerH / 2
        });

        try { obj.setCoords && obj.setCoords(); } catch (e) {}
        return true;
    }

    /**
     * 给当前画布上的所有对象统一应用/移除裁剪
     */
    applyClipPathForAllObjects() {
        if (!this.canvas) return;

        const objs = this.canvas.getObjects() || [];
        objs.forEach(o => {
            // 蓝色边框自己永远不套 clipPath，避免被裁剪掉一部分
            if (o.customType === 'clipOverlay') {
                o.clipPath = null;
                return;
            }

            if (this.clipEnabled && this.totalClipPath) {
                o.clipPath = this.totalClipPath;
            } else {
                o.clipPath = null;
            }
        });
    }

    /**
     * 根据画布内容和当前设计，控制蓝色裁剪框显隐
     */
    updateClipOverlayVisibility() {
        if (!this.canvas || !Array.isArray(this.clipBorderShapes) || this.clipBorderShapes.length === 0) {
            return;
        }

        const objects = this.canvas.getObjects() || [];
        const hasContent = objects.some(o =>
            o !== this.canvas.backgroundImage &&
            o.customType !== 'clipOverlay' &&
            o.customType !== 'removeTag'
        );

        // 只有当前设计启用裁剪，且画布上有内容时才显示蓝色框
        const visible = this.clipEnabled && hasContent;

        this.clipBorderShapes.forEach(shape => {
            shape.visible = visible;
            if (visible) {
                this.canvas.bringToFront(shape);
            }
        });

        this.canvas.renderAll();
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
        this.bindImageUploadWarningEvents();
        
        this.initializeLayersPanel();
        this.initializeImageControls();
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
        document.getElementById('btnRotate90').addEventListener('click', () => this.rotate90());
        
        // 图层操作
        document.getElementById('btnBringForward').addEventListener('click', () => this.bringForward());
        document.getElementById('btnSendBackward').addEventListener('click', () => this.sendBackward());
        document.getElementById('btnBringToFront').addEventListener('click', () => this.bringToFront());
        document.getElementById('btnSendToBack').addEventListener('click', () => this.sendToBack());
        
        // 组合操作
        document.getElementById('btnGroup').addEventListener('click', () => this.groupObjects());
        document.getElementById('btnUngroup').addEventListener('click', () => this.ungroupObjects());
        
        // 주의사항 버튼: 디자인 검수 안내 팝업만 열기
        document.getElementById('btnCheck').addEventListener('click', () => this.showDesignCheckModal());

        // ✅ 커스텀 방법 안내 버튼
        const guideBtn = document.getElementById('btnCustomGuide');
        if (guideBtn) {
            guideBtn.addEventListener('click', () => this.showCustomGuideModal());
        }
        
        // 键盘删除功能
        document.addEventListener('keydown', (e) => {
            // 只在没有输入框获得焦点时响应删除键
            const activeElement = document.activeElement;
            const isInputElement = activeElement && (
                activeElement.tagName === 'INPUT' || 
                activeElement.tagName === 'TEXTAREA' || 
                activeElement.isContentEditable
            );

            // 撤销/重做快捷键
            // - Windows/Linux: Ctrl+Z / Ctrl+Shift+Z
            // - macOS: Cmd+Z / Cmd+Shift+Z
            if (!isInputElement) {
                const isMac = (typeof navigator !== 'undefined')
                    && typeof navigator.platform === 'string'
                    && navigator.platform.toUpperCase().includes('MAC');
                const modKeyPressed = isMac ? e.metaKey : e.ctrlKey;
                const key = (e.key || '').toLowerCase();

                if (modKeyPressed && !e.shiftKey && key === 'z') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.undo();
                    return;
                }

                // 需求：Ctrl+Shift+Z（或 Cmd+Shift+Z）
                if (modKeyPressed && e.shiftKey && key === 'z') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.redo();
                    return;
                }

                // 兼容：部分用户习惯 Ctrl+Y 重做
                if (!isMac && e.ctrlKey && !e.shiftKey && key === 'y') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.redo();
                    return;
                }
            }
            
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
            
            // 文件上传处理（支持多选）
            fileInput.addEventListener('change', async (e) => {
                console.log('文件选择事件触发:', e.target.files);
                const files = e.target.files;
                if (files && files.length > 0) {
                    // 逐个处理选中的文件
                    for (let i = 0; i < files.length; i++) {
                        try {
                            await this.handleImageUpload(files[i]);
                        } catch (error) {
                            console.error('处理图片上传失败:', error);
                        }
                    }
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
            // 显示功能准备中提醒
            alert('기능 준비 중입니다. 곧 만나보실 수 있습니다!');
        });
        
        // 다운로드 버튼(UX에서 제거됨)
        // - 기능은 유지: 필요 시 다른 경로(예: 관리/테스트/외부몰 플로우)에서 재사용 가능
        // - 버튼이 없더라도 오류 없이 동작하도록 방어적으로 처리
        const downloadBtn = document.getElementById('btnDownload');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                this.downloadCustomizedSVG();
            });
        }
    }
    
    /**
     * 绑定商品信息事件
     */
    bindProductInfoEvents() {
        // 颜色选择
        document.querySelectorAll('.color-item').forEach(option => {
            option.addEventListener('click', () => {
                const color = option.dataset.color;
                const imageSrc = option.getAttribute('data-image-src')
                    || option.getAttribute('data-svg-path')
                    || option.getAttribute('data-bg-image');
                this.selectColor(color, imageSrc);
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

        // ✅ 加入购物车按钮
        const btnAddToCart = document.getElementById('btnAddToCart');
        if (btnAddToCart) {
            btnAddToCart.addEventListener('click', () => {
                console.log('✅ 加入购物车按钮被点击');
                this.addToCart();
            });
            console.log('✅ 加入购物车按钮事件已绑定');
        } else {
            console.error('❌ 加入购物车按钮未找到！');
        }
    }

    /**
     * 绑定弹窗事件
     */
    bindModalEvents() {
        // 文本编辑弹窗 - 确认按钮
        const btnConfirmText = document.getElementById('btnConfirmText');
        if (btnConfirmText) {
            btnConfirmText.addEventListener('click', async () => {
                const text = document.getElementById('textContent').value;
                const fontSize = parseInt(document.getElementById('textSize').value);
                const fontColor = document.getElementById('textColor').value;
                const fontFamily = document.getElementById('textFont').value;
                
                if (text.trim()) {
                    await this.ensureFontLoaded(fontFamily);
                    if (this.editingTextObject) {
                        // ✅ 编辑已有文字
                        this.editingTextObject.set({
                            text: text,
                            fontSize: fontSize,
                            fill: fontColor,
                            fontFamily: fontFamily
                        });
                        this.canvas.renderAll();
                        this.editingTextObject = null;
                    } else {
                        // ✅ 新增文字
                        this.addTextToCanvas(text, fontFamily, fontSize, fontColor);
                    }
                    this.closeModal(document.getElementById('textModal'));
                    // 清空输入框
                    document.getElementById('textContent').value = '';
                } else {
                    alert('텍스트 내용을 입력해주세요');
                }
            });
        }

        // 文本编辑弹窗 - 取消按钮
        const btnCancelText = document.getElementById('btnCancelText');
        if (btnCancelText) {
            btnCancelText.addEventListener('click', () => {
                this.closeModal(document.getElementById('textModal'));
                this.editingTextObject = null; // ✅ 退出编辑模式
                // 清空输入框
                document.getElementById('textContent').value = '';
            });
        }
        
        // ✅ Pickr(색상 선택기) 초기화
        this.initTextColorPickr();
        
        // 关闭弹窗
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModal(btn.closest('.text-modal, .pattern-modal'));
            });
        });

        // 点击弹窗外部关闭
        document.querySelectorAll('.text-modal, .pattern-modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target !== modal) {
                    return;
                }

                // ✅ 防止误触：textModal / externalOrderModal 不允许点击遮罩关闭
                if (modal && (modal.id === 'textModal' || modal.id === 'externalOrderModal')) {
                    return;
                }

                this.closeModal(modal);
            });
        });

        this.initFontSelector();
    }

    initFontSelector() {
        const fontSelect = document.getElementById('textFont');
        const picker = document.getElementById('textFontPicker');
        const menu = document.getElementById('fontPickerMenu');
        const toggle = document.getElementById('fontPickerToggle');
        if (!fontSelect || !picker || !menu || !toggle) {
            return;
        }
        this.fontPickerButtons = [];

        menu.innerHTML = '';
        const options = Array.from(fontSelect.options || []);

        options.forEach((option) => {
            const fontName = option.value;
            if (!fontName) {
                return;
            }

            const assets = this.fontImageMap ? this.fontImageMap[fontName] : null;
            const item = document.createElement('li');
            item.className = 'font-picker-item';

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'font-picker-btn';
            button.dataset.font = fontName;
            button.setAttribute('aria-label', fontName);

            if (assets && assets.normal) {
                const img = document.createElement('img');
                img.className = 'font-picker-img';
                img.alt = fontName;
                img.loading = 'lazy';
                img.decoding = 'async';
                img.dataset.normalSrc = assets.normal;
                img.dataset.hoverSrc = assets.hover || assets.normal;
                img.dataset.loaded = 'false';
                button.appendChild(img);
                button.addEventListener('mouseenter', () => {
                    this.ensureFontButtonImage(button);
                    if (!button.classList.contains('is-active')) {
                        this.applyFontButtonImage(button, 'hover');
                    }
                });
                button.addEventListener('mouseleave', () => {
                    if (!button.classList.contains('is-active')) {
                        this.applyFontButtonImage(button, 'normal');
                    }
                });
            } else {
                button.textContent = fontName;
                button.classList.add('font-picker-btn--text');
            }

            button.addEventListener('click', () => {
                this.applyFontSelection(fontName);
            });

            item.appendChild(button);
            menu.appendChild(item);
            this.fontPickerButtons.push(button);
        });

        if (!menu._fontScrollBound) {
            menu.addEventListener('scroll', () => this.lazyLoadVisibleFontButtons(menu));
            menu._fontScrollBound = true;
        }

        toggle.addEventListener('click', () => {
            picker.classList.toggle('is-open');
            menu.classList.toggle('is-open');
            if (picker.classList.contains('is-open')) {
                requestAnimationFrame(() => this.lazyLoadVisibleFontButtons(menu));
            }
        });

        if (this.fontPickerOutsideHandler) {
            document.removeEventListener('click', this.fontPickerOutsideHandler);
        }
        this.fontPickerOutsideHandler = (event) => {
            if (!event.target.closest('.text-font-picker')) {
                picker.classList.remove('is-open');
                menu.classList.remove('is-open');
            }
        };
        document.addEventListener('click', this.fontPickerOutsideHandler);

        const initialFont = (fontSelect.value && this.fontImageMap[fontSelect.value])
            ? fontSelect.value
            : (options.find((opt) => this.fontImageMap && this.fontImageMap[opt.value])?.value || (options[0] ? options[0].value : ''));

        if (initialFont) {
            this.applyFontSelection(initialFont, { skipMenuClose: true });
        } else {
            this.updateFontPreview(fontSelect.value || '');
        }
    }

    applyFontSelection(fontName, options = {}) {
        const fontSelect = document.getElementById('textFont');
        const menu = document.getElementById('fontPickerMenu');
        const picker = document.getElementById('textFontPicker');
        if (fontSelect && fontName) {
            fontSelect.value = fontName;
        }

        this.updateFontPreview(fontName);

        if (menu) {
            const buttons = menu.querySelectorAll('.font-picker-btn');
            buttons.forEach((button) => {
                const targetFont = button.dataset.font;
                const assets = this.fontImageMap ? this.fontImageMap[targetFont] : null;
                const isActive = targetFont === fontName;
                button.classList.toggle('is-active', isActive);

                if (assets && assets.normal) {
                    if (isActive) {
                        this.ensureFontButtonImage(button);
                        this.applyFontButtonImage(button, 'hover');
                    } else {
                        this.applyFontButtonImage(button, 'normal');
                    }
                }
            });

            if (!options.skipMenuClose) {
                menu.classList.remove('is-open');
            }
        }

        if (!options.skipMenuClose && picker) {
            picker.classList.remove('is-open');
        }
    }

    updateFontPreview(fontName) {
        const thumb = document.getElementById('fontPickerThumb');
        if (!thumb) {
            return;
        }

        const assets = this.fontImageMap ? this.fontImageMap[fontName] : null;
        if (assets && assets.normal) {
            thumb.style.backgroundImage = `url('${assets.normal}')`;
            thumb.style.backgroundColor = 'transparent';
        } else {
            thumb.style.backgroundImage = 'none';
            thumb.style.backgroundColor = '#f5f5f5';
        }
    }

    lazyLoadVisibleFontButtons(menu) {
        if (!menu || !menu.classList.contains('is-open')) {
            return;
        }
        const buttons = this.fontPickerButtons || [];
        if (!buttons.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            buttons.forEach((btn) => this.ensureFontButtonImage(btn));
            return;
        }

        if (!this.fontButtonObserver) {
            this.fontButtonObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const btn = entry.target;
                        this.ensureFontButtonImage(btn);
                        this.fontButtonObserver.unobserve(btn);
                    }
                });
            }, { root: menu, rootMargin: '60px' });
        }

        buttons.forEach((btn) => {
            if (btn.dataset.fontLoaded === 'true') {
                return;
            }
            this.fontButtonObserver.observe(btn);
        });
    }

    ensureFontButtonImage(button) {
        if (!button) {
            return;
        }
        const imgEl = button.querySelector('.font-picker-img');
        if (!imgEl || imgEl.dataset.loaded === 'true') {
            return;
        }
        const normal = imgEl.dataset.normalSrc;
        if (normal) {
            imgEl.src = normal;
            imgEl.dataset.loaded = 'true';
            const hover = imgEl.dataset.hoverSrc;
            if (hover && hover !== normal) {
                const preloadImg = new Image();
                preloadImg.src = hover;
            }
        }
    }

    applyFontButtonImage(button, state = 'normal') {
        if (!button) {
            return;
        }
        const imgEl = button.querySelector('.font-picker-img');
        if (!imgEl) {
            return;
        }
        const normal = imgEl.dataset.normalSrc;
        const hover = imgEl.dataset.hoverSrc || normal;
        if (!normal) {
            return;
        }
        if (imgEl.dataset.loaded !== 'true') {
            this.ensureFontButtonImage(button);
        }
        imgEl.src = state === 'hover' ? hover : normal;
    }

    /**
     * 绑定图片尺寸/格式警告弹层事件
     */
    bindImageUploadWarningEvents() {
        this.ensureImageUploadWarningElements();
        if (!this.imageWarningOverlay) {
            return;
        }

        const closeBtn = this.imageWarningOverlay.querySelector('.custom-alert-close');
        if (closeBtn && !closeBtn._imageWarningBound) {
            closeBtn.addEventListener('click', () => this.hideImageUploadWarning());
            closeBtn._imageWarningBound = true;
        }

        this.imageWarningOverlay.addEventListener('click', (event) => {
            if (event.target === this.imageWarningOverlay) {
                this.hideImageUploadWarning();
            }
        });
    }

    ensureImageUploadWarningElements() {
        if (!this.imageWarningOverlay) {
            this.imageWarningOverlay = document.getElementById('imageUploadWarning');
        }
        if (this.imageWarningOverlay && !this.imageWarningText) {
            this.imageWarningText = this.imageWarningOverlay.querySelector('.custom-alert-text');
        }
        if (this.imageWarningOverlay && !this.imageWarningDesc) {
            this.imageWarningDesc = this.imageWarningOverlay.querySelector('.custom-alert-desc');
        }
    }

    showImageUploadWarning(message, detailText) {
        this.ensureImageUploadWarningElements();
        if (!this.imageWarningOverlay) {
            alert(message || '이미지를 업로드할 수 없습니다.');
            return;
        }

        if (this.imageWarningText && message) {
            this.imageWarningText.textContent = message;
        }

        if (this.imageWarningDesc) {
            if (detailText) {
                this.imageWarningDesc.textContent = detailText;
                this.imageWarningDesc.style.display = 'block';
            } else {
                this.imageWarningDesc.textContent = '';
                this.imageWarningDesc.style.display = 'none';
            }
        }

        this.imageWarningOverlay.style.display = 'flex';
        this.imageWarningOverlay.setAttribute('aria-hidden', 'false');
    }

    hideImageUploadWarning() {
        this.ensureImageUploadWarningElements();
        if (!this.imageWarningOverlay) {
            return;
        }
        this.imageWarningOverlay.style.display = 'none';
        this.imageWarningOverlay.setAttribute('aria-hidden', 'true');
    }

    /**
     * 디자인 검수 안내 팝업 열기
     */
    showDesignCheckModal() {
        const modal = document.getElementById('designCheckModal');
        if (!modal) {
            console.warn('designCheckModal not found');
            return;
        }
        modal.style.display = 'block';

        const confirmBtn = document.getElementById('btnConfirmDesignCheck');
        if (confirmBtn && !confirmBtn._designCheckBound) {
            confirmBtn.addEventListener('click', () => this.hideDesignCheckModal());
            confirmBtn._designCheckBound = true;
        }

        const backdrop = modal.querySelector('.design-check-backdrop');
        if (backdrop && !backdrop._designCheckBound) {
            backdrop.addEventListener('click', () => this.hideDesignCheckModal());
            backdrop._designCheckBound = true;
        }
    }

    /**
     * 패키지 영역 안내 팝업 열기 (design-id="4")
     * - 요구사항: 반드시 "이해했습니다" 버튼을 눌러야 닫힘(바깥 클릭으로 닫지 않음)
     */
    showPackageGuideModal() {
        const modal = document.getElementById('packageGuideModal');
        if (!modal) {
            console.warn('packageGuideModal not found');
            return;
        }
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');

        const confirmBtn = document.getElementById('btnConfirmPackageGuide');
        if (confirmBtn && !confirmBtn._packageGuideBound) {
            confirmBtn.addEventListener('click', () => this.hidePackageGuideModal());
            confirmBtn._packageGuideBound = true;
        }
    }

    /**
     * 패키지 영역 안내 팝업 닫기
     */
    hidePackageGuideModal() {
        const modal = document.getElementById('packageGuideModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * 공통 오류 안내 팝업 열기
     * - 요구사항: 반드시 "확인" 버튼을 눌러야 닫힘(바깥 클릭으로 닫지 않음)
     */
    showGlobalErrorModal(message = '') {
        const modal = document.getElementById('globalErrorModal');
        if (!modal) {
            return false;
        }

        const textEl = document.getElementById('globalErrorText');
        if (textEl) {
            textEl.textContent = String(message || '').trim();
        }

        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');

        const confirmBtn = document.getElementById('btnConfirmGlobalError');
        if (confirmBtn && !confirmBtn._globalErrorBound) {
            confirmBtn.addEventListener('click', () => this.hideGlobalErrorModal());
            confirmBtn._globalErrorBound = true;
        }

        return true;
    }

    /**
     * 공통 오류 안내 팝업 닫기
     */
    hideGlobalErrorModal() {
        const modal = document.getElementById('globalErrorModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * 디자인 검수 안내 팝업 닫기
     */
    hideDesignCheckModal() {
        const modal = document.getElementById('designCheckModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * ✅ 커스텀 방법 안내 팝업 열기
     */
    showCustomGuideModal() {
        const modal = document.getElementById('customGuideModal');
        if (!modal) {
            return;
        }

        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');

        const closeBtn = document.getElementById('btnCloseCustomGuide');
        if (closeBtn && !closeBtn._customGuideBound) {
            closeBtn.addEventListener('click', () => this.hideCustomGuideModal());
            closeBtn._customGuideBound = true;
        }

        const backdrop = modal.querySelector('.design-check-backdrop');
        if (backdrop && !backdrop._customGuideBound) {
            backdrop.addEventListener('click', () => this.hideCustomGuideModal());
            backdrop._customGuideBound = true;
        }
    }

    /**
     * ✅ 커스텀 방법 안내 팝업 닫기
     */
    hideCustomGuideModal() {
        const modal = document.getElementById('customGuideModal');
        if (!modal) {
            return;
        }
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    /**
     * 加载商品背景图片
     */
    async loadProductBackground() {
        const backgroundImg = document.getElementById('backgroundImage');
        if (backgroundImg && backgroundImg.src) {
            backgroundImg.style.display = 'block';
            this.selectedColorImage = backgroundImg.src;
            this.currentBackgroundSrc = backgroundImg.src;
            console.log('使用模板提供的商品背景图片:', backgroundImg.src);
        } else {
            console.warn('未检测到商品背景图片，请检查模板配置');
        }
    }

    /**
     * 加载图案库
     */
    loadPatternLibrary() {
        try {
            const patternsDataElement = document.getElementById('patternsData');
            if (patternsDataElement && patternsDataElement.textContent.trim()) {
                const patternsData = JSON.parse(patternsDataElement.textContent);
                
                if (patternsData && Array.isArray(patternsData) && patternsData.length > 0) {
                    console.log('图案库数据已加载，图案数量:', patternsData.length);
                    this.patternsData = patternsData;
                } else {
                    console.log('图案库为空');
                    this.patternsData = [];
                }
            } else {
                console.log('未找到图案库数据');
                this.patternsData = [];
            }
        } catch (error) {
            console.error('图案库加载失败:', error);
            this.patternsData = [];
        }
    }

    /**
     * 渲染图案库
     */
    renderPatterns(patterns) {
        const patternGrid = document.getElementById('patternGrid');
        if (!patternGrid) {
            console.error('未找到图案网格容器');
            return;
        }

        // 清空现有图案
        patternGrid.innerHTML = '';

        if (!patterns || patterns.length === 0) {
            // 显示无图案提示
            patternGrid.innerHTML = `
                <div class="no-patterns-message">
                    <div class="no-patterns-icon">🎨</div>
                    <div class="no-patterns-text">사용 가능한 패턴이 없습니다</div>
                    <div class="no-patterns-hint">관리자에게 패턴 추가를 요청하세요</div>
                </div>
            `;
            return;
        }

        // 渲染图案项
        patterns.forEach(pattern => {
            const patternItem = document.createElement('div');
            patternItem.className = 'pattern-item';
            patternItem.dataset.patternId = pattern.id;
            
            patternItem.innerHTML = `
                <img src="${pattern.image_url}" alt="${pattern.name}" loading="lazy">
                <div class="pattern-name">${pattern.name}</div>
                ${pattern.category ? `<div class="pattern-category">${pattern.category}</div>` : ''}
            `;

            // 点击图案添加到画布
            patternItem.addEventListener('click', () => {
                this.addPatternToCanvas(pattern);
            });

            patternGrid.appendChild(patternItem);
        });

        console.log('图案库已渲染，图案数量:', patterns.length);
    }

    /**
     * 添加图案到画布
     */
    addPatternToCanvas(pattern) {
        if (!pattern || !pattern.image_url) {
            console.error('无效的图案数据');
            return;
        }

        console.log('添加图案到画布:', pattern.name);

        fabric.Image.fromURL(pattern.image_url, (img) => {
            // 调整图案大小以适应画布
            const scale = Math.min(300 / img.width, 300 / img.height);
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

            // 应用定制区域裁剪
            this.applyClipPathIfAvailable(img);
            // ✅ 默认放入“面积最大的裁剪框”内
            this.placeObjectInsideLargestClipRegion(img);

            this.canvas.add(img);
            this.canvas.setActiveObject(img);
            this.canvas.renderAll();
            
            // 关闭图案库弹窗
            this.closeModal(document.getElementById('patternModal'));
            
            console.log('图案已添加到画布:', pattern.name);
        }, {
            crossOrigin: 'anonymous'  // 处理跨域图片
        });
    }

    /**
     * 选择颜色
     */
    selectColor(color, imageSrc) {
        this.selectedColor = color;
        if (imageSrc) {
            this.selectedColorImage = imageSrc;
            this.updateBackgroundImage(imageSrc).catch((err) => {
                console.warn('更新背景图片失败', err);
            });
        }
        const colorItems = document.querySelectorAll('.color-item');
        colorItems.forEach(item => item.classList.remove('active'));
        const targetItem = document.querySelector(`[data-color="${color}"]`);
        if (targetItem) {
            targetItem.classList.add('active');
        }
        console.log('选择颜色:', color, this.selectedColorImage || '');
    }

    async updateBackgroundImage(imageSrc, options = {}) {
        if (!imageSrc) {
            return;
        }

        const backgroundImgEl = document.getElementById('backgroundImage');
        if (!options.skipDomUpdate && backgroundImgEl) {
            backgroundImgEl.src = imageSrc;
        }
        this.currentBackgroundSrc = imageSrc;
        return Promise.resolve();
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
     * 显示文字编辑器（新增文字）
     */
    showTextEditor() {
        // 新增模式，清空当前编辑对象
        this.editingTextObject = null;

        const modal = document.getElementById('textModal');
        if (modal) {
            // ✅ 기본 글꼴 크기 초기화
            const sizeInput = document.getElementById('textSize');
            if (sizeInput) {
                sizeInput.value = 50;
            }
            // ✅ 기본 색상으로 초기화 + UI 동기화
            this.syncTextColorUi('#000000');
            if (this._textPickr) {
                try { this._textPickr.setColor && this._textPickr.setColor('#000000', true); } catch (e) {}
            }
            modal.style.display = 'block';
        } else {
            console.error('文本编辑弹窗未找到: textModal');
        }
    }

    /**
     * 进入已有文字编辑模式
     */
    startEditText(textObject) {
        if (!textObject) return;

        this.editingTextObject = textObject;

        const modal = document.getElementById('textModal');
        if (!modal) {
            console.error('文本编辑弹窗未找到: textModal');
            return;
        }

        const textInput = document.getElementById('textContent');
        const sizeInput = document.getElementById('textSize');
        const colorInput = document.getElementById('textColor');
        const fontSelect = document.getElementById('textFont');

        if (textInput) textInput.value = textObject.text || '';
        if (sizeInput) sizeInput.value = parseInt(textObject.fontSize || 50, 10);
        if (colorInput) colorInput.value = textObject.fill || '#000000';
        // ✅ Pickr/버튼 UI 동기화
        this.syncTextColorUi((textObject && textObject.fill) ? textObject.fill : '#000000');
        if (this._textPickr) {
            try {
                const c = (textObject && textObject.fill) ? textObject.fill : '#000000';
                this._textPickr.setColor && this._textPickr.setColor(c, true);
            } catch (e) {}
        }
        if (fontSelect && textObject.fontFamily) {
            fontSelect.value = textObject.fontFamily;
            if (typeof this.applyFontSelection === 'function') {
                this.applyFontSelection(textObject.fontFamily, { skipMenuClose: true });
            }
        }

        modal.style.display = 'block';
    }

    /**
     * 显示图案库
     */
    showPatternLibrary() {
        const modal = document.getElementById('patternModal');
        if (modal) {
            // 渲染图案库（如果还未渲染）
            if (this.patternsData) {
                this.renderPatterns(this.patternsData);
            }
            modal.style.display = 'block';
        } else {
            console.error('图案库弹窗未找到: patternModal');
        }
    }

    /**
     * 关闭弹窗
     */
    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }

        // ✅ textModal 닫을 때 색상 확인/취소 상태도 초기화
        try {
            if (modal && modal.id === 'textModal') {
                // Pickr 열려있으면 닫기 + 현재값으로 동기화
                const input = document.getElementById('textColor');
                if (this._textPickr) {
                    try { this._textPickr.hide && this._textPickr.hide(); } catch (e) {}
                    try {
                        const current = (input && input.value) ? input.value : '#000000';
                        this._textPickr.setColor && this._textPickr.setColor(current, true);
                    } catch (e) {}
                }
                if (input) this.syncTextColorUi(input.value || '#000000');
            }
        } catch (e) {
            // ignore
        }
    }

    /**
     * 添加文字到画布
     */
    addTextToCanvas(text, fontFamily = 'Ongleaff Park Dahyeon', fontSize = 50, color = '#000000') {
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

        // 应用定制区域裁剪
        this.applyClipPathIfAvailable(textObject);
        // ✅ 默认放入“面积最大的裁剪框”内
        this.placeObjectInsideLargestClipRegion(textObject);

        this.canvas.add(textObject);
        this.canvas.setActiveObject(textObject);
        this.canvas.renderAll();
        console.log('添加文字到画布:', text);
    }

    preloadCustomFonts() {
        if (!Array.isArray(this.customFontFamilies)) {
            return;
        }
        this.customFontFamilies.forEach((font) => {
            this.ensureFontLoaded(font);
        });
    }

    async ensureFontLoaded(fontFamily) {
        if (!fontFamily) {
            return;
        }
        if (!this.loadedFontMap) {
            this.loadedFontMap = {};
        }
        if (this.loadedFontMap[fontFamily]) {
            return;
        }
        if (typeof document === 'undefined' || !document.fonts || typeof document.fonts.load !== 'function') {
            this.loadedFontMap[fontFamily] = true;
            return;
        }
        try {
            await document.fonts.load(`16px "${fontFamily}"`);
            this.loadedFontMap[fontFamily] = true;
        } catch (error) {
            console.warn('字体加载失败', fontFamily, error);
        }
    }

    /**
     * 处理图片上传
     */
    async handleImageUpload(file) {
        if (!file) {
            return;
        }

        if (!this.isAllowedImageFile(file)) {
            this.showImageUploadWarning('PNG/JPG 형식의 이미지만 업로드 가능합니다.');
            return;
        }

        try {
            const dimensions = await this.getImageDimensions(file);
            if (dimensions) {
                const { width, height } = dimensions;
                if (width > this.maxImageDimension || height > this.maxImageDimension) {
                    const detail = `현재 이미지: ${width}px × ${height}px`;
                    this.showImageUploadWarning('3,000 픽셀 미만의 이미지를 사용해주세요.', detail);
                    return;
                }
            }
        } catch (error) {
            console.warn('无法获取图片尺寸，将跳过尺寸校验', error);
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const imageDataUrl = e.target.result;
                
                if (!this.customerPatternImage) {
                    this.customerPatternImage = imageDataUrl;
                    console.log('✅ 客户主图案已记录');
                }
                
                this.customerPatternImages.push(imageDataUrl);
                console.log('✅ 客户图案已添加到多图列表，当前总数:', this.customerPatternImages.length);
                
                this.uploadedImages.push({
                    dataUrl: imageDataUrl,
                    fileName: file.name,
                    uploadTime: new Date().toISOString()
                });
                
                const fileExtension = this.getFileExtension(file.name);
                
                if (fileExtension === 'svg') {
                    this.handleSVGUpload(imageDataUrl);
                } else {
                    this.handleRegularImageUpload(imageDataUrl);
                }
                resolve();
            };
            reader.onerror = (error) => {
                reject(error);
            };
            reader.readAsDataURL(file);
        });
    }

    getFileExtension(fileName = '') {
        if (!fileName || fileName.indexOf('.') === -1) {
            return '';
        }
        return fileName.split('.').pop().toLowerCase();
    }

    isAllowedImageFile(file) {
        if (!file) {
            return false;
        }
        const extension = this.getFileExtension(file.name);
        const mime = (file.type || '').toLowerCase();
        const hasAllowedExtension = this.allowedImageExtensions.includes(extension);
        const hasAllowedMime = !mime || this.allowedImageMimeTypes.includes(mime);
        return hasAllowedExtension && hasAllowedMime;
    }

    getImageDimensions(file) {
        return new Promise((resolve, reject) => {
            try {
                const img = new Image();
                const objectUrl = URL.createObjectURL(file);
                img.onload = () => {
                    const width = img.naturalWidth || img.width || 0;
                    const height = img.naturalHeight || img.height || 0;
                    URL.revokeObjectURL(objectUrl);
                    resolve({ width, height });
                };
                img.onerror = (error) => {
                    URL.revokeObjectURL(objectUrl);
                    reject(error);
                };
                img.src = objectUrl;
            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * 处理SVG文件上传
     * @param {string} svgDataURL SVG数据URL
     */
    handleSVGUpload(svgDataURL) {
        console.log('开始处理SVG上传...');
        
        // 解析SVG内容
        const svgContent = svgDataURL.split(',')[1] ? atob(svgDataURL.split(',')[1]) : svgDataURL;
        console.log('SVG内容长度:', svgContent.length);
        
        // 创建临时SVG元素来获取尺寸
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = svgContent;
        const svgElement = tempDiv.querySelector('svg');
        
        if (!svgElement) {
            console.error('无效的SVG文件');
            alert('SVG 파일을 읽을 수 없습니다');
            return;
        }

        // 获取SVG的原始尺寸 - 更精确的方法
        let svgWidth, svgHeight;
        
        // 方法1: 从width和height属性获取
        const widthAttr = svgElement.getAttribute('width');
        const heightAttr = svgElement.getAttribute('height');
        
        if (widthAttr && heightAttr) {
            svgWidth = parseFloat(widthAttr);
            svgHeight = parseFloat(heightAttr);
            console.log('从width/height属性获取尺寸:', svgWidth, 'x', svgHeight);
        }
        
        // 方法2: 从viewBox获取
        if (!svgWidth || !svgHeight) {
            const viewBox = svgElement.getAttribute('viewBox');
            if (viewBox) {
                const viewBoxValues = viewBox.split(/\s+|,/);
                if (viewBoxValues.length >= 4) {
                    svgWidth = parseFloat(viewBoxValues[2]);
                    svgHeight = parseFloat(viewBoxValues[3]);
                    console.log('从viewBox获取尺寸:', svgWidth, 'x', svgHeight);
                }
            }
        }
        
        // 方法3: 使用getBBox()获取实际尺寸
        if (!svgWidth || !svgHeight) {
            try {
                const bbox = svgElement.getBBox();
                svgWidth = bbox.width;
                svgHeight = bbox.height;
                console.log('从getBBox获取尺寸:', svgWidth, 'x', svgHeight);
            } catch (e) {
                console.log('getBBox失败，使用默认尺寸');
                svgWidth = 200;
                svgHeight = 200;
            }
        }

        console.log('最终SVG尺寸:', svgWidth, 'x', svgHeight);

        // 尝试多种方法加载SVG
        this.loadSVGWithMultipleMethods(svgDataURL, svgWidth, svgHeight, svgElement);
    }

    /**
     * 使用Canvas方法加载SVG
     * @param {string} svgDataURL SVG数据URL
     * @param {number} svgWidth SVG宽度
     * @param {number} svgHeight SVG高度
     */
    loadSVGWithCanvas(svgDataURL, svgWidth, svgHeight) {
        // 创建Image对象来加载SVG
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = () => {
            console.log('SVG Image加载成功，实际尺寸:', img.naturalWidth, 'x', img.naturalHeight);
            
            // 使用实际加载的尺寸
            const actualWidth = img.naturalWidth || svgWidth;
            const actualHeight = img.naturalHeight || svgHeight;
            
            // 调整图片大小以适应画布（最大200px）
            const maxSize = 200;
            const scale = Math.min(maxSize / actualWidth, maxSize / actualHeight);
            
            console.log('SVG缩放比例:', scale, '实际尺寸:', actualWidth, 'x', actualHeight);

            // 创建fabric.Image对象
            fabric.Image.fromURL(svgDataURL, (fabricImg) => {
                if (!fabricImg) {
                    console.error('Fabric SVG 이미지 생성 실패');
                    alert('SVG 이미지를 생성할 수 없습니다');
                    return;
                }

                console.log('Fabric SVG对象创建成功，尺寸:', fabricImg.width, 'x', fabricImg.height);

                // 设置图片属性 - 使用更精确的设置
                fabricImg.set({
                    left: (this.canvas.width - actualWidth * scale) / 2,
                    top: (this.canvas.height - actualHeight * scale) / 2,
                    scaleX: scale,
                    scaleY: scale,
                    selectable: true,
                    hasControls: true,
                    hasBorders: true,
                    hasRotatingPoint: true,
                    // SVG特殊属性
                    crossOrigin: 'anonymous',
                    objectCaching: false,  // 禁用缓存
                    dirty: true,          // 标记为脏，强制重绘
                    // 强制设置尺寸
                    width: actualWidth,
                    height: actualHeight
                });

                // 应用定制区域裁剪 + 默认放入最大裁剪框
                this.applyClipPathIfAvailable(fabricImg);
                this.placeObjectInsideLargestClipRegion(fabricImg);

                // 添加到画布
                this.canvas.add(fabricImg);
                this.canvas.setActiveObject(fabricImg);
                this.canvas.renderAll();
                
                console.log('SVG 이미지가 캔버스에 추가됨，最终位置:', fabricImg.left, fabricImg.top);
                console.log('SVG 이미지最终尺寸:', fabricImg.width * fabricImg.scaleX, 'x', fabricImg.height * fabricImg.scaleY);
            }, {
                crossOrigin: 'anonymous'
            });
        };
        
        img.onerror = (error) => {
            console.error('SVG Image加载失败:', error);
            alert('SVG 이미지를 로딩할 수 없습니다');
        };
        
        // 开始加载
        img.src = svgDataURL;
    }

    /**
     * 使用多种方法尝试加载SVG
     * @param {string} svgDataURL SVG数据URL
     * @param {number} svgWidth SVG宽度
     * @param {number} svgHeight SVG高度
     * @param {Element} svgElement SVG元素
     */
    loadSVGWithMultipleMethods(svgDataURL, svgWidth, svgHeight, svgElement) {
        console.log('尝试多种方法加载SVG...');
        
        // 方法1: 使用fabric.loadSVGFromString (如果可用)
        if (typeof fabric.loadSVGFromString === 'function') {
            console.log('尝试使用fabric.loadSVGFromString...');
            const svgContent = svgDataURL.split(',')[1] ? atob(svgDataURL.split(',')[1]) : svgDataURL;
            
            fabric.loadSVGFromString(svgContent, (objects, options) => {
                if (objects && objects.length > 0) {
                    console.log('fabric.loadSVGFromString成功，对象数量:', objects.length);
                    
                    // 创建组
                    const group = new fabric.Group(objects, {
                        left: (this.canvas.width - svgWidth) / 2,
                        top: (this.canvas.height - svgHeight) / 2,
                        scaleX: Math.min(200 / svgWidth, 200 / svgHeight),
                        scaleY: Math.min(200 / svgWidth, 200 / svgHeight),
                        selectable: true,
                        hasControls: true,
                        hasBorders: true,
                        hasRotatingPoint: true
                    });

                    // 应用定制区域裁剪 + 默认放入最大裁剪框
                    this.applyClipPathIfAvailable(group);
                    this.placeObjectInsideLargestClipRegion(group);
                    
                    this.canvas.add(group);
                    this.canvas.setActiveObject(group);
                    this.canvas.renderAll();
                    console.log('SVG组已添加到画布');
                    return;
                }
            });
        }
        
        // 方法2: 使用Canvas转换
        this.loadSVGWithCanvasConversion(svgDataURL, svgWidth, svgHeight);
    }

    /**
     * 使用Canvas转换方法加载SVG
     * @param {string} svgDataURL SVG数据URL
     * @param {number} svgWidth SVG宽度
     * @param {number} svgHeight SVG高度
     */
    loadSVGWithCanvasConversion(svgDataURL, svgWidth, svgHeight) {
        console.log('使用Canvas转换方法...');
        
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = () => {
            console.log('SVG Image加载成功，浏览器渲染尺寸:', img.naturalWidth, 'x', img.naturalHeight);
            
            // 创建高分辨率Canvas来渲染SVG
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // 设置Canvas尺寸为SVG的2倍分辨率
            const scale = 2;
            canvas.width = svgWidth * scale;
            canvas.height = svgHeight * scale;
            
            // 设置高DPI渲染
            ctx.scale(scale, scale);
            
            // 渲染SVG到Canvas
            ctx.drawImage(img, 0, 0, svgWidth, svgHeight);
            
            // 转换为DataURL
            const canvasDataURL = canvas.toDataURL('image/png');
            
            console.log('SVG已转换为Canvas，尺寸:', canvas.width, 'x', canvas.height);
            
            // 使用转换后的图片创建fabric对象
            fabric.Image.fromURL(canvasDataURL, (fabricImg) => {
                if (!fabricImg) {
                    console.error('Canvas转换后的fabric对象创建失败');
                    this.loadSVGWithCanvas(svgDataURL, svgWidth, svgHeight);
                    return;
                }
                
                // 计算最终显示尺寸
                const maxSize = 200;
                const finalScale = Math.min(maxSize / svgWidth, maxSize / svgHeight);
                
                fabricImg.set({
                    left: (this.canvas.width - svgWidth * finalScale) / 2,
                    top: (this.canvas.height - svgHeight * finalScale) / 2,
                    scaleX: finalScale,
                    scaleY: finalScale,
                    selectable: true,
                    hasControls: true,
                    hasBorders: true,
                    hasRotatingPoint: true,
                    // 标记为SVG转换的图片
                    svgSource: svgDataURL
                });

                // 应用定制区域裁剪 + 默认放入最大裁剪框
                this.applyClipPathIfAvailable(fabricImg);
                this.placeObjectInsideLargestClipRegion(fabricImg);
                
                this.canvas.add(fabricImg);
                this.canvas.setActiveObject(fabricImg);
                this.canvas.renderAll();
                
                console.log('Canvas转换的SVG已添加到画布');
            });
        };
        
        img.onerror = (error) => {
            console.error('SVG Image加载失败，回退到原始方法:', error);
            this.loadSVGWithCanvas(svgDataURL, svgWidth, svgHeight);
        };
        
        img.src = svgDataURL;
    }

    /**
     * 处理普通图片文件上传
     * @param {string} imageDataURL 图片数据URL
     */
    handleRegularImageUpload(imageDataURL) {
        fabric.Image.fromURL(imageDataURL, (img) => {
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

            // 应用定制区域裁剪
            this.applyClipPathIfAvailable(img);
            // ✅ 默认放入“面积最大的裁剪框”内
            this.placeObjectInsideLargestClipRegion(img);

                this.canvas.add(img);
                this.canvas.setActiveObject(img);
                this.canvas.renderAll();
            console.log('일반 이미지가 캔버스에 추가됨');
            });
    }

    /**
     * 撤销操作
     */
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.restoreCanvasState(this.history[this.historyIndex]);
        } else {
            this.updateToolbarState();
        }
    }

    /**
     * 重做操作
     */
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.restoreCanvasState(this.history[this.historyIndex]);
        } else {
            this.updateToolbarState();
        }
    }

    /**
     * 添加历史记录
     */
    addToHistory() {
        // 为了避免历史快照过大/clipPath 反序列化不稳定：
        // - 不在历史里保存 clipOverlay（蓝色裁剪框）
        // - 不在历史里保存 clipPath（撤销/重做后统一重新应用）
        const state = this.cleanCanvasJSON(this.canvas.toJSON());
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(state);
        
        if (this.history.length > this.maxHistory) {
            this.history.shift();
            this.historyIndex = this.history.length - 1;
        } else {
            this.historyIndex++;
        }
        
        this.updateToolbarState();
    }

    /**
     * 恢复画布状态
     */
    restoreCanvasState(state) {
        // 先保存当前的裁剪框引用（loadFromJSON 会清空并重建 objects）
        const previousBorderShapes = Array.isArray(this.clipBorderShapes) ? this.clipBorderShapes : [];

        this.isRestoringHistory = true;
        this.canvas.loadFromJSON(state, () => {
            // 1) 重新绑定/恢复蓝色裁剪框（clipOverlay）
            const overlaysFromJson = (this.canvas.getObjects() || []).filter(
                (o) => o && o.customType === 'clipOverlay'
            );

            if (overlaysFromJson.length > 0) {
                this.clipBorderShapes = overlaysFromJson;
                overlaysFromJson.forEach((shape) => {
                    // 蓝色框永远不参与裁剪
                    shape.clipPath = null;
                    this.canvas.bringToFront(shape);
                });
            } else if (previousBorderShapes.length > 0) {
                // 历史快照中不包含裁剪框时，使用旧引用重新加回去
                previousBorderShapes.forEach((shape) => {
                    if (shape && shape.canvas !== this.canvas) {
                        this.canvas.add(shape);
                    }
                    if (shape) {
                        shape.clipPath = null;
                        this.canvas.bringToFront(shape);
                    }
                });
                this.clipBorderShapes = previousBorderShapes;
            }

            // 2) 统一重新应用裁剪（保证撤销到“应被裁剪”的状态时不会整张显示）
            this.applyClipPathForAllObjects();
            this.updateClipOverlayVisibility();

            // 3) 常规 UI 刷新
            this.canvas.discardActiveObject();
            this.refreshLayersPanel();
            this.updateLayerSelection();
            this.updateToolbarState();

            this.isRestoringHistory = false;
        });
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
     * 90° 회전
     */
    rotate90() {
        const activeObject = this.canvas.getActiveObject();
        if (!activeObject) {
            return;
        }

        // 多选（activeSelection）时，逐个对象旋转，保持选区不丢失
        if (activeObject.type === 'activeSelection' && typeof activeObject.forEachObject === 'function') {
            activeObject.forEachObject((obj) => {
                const currentAngle = (typeof obj.angle === 'number') ? obj.angle : 0;
                const nextAngle = (currentAngle + 90) % 360;
                obj.rotate(nextAngle);
                obj.setCoords();
            });
            // 重新计算选区坐标
            activeObject.setCoords();
        } else {
            const currentAngle = (typeof activeObject.angle === 'number') ? activeObject.angle : 0;
            const nextAngle = (currentAngle + 90) % 360;
            activeObject.rotate(nextAngle);
            activeObject.setCoords();
        }

        this.canvas.renderAll();
        // 程序性旋转不一定触发 object:modified，手动写入历史并刷新面板
        this.addToHistory();
        this.refreshLayersPanel();
        this.updateLayerSelection();
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
            this.canvas.sendBackwards(activeObject);
            this.canvas.renderAll();
            this.addToHistory();
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
        // ✅ 只要画布上有真正的内容对象（文字或图片）即可，不强制必须上传图案图片
        const objects = this.canvas.getObjects() || [];
        const hasContent = objects.some(o =>
            o !== this.canvas.backgroundImage &&
            o.customType !== 'clipOverlay' && // 排除蓝色裁剪框
            o.customType !== 'removeTag'      // 排除删除标记等辅助对象
        );

        if (!hasContent) {
            this.showErrorMessage('이미지 혹은 텍스트를 추가해주세요.');
            return false;
        }
        
        return true;
    }

    /**
     * 添加到购物车
     * ✅ 更新：生成合成图并传递给后端
     */
    async addToCart() {
        // 成功后即将跳转到购物车时，保持 Loading 不消失（直到页面跳转）
        let willRedirectToCart = false;

        this.showLoadingOverlay('장바구니에 추가 중입니다...');
        this.setEditorProcessingState(true);
        try {
            // ⭐ 在开始处理之前，先把当前正在编辑的设计状态保存到 designStates 中
            this.saveDesignState(this.currentDesignId || 'base');

            // ✅ Step 1: 针对 전체 디자인 中的所有设计生成合成图，并校验每套都已完成
            const backgroundImg = document.getElementById('backgroundImage');
            if (!backgroundImg) {
                console.error('背景图片元素 #backgroundImage 未找到');
                this.showErrorMessage('시스템 오류가 발생했습니다. 다시 시도해 주세요.');
            return;
        }

            // 保存当前设计状态，便于遍历后还原
            const originalDesignId = this.currentDesignId || 'base';
            if (this.selectedColorImage) {
                backgroundImg.src = this.selectedColorImage;
            }
            const originalBgSrc = backgroundImg.src;
            const originalCanvasState = this.canvas.toJSON();

            const dropdown = document.querySelector('.design-dropdown');
            const items = dropdown ? dropdown.querySelectorAll('.design-dropdown-item') : null;
            const designImages = [];

            if (!items || items.length === 0) {
                // 没有 전체 디자인 按钮时，退回到单一画布校验逻辑
                if (!this.checkCustomization()) {
                    return;
                }
                // 单一主图场景：确保启用裁剪并统一应用到所有对象
                this.clipEnabled = true;
                this.applyClipPathForAllObjects();

            const objects = this.canvas.getObjects();
                const svgContent = await this.generateCustomizedSVG(backgroundImg, objects);
                const singleImage = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgContent)));
                designImages.push(singleImage);
            } else {
                let hasEmptyDesign = false;
                let emptyDesignId = null;
                let emptyDesignLabel = null;

                for (const item of items) {
                    const designId = item.getAttribute('data-design-id') || 'base';
                    let imageSrc = item.getAttribute('data-image-src') || '';
                    if (designId === 'base' && this.selectedColorImage) {
                        imageSrc = this.selectedColorImage;
                    }

                    // 还原该设计的画布
                    this.currentDesignId = designId;
                    await this.restoreDesignState(designId);

                    // 根据 designId 决定是否启用裁剪（与前台切换逻辑保持一致）
                    this.clipEnabled = (designId !== '4');
                    this.applyClipPathForAllObjects();
                    this.updateClipOverlayVisibility();

                    if (imageSrc) {
                        backgroundImg.src = imageSrc;
                    }

                    const objects = this.canvas.getObjects() || [];
                    // 判断当前设计是否有真正的内容对象（排除背景图、蓝色裁剪框、辅助标记等）
                    const hasContent = objects.some(o =>
                        o !== this.canvas.backgroundImage &&
                        o.customType !== 'clipOverlay' && // 排除蓝色裁剪框
                        o.customType !== 'removeTag'      // 排除删除标记等辅助对象
                    );

                    // ✅ design-id="4"(패키지/包装) 允许为空：不作为“未完成”拦截条件
                    if (designId !== '4' && !hasContent) {
                        hasEmptyDesign = true;
                        emptyDesignId = designId;
                        emptyDesignLabel = (item.textContent || '').trim();
                        console.warn('设计未完成，designId =', designId);
                        break;
                    }

            const svgContent = await this.generateCustomizedSVG(backgroundImg, objects);
                    const dataUrl = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgContent)));
                    designImages.push(dataUrl);
                }

                // 遍历结束后，无论成功/失败都先还原原来的画布与背景
                try {
                    backgroundImg.src = originalBgSrc;
                    this.currentDesignId = originalDesignId;
                    this.canvas.clear();
                    this.canvas.loadFromJSON(originalCanvasState, () => {
                        this.canvas.renderAll();
                    });
                } catch (restoreError) {
                    console.warn('还原原始设计状态失败', restoreError);
                }

                if (hasEmptyDesign) {
                    // 至少有一套(非 패키지)设计为空，不允许加入购物车
                    this.showErrorMessage('모든 디자인을 완료한 후 장바구니에 담아 주세요.');
                    return;
                }
            }

            if (designImages.length === 0) {
                console.error('未生成任何设计合成图');
                this.showErrorMessage('모든 디자인을 완료한 후 장바구니에 담아 주세요.');
                return;
            }

            const primaryImageDataUrl = designImages[0] || null;
            console.log('合成图生成成功，设计数量:', designImages.length);
            
            // ✅ 获取多张客户图案
            const customerPatternImages = (this.customerPatternImages && this.customerPatternImages.length)
                ? this.customerPatternImages.slice()
                : (this.customerPatternImage ? [this.customerPatternImage] : []);
            
            console.log('✅ 客户图案数量:', customerPatternImages.length);
            
            // ✅ 将多图序列化为JSON存入数据库字段（方案A）
            const customerPatternImageDataUrl = customerPatternImages.length > 0
                ? JSON.stringify(customerPatternImages)  // 多图时存JSON数组
                : null;                                   // 无图案时为null
            
            console.log('✅ 客户图案数据类型:', customerPatternImages.length > 1 ? 'JSON数组' : '单图');
            
            // ✅ 构造要写入订单的精简版 customization_data：只保留追加选项相关信息
            let orderCustomizationData = {
                extraOptions: null,
                extra_price_info: null,
                extraoption: ''
            };

            // ✅ 获取追加选项选择并写入定制数据（用于后端查看与复盘）
            try {
                if (typeof window.getExtraOptionsPayload === 'function') {
                    const extraPayload = window.getExtraOptionsPayload() || {};
                    orderCustomizationData.extraOptions = extraPayload;
                }

                // 额外：把当前已选追加选项的显示文本写入 extraoption（来自 .extra-options-list）
                if (typeof window.getExtraOptionsSummaryText === 'function') {
                    const summary = window.getExtraOptionsSummaryText() || '';
                    if (summary && summary.trim()) {
                        orderCustomizationData.extraoption = summary.trim();
                    }
                }

                // ⭐ 把当前追加选项的“附加费用”概要写入 extra_price_info
                if (typeof window.getExtraOptionsPriceSummaryForRequest === 'function') {
                    const extraPriceInfo = window.getExtraOptionsPriceSummaryForRequest() || {};
                    orderCustomizationData.extra_price_info = extraPriceInfo;
                }
            } catch (e) {
                console.warn('获取追加选项数据失败，将继续提交其它定制数据', e);
            }

            // ⭐ 根据 extra_price_info 计算本次定制的附加费用总额
            let extraPriceTotal = 0;
            if (orderCustomizationData && orderCustomizationData.extra_price_info) {
                const info = orderCustomizationData.extra_price_info;
                const perUnit = parseInt(info.perUnitExtra || 0, 10) || 0;
                const perOrder = parseInt(info.perOrderExtra || 0, 10) || 0;
                const qty = this.quantity || 1;
                extraPriceTotal = (perUnit * qty) + perOrder;
            }

            // ✅ 保存当前画布完整 JSON，用于"편집하기"时在前端还原
            // 使用一开始记录的 originalCanvasState + designStates，避免在生成合成图过程中拿到被清空的画布
            const canvasState = this.cleanCanvasJSON(originalCanvasState);

            // 所有设计的画布状态（主图 + 附加图），按 designId 存一份
            let designStatesForStore = null;
            if (this.designStates && typeof this.designStates === 'object') {
                designStatesForStore = {};
                Object.keys(this.designStates).forEach((designId) => {
                    designStatesForStore[designId] = this.cleanCanvasJSON(this.designStates[designId]);
                });
            }
            
            // ✅ 获取官方选项 sno（如果用户选择了官方选项）
            let officialOptionSno = 0;
            if (typeof window.getSelectedOfficialOptionSno === 'function') {
                officialOptionSno = window.getSelectedOfficialOptionSno() || 0;
            }

            let officialOptionTexts = {};
            if (typeof window.getOfficialOptionTextPayload === 'function') {
                officialOptionTexts = window.getOfficialOptionTextPayload() || {};
            }
            
            // ✅ 构造完整的 customization_data（包含画布 JSON + 追加选项），用于数据库存储和「편집하기」还原
            const fullCustomizationData = {
                // 追加选项相关（用于后端查看与复盘）
                extraOptions: orderCustomizationData.extraOptions,
                extra_price_info: orderCustomizationData.extra_price_info,
                extraoption: orderCustomizationData.extraoption,
                // ✅ 完整画布状态，用于「편집하기」从数据库还原
                canvasJSON: canvasState,
                designStates: designStatesForStore,
                currentDesignId: this.currentDesignId || 'base',
                customerPatternImages: (customerPatternImages && customerPatternImages.length)
                    ? customerPatternImages.slice()
                    : [],
                // ⭐ 记录当前选择的颜色及其底图，供购物车 편집하기 还原
                selectedColor: this.selectedColor || null,
                selectedColorImage: this.selectedColorImage || null
            };

            // ✅ 构建请求数据（包含Base64图片）
            const requestData = {
                goods_no: this.getGoodsNo(),
                quantity: this.quantity,
                selected_color: this.selectedColor,
                selected_size: this.selectedSize,
                // ✅ 完整定制数据（追加选项 + 画布 JSON），用于数据库存储和「편집하기」还原
                customization_data: JSON.stringify(fullCustomizationData),
                // ✅ 多套设计合成图：按 화면上的 전체 디자인 按钮顺序生成的 Base64 数组
                customization_image: JSON.stringify(designImages),
                // ✅ 客户图案（JSON数组或null），保持现有格式
                customer_pattern_image: customerPatternImageDataUrl,
                // ⭐ 新增：本行购物车的"附加费用总额"
                extra_price_total: extraPriceTotal,
                // ⭐ 新增：官方选项 sno（用于购物车显示选项信息和结算）
                option_sno: officialOptionSno,
                option_texts: officialOptionTexts
            };

            // ✅ 新增：如果 URL 中有 cartSno 参数，则传给后端进行更新而不是新建
            const urlParams = new URLSearchParams(window.location.search);
            const existingCartSno = urlParams.get('cartSno');
            if (existingCartSno) {
                requestData.cart_sno = existingCartSno;
                console.log('✅ 编辑模式：将更新现有购物车项 cartSno=' + existingCartSno);
            }

            console.log('장바구니 추가 요청 데이터:', {
                ...requestData,
                customization_image: '(multi design images)',
                customer_pattern_image: '(pattern images)'
            });

            // ⭐ 在整单维度累积 customization_data，供订单完成页写入 custom_order_extension 使用
            try {
                const payloadForOrder = {
                    goodsNo: this.getGoodsNo(),
                    quantity: this.quantity,
                    selected_color: this.selectedColor,
                    selected_size: this.selectedSize,
                    // ✅ 使用完整定制数据（与数据库保持一致）
                    customization_data: fullCustomizationData,
                    extra_price_total: extraPriceTotal
                };

                const existingRaw = localStorage.getItem('customOrderCustomizationData');
                let list = [];
                if (existingRaw) {
                    try {
                        const parsed = JSON.parse(existingRaw);
                        if (Array.isArray(parsed)) {
                            list = parsed;
                        }
                    } catch (e) {
                        console.warn('解析已有 customOrderCustomizationData 失败，将重建数组', e);
                    }
                }
                list.push(payloadForOrder);
                localStorage.setItem('customOrderCustomizationData', JSON.stringify(list));
            } catch (e) {
                console.warn('累积整单 customization_data 到 localStorage 失败，将不影响下单流程', e);
            }

            // ✅ 发送请求到控制器方法
            const response = await fetch('../custom/goods_custom.php?mode=addToCart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();

            if (result.success) {
                try {
                    // ⭐ 新增：把所有设计的合成图数组一起存入 localStorage（包含 base + 패키지）
                    if (designImages && designImages.length > 0) {
                        localStorage.setItem('customOrderCustomizationImages', JSON.stringify(designImages));
                    }

                    // 兼容旧逻辑：仍然用第一张作为购物车列表的主缩略图
                    if (primaryImageDataUrl) {
                        // 使用第一套设计的合成图作为购物车列表的主缩略图
                        localStorage.setItem('customOrderCustomizationImage', primaryImageDataUrl);
                    }
                    // ✅ 保存 / 清理 图案数据：以本次定制为准
                    if (customerPatternImages.length > 0) {
                        // 有图案 → 覆盖为当前图案
                        localStorage.setItem('customOrderCustomerPatternImage', customerPatternImages[0]);
                        localStorage.setItem('customOrderCustomerPatternImages', JSON.stringify(customerPatternImages));
                    } else {
                        // 无图案 → 清理之前遗留的图案数据，避免误显示
                        localStorage.removeItem('customOrderCustomerPatternImage');
                        localStorage.removeItem('customOrderCustomerPatternImages');
                    }
                    // ✅ 额外保存：完整画布 JSON 和商品编号，用于"편집하기"时还原
                    localStorage.setItem('customOrderCanvasJSON', JSON.stringify(canvasState));
                    localStorage.setItem('customOrderGoodsNo', String(this.getGoodsNo()));

                    // ✅ 针对每个购物车项，使用 cartSno 生成专用本地缓存 key（custom_design_{cartSno}），便于单独还原
                    if (result.cart_sno) {
                        try {
                            const perCartKey = 'custom_design_' + result.cart_sno;
                            const perCartPayload = {
                                goodsNo: this.getGoodsNo(),
                                selectedColor: this.selectedColor,
                                selectedSize: this.selectedSize,
                                quantity: this.quantity,
                                selectedColorImage: this.selectedColorImage || null,
                                // 当前激活设计的画布
                                canvasJSON: canvasState,
                                // ⭐ 所有设计的画布 JSON（主图 + 附加图）
                                designStates: designStatesForStore,
                                // 当前设计 ID
                                currentDesignId: this.currentDesignId || 'base',
                                // ⭐ 保存本次定制使用的原始上传图案数组，便于再次 편집하기 后重新加车时还能带上 업로드 이미지
                                customerPatternImages: (customerPatternImages && customerPatternImages.length)
                                    ? customerPatternImages.slice()
                                    : []
                            };
                            localStorage.setItem(perCartKey, JSON.stringify(perCartPayload));
                        } catch (perCartError) {
                            console.warn('保存 per-cart 本地定制缓存失败', perCartError);
                        }
                    }
                } catch (storageError) {
                    console.warn('本地存储保存失败', storageError);
                }

                // 后端 message 可能来自旧代码，这里强制使用统一文案
                this.showSuccessMessage('커스텀 상품이 장바구니에 추가되었습니다.');
                console.log('장바구니 추가 성공:', result);
                
                // 延迟跳转到购物车
                willRedirectToCart = true;
                this.showLoadingOverlay('장바구니로 이동 중입니다...');
                setTimeout(() => {
                    window.location.href = '../order/cart.php';
                }, 2000);
            } else {
                this.showErrorMessage(result.message || '장바구니 추가 실패');
                console.error('장바구니 추가 실패:', result);
            }
        } catch (error) {
            console.error('장바구니 추가 오류:', error);
            this.showErrorMessage('네트워크 오류가 발생했습니다. 다시 시도해 주세요.');
        } finally {
            // 실패/예외인 경우에만 Loading을 닫는다.
            // 성공 후에는 곧바로 cart.php로 이동하므로, 이동 전까지 Loading을 유지한다.
            if (!willRedirectToCart) {
                this.hideLoadingOverlay();
                this.setEditorProcessingState(false);
            }
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
     * 保存当前设计的画布状态
     */
    saveDesignState(designId) {
        if (!this.canvas || !designId) return;
        try {
            this.designStates[designId] = this.cleanCanvasJSON(this.canvas.toJSON());
        } catch (e) {
            console.warn('保存设计状态失败', designId, e);
        }
    }

    /**
     * 清理画布 JSON，删除辅助对象和冗余字段，避免存储过大
     */
    cleanCanvasJSON(state) {
        if (!state) return state;
        const cloned = JSON.parse(JSON.stringify(state));
        if (Array.isArray(cloned.objects)) {
            cloned.objects = cloned.objects
                .filter(obj => obj.customType !== 'clipOverlay')
                .map(obj => {
                    if (obj.clipPath) {
                        delete obj.clipPath;
                    }
                    if (obj.srcCache) {
                        delete obj.srcCache;
                    }
                    if (obj.cacheKey) {
                        delete obj.cacheKey;
                    }
                    return obj;
                });
        }
        return cloned;
    }

    /**
     * 还原指定设计的画布状态
     */
    async restoreDesignState(designId) {
        if (!this.canvas || !designId) return;

        const state = this.designStates[designId];
        
        // 先把当前的蓝色边框对象引用保存下来，避免被 clear 丢失
        const borderShapes = Array.isArray(this.clipBorderShapes) ? this.clipBorderShapes : [];

        this.isRestoringHistory = true;
        this.canvas.clear();

        const restoreOverlay = () => {
            if (borderShapes.length > 0) {
                borderShapes.forEach(shape => {
                    // 如果当前不在画布上，再重新添加
                    if (shape.canvas !== this.canvas) {
                        this.canvas.add(shape);
                    }
                    // 按当前 clipEnabled 显示/隐藏
                    shape.visible = this.clipEnabled;
                    // 保证在最上层
                    this.canvas.bringToFront(shape);
                });
            }
            this.canvas.renderAll();
        };

        if (state) {
            return new Promise((resolve) => {
                this.canvas.loadFromJSON(state, () => {
                    this.canvas.renderAll();
                    this.isRestoringHistory = false;
                    this.addToHistory();
                    this.updateToolbarState();
                    this.updateImageControls(null);
                    
                    // 重要：还原完画布后，先把蓝色框重新加回去，再根据当前配置统一应用裁剪
                    restoreOverlay();
                    this.applyClipPathForAllObjects();
                    this.updateClipOverlayVisibility();
                    resolve();
                });
            });
        } else {
            this.canvas.renderAll();
            this.isRestoringHistory = false;
            this.addToHistory();
            this.updateToolbarState();
            this.updateImageControls(null);
            
            // 没有保存状态时，同样要把蓝色框加回去并应用裁剪
            restoreOverlay();
            this.applyClipPathForAllObjects();
            this.updateClipOverlayVisibility();
        }
    }

    /**
     * 切换 전체 디자인（商品图）并还原对应画布
     */
    async switchDesign(designId, imageSrc) {
        if (!designId || !imageSrc) return;

        const bg = document.getElementById('backgroundImage');

        // 同一设计且图片未变时不重复处理
        if (designId === this.currentDesignId && bg && bg.src === imageSrc) {
            return;
        }

        // 先保存当前设计的状态
        this.saveDesignState(this.currentDesignId);

        // 切换商品背景图
        if (bg) {
            bg.src = imageSrc;
        }

        // 更新当前设计 ID 并还原新设计的画布
        this.currentDesignId = designId;
        await this.restoreDesignState(designId);

        // 根据 designId 决定是否启用裁剪：패키지(data-design-id="4") 不启用
        this.clipEnabled = (designId !== '4');

        // 统一为当前画布对象应用/移除裁剪
        this.applyClipPathForAllObjects();

        // 切换设计后，根据当前内容刷新蓝色裁剪框显隐
        this.updateClipOverlayVisibility();
    }

    /**
     * 获取CSRF令牌
     */
    getCsrfToken() {
        const pageConfig = JSON.parse(document.getElementById('pageConfig').textContent);
        return pageConfig.csrf_token || '';
    }

    /**
     * 测试画布状态
     */
    testCanvasState() {
        const objects = this.canvas.getObjects();
        console.log('=== 画布状态检查 ===');
        console.log('画布尺寸:', this.canvas.width, 'x', this.canvas.height);
        console.log('画布显示尺寸:', this.canvas.upperCanvasEl.style.width, 'x', this.canvas.upperCanvasEl.style.height);
        console.log('对象数量:', objects.length);
        console.log('设备像素比:', window.devicePixelRatio);
        
        objects.forEach((obj, index) => {
            console.log(`对象 ${index + 1}:`, {
                type: obj.type,
                left: obj.left,
                top: obj.top,
                width: obj.width,
                height: obj.height,
                scaleX: obj.scaleX,
                scaleY: obj.scaleY,
                angle: obj.angle,
                visible: obj.visible,
                src: obj.src || (obj.getElement && obj.getElement().src) || 'unknown'
            });
        });
        
        console.log('=== 画布状态检查结束 ===');
    }

    /**
     * 简单SVG测试
     */
    simpleSVGTest() {
        console.log('=== 简单SVG测试开始 ===');
        
        const objects = this.canvas.getObjects();
        console.log('画布对象数量:', objects.length);
        
        if (objects.length === 0) {
            console.log('❌ 没有对象，请先上传图案');
            return;
        }
        
        // 创建最简单的SVG
        let svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="860" height="860" viewBox="0 0 860 860">`;
        
        // 添加背景
        svgContent += `<rect x="0" y="0" width="860" height="860" fill="#f0f0f0" stroke="#000" stroke-width="2"/>`;
        
        // 添加所有对象
        objects.forEach((obj, index) => {
            console.log(`处理对象 ${index + 1}:`, obj.type);
            
            if (obj.type === 'image') {
                // 简单处理图片对象
                const x = (obj.left || 0) / (window.devicePixelRatio || 1);
                const y = (obj.top || 0) / (window.devicePixelRatio || 1);
                const width = (obj.width || 100) / (window.devicePixelRatio || 1);
                const height = (obj.height || 100) / (window.devicePixelRatio || 1);
                
                console.log(`图片位置: x=${x}, y=${y}, w=${width}, h=${height}`);
                
                // 添加一个简单的矩形作为占位符
                svgContent += `<rect x="${x}" y="${y}" width="${width}" height="${height}" fill="red" stroke="blue" stroke-width="2"/>`;
                svgContent += `<text x="${x + width/2}" y="${y + height/2}" text-anchor="middle" fill="white">IMG</text>`;
            } else if (obj.type === 'text') {
                // 简单处理文字对象
                const x = (obj.left || 0) / (window.devicePixelRatio || 1);
                const y = (obj.top || 0) / (window.devicePixelRatio || 1);
                const text = obj.text || 'TEXT';
                
                console.log(`文字位置: x=${x}, y=${y}, text=${text}`);
                
                svgContent += `<text x="${x}" y="${y}" font-family="Arial" font-size="20" fill="black">${text}</text>`;
            }
        });
        
        svgContent += '</svg>';
        
        console.log('生成的简单SVG:', svgContent);
        
        // 下载测试SVG
        this.downloadSVGFile(svgContent, 'simple-test.svg');
        
        console.log('=== 简单SVG测试结束 ===');
    }

    /**
     * 显示错误消息
     */
    showErrorMessage(message) {
        // 优先使用弹窗式错误提示（更显眼，且不会 3 秒自动消失）
        if (this.showGlobalErrorModal && this.showGlobalErrorModal(message)) {
            return;
        }

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
        if (successDiv) {
            successDiv.style.display = 'none';
        }
    }

    /**
     * 显示全局加载弹窗
     */
    showLoadingOverlay(message = '처리 중입니다...') {
        if (this.loadingOverlay) {
            const textEl = this.loadingOverlay.querySelector('.custom-loading-text');
            if (textEl) {
                textEl.textContent = message;
            }
            return;
        }

        const overlay = document.createElement('div');
        overlay.className = 'custom-loading-overlay';
        overlay.innerHTML = `
            <div class="custom-loading-box">
                <div class="custom-loading-spinner"></div>
                <div class="custom-loading-text">${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
        this.loadingOverlay = overlay;
    }

    /**
     * 隐藏全局加载弹窗
     */
    hideLoadingOverlay() {
        if (!this.loadingOverlay) {
            return;
        }
        try {
            this.loadingOverlay.remove();
        } catch (e) {
            console.warn('移除加载弹窗失败', e);
        }
        this.loadingOverlay = null;
    }

    /**
     * 控制全局 processing 状态（用于隐藏画布，避免合成过程曝光）
     */
    setEditorProcessingState(isProcessing) {
        const root = document.body;
        if (!root) {
            return;
        }

        if (isProcessing) {
            root.classList.add('custom-editor-processing');
        } else {
            root.classList.remove('custom-editor-processing');
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

    /**
     * 更新工具栏按钮状态
     * 根据选择状态启用/禁用相关按钮
     */
    updateToolbarState() {
        const activeObject = this.canvas.getActiveObject();
        const hasSelection = activeObject !== null;
        
        // 需要选中对象才能操作的按钮列表
        const selectionRequiredButtons = [
            'btnDelete',
            'btnFlipH',
            'btnFlipV',
            'btnRotate90',
            'btnBringForward',
            'btnSendBackward',
            'btnBringToFront',
            'btnSendToBack'
        ];
        
        // 根据选择状态启用/禁用按钮
        selectionRequiredButtons.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                if (hasSelection) {
                    btn.classList.remove('disabled');
                    btn.disabled = false;
                } else {
                    btn.classList.add('disabled');
                    btn.disabled = true;
                }
            }
        });
        
        // 组合按钮：需要多选对象
        const btnGroup = document.getElementById('btnGroup');
        if (btnGroup) {
            if (activeObject && activeObject.type === 'activeSelection') {
                btnGroup.classList.remove('disabled');
                btnGroup.disabled = false;
            } else {
                btnGroup.classList.add('disabled');
                btnGroup.disabled = true;
            }
        }
        
        // 解组按钮：需要选中组对象
        const btnUngroup = document.getElementById('btnUngroup');
        if (btnUngroup) {
            if (activeObject && activeObject.type === 'group') {
                btnUngroup.classList.remove('disabled');
                btnUngroup.disabled = false;
            } else {
                btnUngroup.classList.add('disabled');
                btnUngroup.disabled = true;
            }
        }
        
        const btnUndo = document.getElementById('btnUndo');
        if (btnUndo) {
            if (this.historyIndex > 0) {
                btnUndo.classList.remove('disabled');
                btnUndo.disabled = false;
            } else {
                btnUndo.classList.add('disabled');
                btnUndo.disabled = true;
            }
        }
        
        const btnRedo = document.getElementById('btnRedo');
        if (btnRedo) {
            if (this.historyIndex < this.history.length - 1) {
                btnRedo.classList.remove('disabled');
                btnRedo.disabled = false;
            } else {
                btnRedo.classList.add('disabled');
                btnRedo.disabled = true;
            }
        }
        
        console.log('工具栏状态已更新，选中对象:', hasSelection);
    }

    /**
     * 下载定制后的SVG图片
     * 将商品背景图与用户添加的图案/文字合成后下载
     */
    async downloadCustomizedSVG() {
        try {
            console.log('开始生成定制SVG...');
            
            // 先检查画布状态
            this.testCanvasState();

            // 显示加载提示
            this.showDownloadProgress('SVG 생성 중...');

            // 创建SVG内容
            const svgContent = await this.generateCustomizedSVG();
            
            // 下载SVG文件
            this.downloadSVGFile(svgContent, 'customized-product.svg');
            
            console.log('SVG 다운로드 완료');
            
        } catch (error) {
            console.error('SVG 생성 오류:', error);
            alert('SVG 생성 중 오류가 발생했습니다: ' + error.message);
        }
    }

    /**
     * 生成定制SVG内容
     * @param {HTMLImageElement} backgroundImg 背景图片
     * @param {Array} objects 画布对象
     * @returns {Promise<string>} SVG内容
     */
    async generateCustomizedSVG() {
        const width = this.displayWidth || 860;
        const height = this.displayHeight || 860;

        const [backgroundMarkup, overlayDataURL] = await Promise.all([
            this.getBackgroundSVGMarkup(width, height),
            this.generateOverlayPNGDataURL()
        ]);

        let svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">`;

        if (backgroundMarkup) {
            svg += backgroundMarkup;
        }

        if (overlayDataURL) {
            svg += `<image x="0" y="0" width="${width}" height="${height}" xlink:href="${overlayDataURL}" preserveAspectRatio="none" />`;
        }

        svg += '</svg>';
        return svg;
    }

    async generateOverlayPNGDataURL() {
        if (!this.canvas) return '';

        const multiplier = this.exportMultiplier || 1;
        const canvas = this.canvas;

        // 没有实际内容（只有背景/蓝框）则不生成覆盖图
        const renderableObjects = canvas.getObjects().filter(o =>
            o !== canvas.backgroundImage &&
            o.customType !== 'clipOverlay' &&
            o.customType !== 'removeTag'
        );
        if (renderableObjects.length === 0) {
            return '';
        }

        // 记录当前背景和蓝色框的状态
        const originalBg = canvas.backgroundImage || null;
        const borderShapes = Array.isArray(this.clipBorderShapes) ? this.clipBorderShapes : [];
        const borderVisible = borderShapes.map(s => !!s.visible);

        // 1）隐藏背景图（主图 SVG 由 getBackgroundSVGMarkup 单独处理）
        if (originalBg) {
            await new Promise(resolve => {
                canvas.setBackgroundImage(null, () => {
                    canvas.renderAll();
                    resolve();
                });
            });
        }

        // 2）隐藏蓝色裁剪框（只用于提示，不参与合成）
        borderShapes.forEach(s => { s.visible = false; });
        canvas.renderAll();

        // 3）导出当前画布为 PNG（此时每个对象上的 clipPath 已经生效）
        const dataURL = canvas.toDataURL({
                            format: 'png',
                            multiplier,
                            enableRetinaScaling: false
                        });

        // 4）还原背景图
        if (originalBg) {
            await new Promise(resolve => {
                canvas.setBackgroundImage(originalBg, () => {
                    canvas.renderAll();
                    resolve();
                    });
            });
        }

        // 5）还原蓝色裁剪框可见性
        borderShapes.forEach((s, idx) => {
            s.visible = borderVisible[idx];
        });
        canvas.renderAll();

        return dataURL;
    }

    async getBackgroundSVGMarkup(width, height) {
        const backgroundImg = document.getElementById('backgroundImage');
        if (!backgroundImg) {
            return '';
        }

        const src = backgroundImg.getAttribute('src') || '';
        if (!src) {
            return '';
        }

        try {
            if (src.startsWith('data:image/svg')) {
                const svgText = this.extractSvgFromDataUrl(src);
                return this.prepareEmbeddedSVG(svgText, width, height);
            }

            if (/\.(svg)(\?.*)?$/i.test(src)) {
                const svgText = await this.fetchSvgText(src);
                return this.prepareEmbeddedSVG(svgText, width, height);
            }
        } catch (error) {
            console.warn('背景SVG加载失败，回退为位图:', error);
        }

        const fallback = this.imageToDataURL(backgroundImg);
        return `<image x="0" y="0" width="${width}" height="${height}" xlink:href="${fallback}" preserveAspectRatio="xMidYMid meet" />`;
    }

    async fetchSvgText(url) {
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) {
            throw new Error(`无法加载SVG: ${response.status} ${response.statusText}`);
        }
        return await response.text();
    }

    prepareEmbeddedSVG(svgText, width, height) {
        const cleaned = this.stripXmlDeclaration(svgText);
        if (!cleaned) {
            return '';
        }

                            const parser = new DOMParser();
        const svgDoc = parser.parseFromString(cleaned, 'image/svg+xml');
                            const svgElement = svgDoc.querySelector('svg');
                            
        if (!svgElement) {
            return `<g>${cleaned}</g>`;
        }

        let vbX = 0;
        let vbY = 0;
        let vbWidth = width;
        let vbHeight = height;

        const viewBoxAttr = svgElement.getAttribute('viewBox');
        if (viewBoxAttr) {
            const parts = viewBoxAttr.trim().split(/[\s,]+/).map(Number);
            if (parts.length === 4 && parts.every(isFinite)) {
                [vbX, vbY, vbWidth, vbHeight] = parts;
            }
        } else {
            const rawWidth = parseFloat(svgElement.getAttribute('width'));
            const rawHeight = parseFloat(svgElement.getAttribute('height'));

            if (isFinite(rawWidth) && rawWidth > 0) {
                vbWidth = rawWidth;
            }

            if (isFinite(rawHeight) && rawHeight > 0) {
                vbHeight = rawHeight;
            }
        }

        if (vbWidth === 0 || vbHeight === 0) {
            vbWidth = width;
            vbHeight = height;
        }

        let scale = Math.min(width / vbWidth, height / vbHeight);
        if (!isFinite(scale) || scale === 0) {
            scale = 1;
        }
        const offsetX = (width - vbWidth * scale) / 2;
        const offsetY = (height - vbHeight * scale) / 2;
        const body = svgElement.innerHTML;
        return `<g transform="translate(${offsetX - vbX * scale} ${offsetY - vbY * scale}) scale(${scale})">${body}</g>`;
    }

    stripXmlDeclaration(svgText) {
        return svgText
            .replace(/<\?xml[\s\S]*?\?>/gi, '')
            .replace(/<!--[\s\S]*?-->/g, '')
            .trim();
                            }

    extractSvgFromDataUrl(dataUrl) {
        const commaIndex = dataUrl.indexOf(',');
        if (commaIndex === -1) {
            return '';
                    }

        const meta = dataUrl.substring(0, commaIndex);
        const data = dataUrl.substring(commaIndex + 1);

        if (meta.includes(';base64')) {
            return decodeURIComponent(escape(atob(data)));
        }

        return decodeURIComponent(data);
    }

    /**
     * 生成文字对象的SVG
     * @param {fabric.Text} textObj 文字对象
     * @returns {string} SVG文字元素
     */
    generateTextSVG(textObj) {
        const x = textObj.left || 0;
        const y = textObj.top || 0;
        const fontSize = textObj.fontSize || 20;
        const fontFamily = textObj.fontFamily || 'Arial';
        const fill = textObj.fill || '#000000';
        const text = textObj.text || '';
        const angle = textObj.angle || 0;
        
        // 处理文字换行
        const lines = text.split('\n');
        let svgText = '';
        
        lines.forEach((line, index) => {
            const lineY = y + (index * fontSize * 1.2);
            svgText += `<text x="${x}" y="${lineY}" font-family="${fontFamily}" font-size="${fontSize}" 
                fill="${fill}" transform="rotate(${angle} ${x} ${lineY})">${this.escapeXml(line)}</text>`;
        });
        
        return svgText;
    }

    /**
     * 生成图片对象的SVG
     * @param {fabric.Image} imgObj 图片对象
     * @returns {Promise<string>} SVG图片元素
     */
    async generateImageSVG(imgObj) {
        return new Promise((resolve) => {
            try {
                const x = imgObj.left || 0;
                const y = imgObj.top || 0;
                const scaleX = imgObj.scaleX || 1;
                const scaleY = imgObj.scaleY || 1;
                const angle = imgObj.angle || 0;
                const width = imgObj.width * scaleX;
                const height = imgObj.height * scaleY;
                
                // 安全地获取图片元素和源
                let imgElement = null;
                let src = '';
                
                // 尝试多种方法获取图片元素
                if (typeof imgObj.getElement === 'function') {
                    imgElement = imgObj.getElement();
                } else if (imgObj._element) {
                    imgElement = imgObj._element;
                } else if (imgObj.element) {
                    imgElement = imgObj.element;
                }
                
                // 获取图片源
                if (imgElement) {
                    src = imgElement.src || imgElement.getAttribute('src') || '';
                } else if (imgObj.src) {
                    src = imgObj.src;
                } else if (imgObj._originalElement && imgObj._originalElement.src) {
                    src = imgObj._originalElement.src;
                }
                
                console.log('图片对象信息:', {
                    hasGetElement: typeof imgObj.getElement === 'function',
                    imgElement: !!imgElement,
                    src: src,
                    type: imgObj.type
                });
                
                if (src) {
                    const isSVG = src.includes('data:image/svg') || src.includes('.svg');
                    
                    if (isSVG) {
                        // SVG图片特殊处理 - 直接嵌入SVG内容
                        this.generateSVGImageElement(imgObj, x, y, width, height, angle, src)
                            .then(svgElement => resolve(svgElement))
                            .catch(error => {
                                console.error('SVG图片处理错误:', error);
                                resolve('');
                            });
                    } else if (imgElement) {
                        // 普通图片处理
                        try {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            canvas.width = width;
                            canvas.height = height;
                            
                            ctx.save();
                            ctx.translate(width / 2, height / 2);
                            ctx.rotate((angle * Math.PI) / 180);
                            ctx.scale(scaleX, scaleY);
                            ctx.drawImage(imgElement, -imgObj.width / 2, -imgObj.height / 2);
                            ctx.restore();
                            
                            const dataURL = canvas.toDataURL('image/png');
                            
                            const svgImage = `<image x="${x}" y="${y}" width="${width}" height="${height}" 
                                xlink:href="${dataURL}" transform="rotate(${angle} ${x + width/2} ${y + height/2})" />`;
                            
                            resolve(svgImage);
                        } catch (canvasError) {
                            console.error('Canvas处理错误:', canvasError);
                            // 回退到直接引用
                            const svgImage = `<image x="${x}" y="${y}" width="${width}" height="${height}" 
                                xlink:href="${src}" transform="rotate(${angle} ${x + width/2} ${y + height/2})" />`;
                            resolve(svgImage);
                        }
                    } else {
                        // 没有图片元素，直接引用源
                        const svgImage = `<image x="${x}" y="${y}" width="${width}" height="${height}" 
                            xlink:href="${src}" transform="rotate(${angle} ${x + width/2} ${y + height/2})" />`;
                        resolve(svgImage);
                    }
                } else {
                    console.warn('无法获取图片源');
                    resolve('');
                }
            } catch (error) {
                console.error('图片SVG生成错误:', error);
                resolve('');
            }
        });
    }

    /**
     * 生成SVG图片元素
     * @param {fabric.Image} imgObj 图片对象
     * @param {number} x X坐标
     * @param {number} y Y坐标
     * @param {number} width 宽度
     * @param {number} height 高度
     * @param {number} angle 角度
     * @param {string} src 图片源
     * @returns {Promise<string>} SVG元素
     */
    async generateSVGImageElement(imgObj, x, y, width, height, angle, src) {
        return new Promise((resolve) => {
            try {
                if (src.includes('data:image/svg')) {
                    // 处理Data URL格式的SVG
                    const svgContent = src.split(',')[1] ? atob(src.split(',')[1]) : src;
                    
                    // 解析SVG内容
                    const parser = new DOMParser();
                    const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                    const svgElement = svgDoc.querySelector('svg');
                    
                    if (svgElement) {
                        // 设置SVG的变换属性
                        const transform = `translate(${x}, ${y}) rotate(${angle} ${width/2} ${height/2}) scale(${imgObj.scaleX || 1}, ${imgObj.scaleY || 1})`;
                        svgElement.setAttribute('transform', transform);
                        svgElement.setAttribute('width', width);
                        svgElement.setAttribute('height', height);
                        
                        resolve(svgElement.outerHTML);
                    } else {
                        // 如果解析失败，回退到普通图片处理
                        resolve(`<image x="${x}" y="${y}" width="${width}" height="${height}" 
                            xlink:href="${src}" transform="rotate(${angle} ${x + width/2} ${y + height/2})" />`);
                    }
                } else {
                    // 普通SVG文件URL - 也尝试内嵌
                    this.fetchAndEmbedSVG(src, x, y, width, height, angle, imgObj)
                        .then(embeddedSVG => resolve(embeddedSVG))
                        .catch(error => {
                            console.error('SVG文件内嵌失败，回退到引用方式:', error);
                            // 回退到引用方式
                            resolve(`<image x="${x}" y="${y}" width="${width}" height="${height}" 
                                xlink:href="${src}" transform="rotate(${angle} ${x + width/2} ${y + height/2})" />`);
                        });
                }
            } catch (error) {
                console.error('SVG元素生成错误:', error);
                resolve('');
            }
        });
    }

    /**
     * 转义XML特殊字符
     * @param {string} text 原始文本
     * @returns {string} 转义后的文本
     */
    escapeXml(text) {
        return text.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&apos;');
    }

    /**
     * 将图片转换为DataURL
     * @param {HTMLImageElement} img 图片元素
     * @returns {string} DataURL字符串
     */
    imageToDataURL(img) {
        try {
            // 如果图片已经有data URL，直接返回
            if (img.src && img.src.startsWith('data:')) {
                return img.src;
            }
            
            // 如果是file://协议，直接返回原始src（避免CORS问题）
            if (img.src && img.src.startsWith('file://')) {
                console.log('使用file://协议，直接返回原始src:', img.src);
                return img.src;
            }
            
            // 创建canvas来转换图片
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = img.naturalWidth || img.width;
            canvas.height = img.naturalHeight || img.height;
            
            ctx.drawImage(img, 0, 0);
            
            return canvas.toDataURL('image/png');
        } catch (error) {
            console.error('图片转DataURL错误:', error);
            // 如果转换失败，返回原始src
            return img.src || '';
        }
    }

    /**
     * 获取并内嵌SVG文件内容
     * @param {string} svgUrl SVG文件URL
     * @param {number} x X坐标
     * @param {number} y Y坐标
     * @param {number} width 宽度
     * @param {number} height 高度
     * @param {number} angle 角度
     * @param {Object} imgObj 图片对象
     * @returns {Promise<string>} 内嵌的SVG元素
     */
    async fetchAndEmbedSVG(svgUrl, x, y, width, height, angle, imgObj) {
        try {
            console.log('尝试获取并内嵌SVG文件:', svgUrl);
            
            // 使用fetch获取SVG内容
            const response = await fetch(svgUrl);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const svgContent = await response.text();
            console.log('SVG文件内容获取成功，长度:', svgContent.length);
            
            // 解析SVG内容
            const parser = new DOMParser();
            const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
            const svgElement = svgDoc.querySelector('svg');
            
            if (!svgElement) {
                throw new Error('无法解析SVG内容');
            }
            
            // 设置SVG的变换属性
            const transform = `translate(${x}, ${y}) rotate(${angle} ${width/2} ${height/2}) scale(${imgObj.scaleX || 1}, ${imgObj.scaleY || 1})`;
            svgElement.setAttribute('transform', transform);
            svgElement.setAttribute('width', width);
            svgElement.setAttribute('height', height);
            
            console.log('SVG文件内容已成功内嵌');
            return svgElement.outerHTML;
            
        } catch (error) {
            console.error('获取并内嵌SVG文件失败:', error);
            throw error;
        }
    }

    /**
     * 将画布对象坐标转换为显示坐标
     * @param {Object} obj 画布对象
     * @returns {Object} 转换后的对象
     */
    convertToDisplayCoordinates(obj) {
        const devicePixelRatio = window.devicePixelRatio || 1;
        const scaleFactor = 1 / devicePixelRatio;
        
        // 创建对象副本
        const displayObj = Object.assign({}, obj);
        
        // 转换位置和尺寸
        displayObj.left = (obj.left || 0) * scaleFactor;
        displayObj.top = (obj.top || 0) * scaleFactor;
        displayObj.width = (obj.width || 0) * scaleFactor;
        displayObj.height = (obj.height || 0) * scaleFactor;
        
        console.log('坐标转换:', {
            原始: { left: obj.left, top: obj.top, width: obj.width, height: obj.height },
            转换后: { left: displayObj.left, top: displayObj.top, width: displayObj.width, height: displayObj.height },
            缩放因子: scaleFactor
        });
        
        return displayObj;
    }

    /**
     * 下载SVG文件
     * @param {string} svgContent SVG内容
     * @param {string} filename 文件名
     */
    downloadSVGFile(svgContent, filename) {
        try {
            // 创建Blob对象
            const blob = new Blob([svgContent], { type: 'image/svg+xml;charset=utf-8' });
            
            // 创建下载链接
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            
            // 触发下载
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // 清理URL对象
            URL.revokeObjectURL(url);
            
            console.log('SVG 파일 다운로드 시작:', filename);
            
        } catch (error) {
            console.error('SVG 다운로드 오류:', error);
            throw error;
        }
    }

    /**
     * 显示下载进度提示
     * @param {string} message 提示消息
     */
    showDownloadProgress(message) {
        // 创建进度提示
        const progressDiv = document.createElement('div');
        progressDiv.id = 'downloadProgress';
        progressDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            z-index: 10000;
            font-size: 16px;
            text-align: center;
        `;
        progressDiv.innerHTML = `
            <div>${message}</div>
            <div style="margin-top: 10px; font-size: 12px;">잠시만 기다려주세요...</div>
        `;
        
        document.body.appendChild(progressDiv);
        
        // 3秒后自动移除
        setTimeout(() => {
            const element = document.getElementById('downloadProgress');
            if (element) {
                element.remove();
            }
        }, 3000);
    }

    toggleLayerVisibility(index) {
        const objects = this.getRenderableObjects();
        const target = objects[index];
        if (!target) {
            return;
        }
        target.visible = !target.visible;
        this.canvas.requestRenderAll();
        if (!this.isRestoringHistory) {
            this.addToHistory();
        }
        this.refreshLayersPanel();
    }

    initializeLayersPanel() {
        this.layersListElement = document.getElementById('layersList');
        if (!this.layersListElement) {
            console.warn('레이어 패널 요소를 찾지 못했습니다');
            return;
        }
        this.refreshLayersPanel();
    }

    initializeImageControls() {
        this.imageControls = document.getElementById('imageControls');
        if (!this.imageControls) {
            return;
        }

        if (this.canvasContainer && this.imageControls.parentElement !== this.canvasContainer) {
            this.canvasContainer.appendChild(this.imageControls);
        }

        this.lockToggleButton = document.getElementById('btnToggleLock');
        this.lockIcon = this.lockToggleButton ? this.lockToggleButton.querySelector('.lock-icon') : null;
        this.lockLabel = this.lockToggleButton ? this.lockToggleButton.querySelector('.lock-label') : null;

        if (this.lockToggleButton) {
            this.lockToggleButton.disabled = true;
            this.lockToggleButton.addEventListener('click', () => this.toggleActiveImageLock());
        }

        const backgroundRemovalBtn = document.getElementById('btnBackgroundRemoval');
        if (backgroundRemovalBtn) {
            backgroundRemovalBtn.addEventListener('click', () => this.handleBackgroundRemovalRequest());
        }
    }

    handleActiveImageTransform(event) {
        if (!this.canvas) {
            return;
        }
        const target = event ? event.target : null;
        if (!target) {
            return;
        }

        if (target.customType === 'removeTag') {
            const image = this.findImageById(target.linkedImageId);
            if (image) {
                this.keepRemoveTagWithinImage(target, image, false);
                this.updateRemoveTagRelativePosition(target, image);
            }
            return;
        }

        if (target.type === 'image') {
            if (target === this.canvas.getActiveObject()) {
                this.updateImageControls(target);
            } else {
                this.updateImageControls(this.canvas.getActiveObject());
            }
            this.updateRemoveTagPositionForImage(target);
            return;
        }

        if (target === this.canvas.getActiveObject()) {
            this.updateImageControls(target);
        }
    }

    updateImageControls(activeObject) {
        if (!this.imageControls || !this.canvasContainer) {
            return;
        }

        if (activeObject && activeObject.type === 'image') {
            this.imageControls.style.display = 'flex';
            this.imageControls.style.visibility = 'hidden';
            this.positionImageControls(activeObject);
            this.imageControls.style.visibility = 'visible';
            this.updateLockControlState(activeObject);
            this.updateBackgroundRemovalButtonState(activeObject);
            if (this.lockToggleButton) {
                this.lockToggleButton.disabled = false;
            }
        } else {
            this.imageControls.style.display = 'none';
            this.updateBackgroundRemovalButtonState(null);
            if (this.lockToggleButton) {
                this.lockToggleButton.disabled = true;
            }
        }
    }

    positionImageControls(imageObject) {
        if (!this.canvas || !this.canvasContainer || !this.imageControls || !imageObject) {
            return;
        }

        const multiplier = this.exportMultiplier || (window.devicePixelRatio || 1);
        const boundingRect = imageObject.getBoundingRect(true, true);
        const canvasElement = this.canvas.upperCanvasEl;
        if (!canvasElement) {
            return;
        }

        const containerRect = this.canvasContainer.getBoundingClientRect();
        const canvasRect = canvasElement.getBoundingClientRect();
        const offsetLeft = canvasRect.left - containerRect.left;
        const offsetTop = canvasRect.top - containerRect.top;

        const centerX = offsetLeft + (boundingRect.left + boundingRect.width / 2) / multiplier;
        const bottomY = offsetTop + (boundingRect.top + boundingRect.height) / multiplier;

        const controlWidth = this.imageControls.offsetWidth || 0;
        const controlHeight = this.imageControls.offsetHeight || 0;
        const halfWidth = controlWidth / 2;
        const minCenter = halfWidth + 8;
        const maxCenter = this.canvasContainer.clientWidth - halfWidth - 8;
        const clampedCenter = Math.max(minCenter, Math.min(centerX, maxCenter));

        const offset = 12;
        const minTop = 8;
        const maxTop = this.canvasContainer.clientHeight - controlHeight - 8;
        const desiredTop = bottomY + offset;
        const clampedTop = Math.max(minTop, Math.min(desiredTop, maxTop));

        this.imageControls.style.left = `${clampedCenter}px`;
        this.imageControls.style.top = `${clampedTop}px`;
        this.imageControls.style.transform = 'translate(-50%, 0)';
    }

    updateLockControlState(imageObject) {
        if (!imageObject) {
            return;
        }
        const isLocked = !!imageObject.customLocked;
        if (this.lockIcon) {
            this.lockIcon.src = isLocked ? this.lockIconPaths.locked : this.lockIconPaths.unlocked;
        }
        if (this.lockLabel) {
            this.lockLabel.textContent = isLocked ? '잠금 해제' : '잠금';
        }
    }

    toggleActiveImageLock() {
        if (!this.canvas) {
            return;
        }
        const activeObject = this.canvas.getActiveObject();
        if (!activeObject || activeObject.type !== 'image') {
            return;
        }

        const nextLocked = !activeObject.customLocked;
        activeObject.customLocked = nextLocked;

        activeObject.set({
            lockMovementX: nextLocked,
            lockMovementY: nextLocked,
            lockScalingX: nextLocked,
            lockScalingY: nextLocked,
            lockScalingFlip: nextLocked,
            lockRotation: nextLocked
        });

        activeObject.hasControls = !nextLocked;
        activeObject.hasBorders = !nextLocked;

        this.canvas.requestRenderAll();
        this.updateLockControlState(activeObject);
        this.addToHistory();
    }

    getRenderableObjects() {
        if (!this.canvas) {
            return [];
        }
        return this.canvas.getObjects().filter(obj =>
            obj !== this.canvas.backgroundImage &&
            obj.customType !== 'removeTag' &&
            obj.customType !== 'clipOverlay'
        );
    }

    refreshLayersPanel() {
        if (!this.layersListElement) {
            return;
        }
        const objects = this.getRenderableObjects();
        const displayObjects = objects.slice().reverse();
        this.layersListElement.innerHTML = '';

        displayObjects.forEach(obj => {
            const originalIndex = objects.indexOf(obj);
            const item = document.createElement('li');
            item.className = 'layer-item';
            item.dataset.layerIndex = String(originalIndex);

            const iconPath = obj.visible ? this.layerIconPaths.visible : this.layerIconPaths.hidden;
            item.innerHTML = `
                <button type="button" class="layer-visibility-btn">
                    <img src="${iconPath}" alt="${obj.visible ? '레이어 표시' : '레이어 숨김'}">
                </button>
                ${this.getLayerPreviewHTML(obj)}
            `;

            const visibilityBtn = item.querySelector('.layer-visibility-btn');
            visibilityBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                this.toggleLayerVisibility(originalIndex);
            });

            item.addEventListener('click', () => {
                this.focusLayer(originalIndex);
            });

            this.layersListElement.appendChild(item);
        });

        this.updateLayerSelection();
    }

    updateLayerSelection() {
        if (!this.layersListElement) {
            return;
        }
        const objects = this.getRenderableObjects();
        const activeObject = this.canvas ? this.canvas.getActiveObject() : null;
        this.layersListElement.querySelectorAll('.layer-item').forEach(item => {
            const index = parseInt(item.dataset.layerIndex, 10);
            const target = objects[index];
            const isSelected = !!activeObject && (
                activeObject === target ||
                (activeObject.type === 'activeSelection' && activeObject._objects && activeObject._objects.includes(target))
            );
            item.classList.toggle('is-active', isSelected);
        });
    }

    focusLayer(index) {
        const objects = this.getRenderableObjects();
        const target = objects[index];
        if (!target) {
            return;
        }
        if (!target.visible) {
            target.visible = true;
        }
        this.canvas.setActiveObject(target);
        this.canvas.requestRenderAll();
        this.updateLayerSelection();
        this.updateToolbarState();
    }

    getLayerDisplayName(obj) {
        if (!obj) {
            return 'layer';
        }
        if (obj.layerLabel) {
            return obj.layerLabel;
        }
        if (obj.type === 'text' || obj.type === 'textbox' || obj.type === 'i-text') {
            return 'text';
        }
        if (obj.type === 'image') {
            return 'image';
        }
        return obj.type || 'layer';
    }

    getLayerPreviewHTML(obj) {
        if (!obj) {
            return '<div class="layer-thumb layer-thumb-text">layer</div>';
        }

        // 이미지 레이어: 실제 이미지 축소판 표시
        if (obj.type === 'image') {
            let src = '';
            if (obj._originalElement && obj._originalElement.src) {
                src = obj._originalElement.src;
            } else if (typeof obj.getSrc === 'function') {
                src = obj.getSrc();
            }
            const safeSrc = this.escapeHtml(src || '');
            return `
                <div class="layer-thumb layer-thumb-image">
                    ${safeSrc ? `<img src="${safeSrc}" alt="이미지 레이어">` : ''}
                </div>
            `;
        }

        // 텍스트 레이어: 실제 텍스트의 축약본 표시
        if (obj.type === 'text' || obj.type === 'textbox' || obj.type === 'i-text') {
            const text = (obj.text || '').trim() || '텍스트';
            const short = text.length > 10 ? text.slice(0, 10) + '…' : text;
            return `
                <div class="layer-thumb layer-thumb-text">
                    ${this.escapeHtml(short)}
                </div>
            `;
        }

        // 기타 레이어: 기존 표시 이름 사용
        const name = this.getLayerDisplayName(obj) || 'layer';
        return `
            <div class="layer-thumb layer-thumb-text">
                ${this.escapeHtml(name)}
            </div>
        `;
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    handleBackgroundRemovalRequest() {
        if (!this.canvas) {
            return;
        }

        const activeObject = this.canvas.getActiveObject();
        if (!activeObject || activeObject.type !== 'image') {
            alert('배경제거 요청을 적용할 이미지를 먼저 선택하세요.');
            return;
        }

        const existingTag = this.getRemoveTagForImage(activeObject);
        if (existingTag) {
            this.removeAssociatedRemoveTag(activeObject, true);
            this.updateBackgroundRemovalButtonState(null);
            this.canvas.requestRenderAll();
            this.addToHistory();
            return;
        }

        const removeTag = this.createRemoveTagForImage(activeObject);
        this.canvas.add(removeTag);
        removeTag.bringToFront();
        this.canvas.setActiveObject(activeObject);
        this.canvas.requestRenderAll();
        this.addToHistory();
        this.updateBackgroundRemovalButtonState(activeObject);
    }

    createRemoveTagForImage(imageObject) {
        this.assignCustomObjectId(imageObject);

        const tag = new fabric.Textbox('remove', {
            fontSize: 26,
            fontWeight: 'bold',
            fill: '#ff4d4f',
            backgroundColor: 'rgba(255, 255, 255, 0.85)',
            padding: 6,
            textAlign: 'center',
            borderColor: '#ff4d4f',
            cornerStyle: 'circle',
            strokeWidth: 0,
            selectable: true,
            hasControls: true,
            hasBorders: true
        });

        tag.customType = 'removeTag';
        tag.linkedImageId = imageObject._customId;
        tag.excludeFromExport = false;
        tag.editable = false;
        tag.lockSkewingX = true;
        tag.lockSkewingY = true;
        this.assignCustomObjectId(tag);

        const imageRect = imageObject.getBoundingRect(true, true);
        tag.set({
            left: imageRect.left + imageRect.width / 2 - tag.width / 2,
            top: imageRect.top + imageRect.height - tag.height - this.removeTagPadding
        });
        tag.setCoords();

        this.ensureRemoveTagFitsInside(tag, imageObject);
        this.keepRemoveTagWithinImage(tag, imageObject, true);
        this.updateRemoveTagRelativePosition(tag, imageObject);
        imageObject.removeTagId = tag._customId;

        return tag;
    }

    getRemoveTagForImage(imageObject) {
        if (!this.canvas || !imageObject) {
            return null;
        }
        const objects = this.canvas.getObjects();
        return objects.find(obj => obj.customType === 'removeTag' && obj.linkedImageId === imageObject._customId) || null;
    }

    findRemoveTagById(tagId) {
        if (!this.canvas || !tagId) {
            return null;
        }
        return this.canvas.getObjects().find(obj => obj.customType === 'removeTag' && obj._customId === tagId) || null;
    }

    findImageById(imageId) {
        if (!this.canvas || !imageId) {
            return null;
        }
        return this.canvas.getObjects().find(obj => obj.type === 'image' && obj._customId === imageId) || null;
    }

    removeAssociatedRemoveTag(imageObject, fromToggle = false) {
        if (!this.canvas || !imageObject) {
            return;
        }
        const tag = this.getRemoveTagForImage(imageObject);
        if (tag) {
            this.canvas.remove(tag);
        }
        delete imageObject.removeTagId;
        if (!fromToggle) {
            const active = this.canvas.getActiveObject();
            if (active && active.type === 'image') {
                this.updateBackgroundRemovalButtonState(active);
            } else {
                this.updateBackgroundRemovalButtonState(null);
            }
        }
    }

    positionRemoveTagAtCenter(tag, imageObject) {
        if (!tag || !imageObject) {
            return;
        }
        const imageRect = imageObject.getBoundingRect(true, true);
        const tagWidth = tag.getScaledWidth ? tag.getScaledWidth() : tag.width * (tag.scaleX || 1);
        const tagHeight = tag.getScaledHeight ? tag.getScaledHeight() : tag.height * (tag.scaleY || 1);
        tag.set({
            left: imageRect.left + (imageRect.width - tagWidth) / 2,
            top: imageRect.top + (imageRect.height - tagHeight) / 2
        });
        tag.setCoords();
    }

    ensureRemoveTagFitsInside(tag, imageObject) {
        if (!tag || !imageObject) {
            return;
        }
        const imageRect = imageObject.getBoundingRect(true, true);
        let tagRect = tag.getBoundingRect(true, true);

        const availableWidth = Math.max(20, imageRect.width - this.removeTagPadding * 2);
        const availableHeight = Math.max(20, imageRect.height - this.removeTagPadding * 2);

        let scaleFactor = 1;
        if (tagRect.width > availableWidth) {
            scaleFactor = Math.min(scaleFactor, availableWidth / tagRect.width);
        }
        if (tagRect.height > availableHeight) {
            scaleFactor = Math.min(scaleFactor, availableHeight / tagRect.height);
        }

        if (scaleFactor < 1) {
            tag.scale(scaleFactor);
            tag.set({ strokeWidth: 0 });
            tag.setCoords();
            tagRect = tag.getBoundingRect(true, true);
        }
    }

    keepRemoveTagWithinImage(tag, imageObject, forceFit = false) {
        if (!tag || !imageObject) {
            return;
        }
        if (forceFit) {
            this.ensureRemoveTagFitsInside(tag, imageObject);
        }

        const imageRect = imageObject.getBoundingRect(true, true);
        const tagRect = tag.getBoundingRect(true, true);
        let moveX = 0;
        let moveY = 0;

        const leftBoundary = imageRect.left + this.removeTagPadding;
        const topBoundary = imageRect.top + this.removeTagPadding;
        const rightBoundary = imageRect.left + imageRect.width - this.removeTagPadding;
        const bottomBoundary = imageRect.top + imageRect.height - this.removeTagPadding;

        if (tagRect.left < leftBoundary) {
            moveX = leftBoundary - tagRect.left;
        } else if (tagRect.left + tagRect.width > rightBoundary) {
            moveX = rightBoundary - (tagRect.left + tagRect.width);
        }

        if (tagRect.top < topBoundary) {
            moveY = topBoundary - tagRect.top;
        } else if (tagRect.top + tagRect.height > bottomBoundary) {
            moveY = bottomBoundary - (tagRect.top + tagRect.height);
        }

        if (moveX !== 0 || moveY !== 0) {
            tag.set({
                left: tag.left + moveX,
                top: tag.top + moveY
            });
            tag.setCoords();
        }
    }

    updateRemoveTagRelativePosition(tag, imageObject) {
        if (!tag || !imageObject) {
            return;
        }
        const imageRect = imageObject.getBoundingRect(true, true);
        const tagCenter = tag.getCenterPoint();
        tag.relativePosition = {
            x: (tagCenter.x - imageRect.left) / imageRect.width,
            y: (tagCenter.y - imageRect.top) / imageRect.height
        };
        imageObject.removeTagId = tag._customId;
    }

    applyRemoveTagRelativePosition(tag, imageObject) {
        if (!tag || !imageObject || !tag.relativePosition) {
            return;
        }
        const imageRect = imageObject.getBoundingRect(true, true);
        const targetCenterX = imageRect.left + imageRect.width * tag.relativePosition.x;
        const targetCenterY = imageRect.top + imageRect.height * tag.relativePosition.y;
        tag.setPositionByOrigin({ x: targetCenterX, y: targetCenterY }, 'center', 'center');
        tag.setCoords();
    }

    updateRemoveTagPositionForImage(imageObject) {
        if (!imageObject) {
            return;
        }
        const tag = this.getRemoveTagForImage(imageObject);
        if (!tag) {
            return;
        }
        this.applyRemoveTagRelativePosition(tag, imageObject);
        this.keepRemoveTagWithinImage(tag, imageObject, true);
        this.updateRemoveTagRelativePosition(tag, imageObject);
        tag.bringToFront();
        const active = this.canvas.getActiveObject();
        if (active && active.type === 'image' && active === imageObject) {
            this.updateBackgroundRemovalButtonState(imageObject);
        }
    }

    assignCustomObjectId(obj) {
        if (!obj || obj === this.canvas.backgroundImage) {
            return;
        }
        if (!obj._customId) {
            const prefix = obj.customType === 'removeTag'
                ? 'tag'
                : (obj.type === 'image' ? 'img' : 'obj');
            obj._customId = this.generateObjectId(prefix);
        }
    }

    generateObjectId(prefix = 'obj') {
        const nextId = `${prefix}_${this.objectIdCounter}`;
        this.objectIdCounter += 1;
        return nextId;
    }

    updateBackgroundRemovalButtonState(imageObject) {
        const button = document.getElementById('btnBackgroundRemoval');
        if (!button) {
            return;
        }
        const hasTag = imageObject && !!this.getRemoveTagForImage(imageObject);
        button.classList.toggle('is-active', !!hasTag);
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    const editor = new CustomGoodsEditor();

    // ✅ 预加载“커스텀 방법 안내”弹框内的引导图片，避免打开弹框时先空白一段时间
    try {
        const guideModal = document.getElementById('customGuideModal');
        if (guideModal) {
            const urls = Array.from(guideModal.querySelectorAll('img'))
                .map((img) => img && (img.getAttribute('src') || img.src))
                .filter(Boolean);
            urls.forEach((url) => {
                try {
                    const pre = new Image();
                    pre.decoding = 'async';
                    pre.src = url;
                } catch (e) {
                    // ignore
                }
            });
        }
    } catch (e) {
        // ignore
    }

    // ✅ 进入 goods_custom 页面后自动弹出 “커스텀 방법 안내” (供用户观看)
    try {
        setTimeout(() => {
            try {
                editor.showCustomGuideModal && editor.showCustomGuideModal();
            } catch (e) {
                // ignore
            }
        }, 200);
    } catch (e) {
        // ignore
    }
    
    // 初始化默认尺寸（来自模板配置）
    let pageConfig = {};
    try {
        const rawPageConfig = (document.getElementById('pageConfig').textContent || '').trim();
        if (rawPageConfig) {
            pageConfig = JSON.parse(rawPageConfig);
            if (pageConfig.custom_size_display) {
                editor.selectedSize = pageConfig.custom_size_display;
            } else if (pageConfig.custom_width && pageConfig.custom_height) {
                editor.selectedSize = pageConfig.custom_width + 'x' + pageConfig.custom_height;
            }
        }
    } catch (e) {
        console.warn('初始化定制尺寸失败', e);
    }

    const syncDesignDropdownState = (designId) => {
        let bgSrc = null;
        try {
            const dropdown = document.querySelector('.design-dropdown');
            if (dropdown) {
                const items = dropdown.querySelectorAll('.design-dropdown-item');
                items.forEach(item => {
                    const id = item.getAttribute('data-design-id') || 'base';
                    if (id === designId) {
                        item.classList.add('is-active');
                    if (id === 'base' && editor.selectedColorImage) {
                        bgSrc = editor.selectedColorImage;
                    } else {
                        bgSrc = item.getAttribute('data-image-src') || null;
                    }
                    } else {
                        item.classList.remove('is-active');
                    }
                });
            }
            const bgEl = document.getElementById('backgroundImage');
            if (bgEl && bgSrc) {
                bgEl.src = bgSrc;
            }
        } catch (syncErr) {
            console.warn('同步 디자인 영역 下拉/背景图失败', syncErr);
        }
        return bgSrc;
    };

    const applyCustomerPatternImages = (patterns) => {
        if (Array.isArray(patterns) && patterns.length > 0) {
            editor.customerPatternImages = patterns.slice();
            editor.customerPatternImage = patterns[0] || null;
        } else {
            editor.customerPatternImages = [];
            editor.customerPatternImage = null;
        }
    };

    const getGlobalPatternImages = () => {
        try {
            const multiRaw = localStorage.getItem('customOrderCustomerPatternImages');
            if (multiRaw) {
                const parsed = JSON.parse(multiRaw);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed;
                }
            }
            const single = localStorage.getItem('customOrderCustomerPatternImage');
            if (single) {
                return [single];
            }
        } catch (err) {
            console.warn('读取全局 customOrderCustomerPatternImages 失败', err);
        }
        return [];
    };

    // ✅ 尝试还原画布（仅在购物车"편집하기"入口生效）
    // 优先从数据库读取，fallback 到 localStorage
    try {
        const currentGoodsNo = String((pageConfig && pageConfig.goodsNo) || '');

        // 入口标记：只有 URL 中带 cartSno 才认为是从购物车"편집하기"进入
        const urlParams = new URLSearchParams(window.location.search);
        const cartSnoParam = urlParams.get('cartSno');
        const hasCartSno = !!cartSnoParam;

        if (hasCartSno) {
            // ✅ 优先从数据库读取画布数据
            restoreCanvasFromDatabase(cartSnoParam, currentGoodsNo);
        }
    } catch (e) {
        console.warn('还原定制设计失败', e);
    }

    /**
     * 从数据库读取画布数据并还原（AJAX）
     * @param {string} cartSno - 购物车编号
     * @param {string} currentGoodsNo - 当前商品编号
     */
    function restoreCanvasFromDatabase(cartSno, currentGoodsNo) {
        console.log('✅ [편집하기] 尝试从数据库读取画布数据，cartSno=' + cartSno);

        fetch('../custom/goods_custom.php?mode=getCanvasData&cartSno=' + encodeURIComponent(cartSno))
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (!result.success || !result.data || !result.data.customization_data) {
                    console.warn('❌ 数据库无画布数据，尝试 fallback 到 localStorage');
                    restoreCanvasFromLocalStorage(cartSno, currentGoodsNo);
                    return;
                }

                var customizationData = result.data.customization_data;
                var canvasJSON = customizationData.canvasJSON;
                var designStates = customizationData.designStates || null;
                var currentDesignId = customizationData.currentDesignId || 'base';
                var customerPatternImages = customizationData.customerPatternImages || [];

                // ⭐ 还原选中的颜色及其底图
                if (customizationData.selectedColor) {
                    editor.selectedColor = customizationData.selectedColor;
                    try {
                        const items = document.querySelectorAll('.color-item');
                        items.forEach(it => it.classList.remove('active'));
                        const activeItem = document.querySelector(`.color-item[data-color="${customizationData.selectedColor}"]`);
                        if (activeItem) {
                            activeItem.classList.add('active');
                        }
                    } catch (e) {
                        console.warn('同步颜色高亮失败', e);
                    }
                }
                if (customizationData.selectedColorImage) {
                    editor.selectedColorImage = customizationData.selectedColorImage;
                    try {
                        const dropdown = document.querySelector('.design-dropdown');
                        if (dropdown) {
                            const baseItem = dropdown.querySelector('.design-dropdown-item[data-design-id="base"]');
                            if (baseItem) {
                                baseItem.setAttribute('data-image-src', customizationData.selectedColorImage);
                            }
                        }
                    } catch (e) {
                        console.warn('更新设计下拉中的 base 图片失败', e);
                    }
                    const bgEl = document.getElementById('backgroundImage');
                    if (bgEl) {
                        bgEl.src = customizationData.selectedColorImage;
                    }
                }

                // ⭐ 新数据结构：存在 designStates → 还原多设计画布
                if (designStates && canvasJSON) {
                    editor.isRestoringHistory = true;
                    editor.designStates = JSON.parse(JSON.stringify(designStates));
                    editor.currentDesignId = currentDesignId;
                    syncDesignDropdownState(currentDesignId);

                    // 按当前设计决定是否启用裁剪（与 switchDesign 逻辑一致）
                    editor.clipEnabled = (currentDesignId !== '4');

                    editor.restoreDesignState(currentDesignId).then(function () {
                        editor.isRestoringHistory = false;

                        // 恢复上传图案数组，保证再次加入购物车时还能带上 customer_pattern_image
                        applyCustomerPatternImages(customerPatternImages);

                        if (typeof editor.applyClipPathForAllObjects === 'function') {
                            editor.applyClipPathForAllObjects();
                        }
                        if (typeof editor.updateClipOverlayVisibility === 'function') {
                            editor.updateClipOverlayVisibility();
                        }

                        editor.addToHistory();
                        editor.updateToolbarState();
                        console.log('✅ 已从数据库还原多设计画布（cartSno=' + cartSno + '）');
                    }).catch(function (err) {
                        editor.isRestoringHistory = false;
                        console.warn('restoreDesignState 失败，降级为单画布还原', err);
                    });
                }
                // 兼容旧结构：只有单个 canvasJSON
                else if (canvasJSON) {
                    editor.isRestoringHistory = true;
                    syncDesignDropdownState(currentDesignId);
                    editor.canvas.loadFromJSON(canvasJSON, function () {
                        editor.canvas.renderAll();
                        editor.isRestoringHistory = false;

                        applyCustomerPatternImages(customerPatternImages);

                        if (typeof editor.applyClipPathForAllObjects === 'function') {
                            editor.applyClipPathForAllObjects();
                        }
                        if (typeof editor.updateClipOverlayVisibility === 'function') {
                            editor.updateClipOverlayVisibility();
                        }

                        editor.addToHistory();
                        editor.updateToolbarState();
                        console.log('✅ 已从数据库还原单画布（cartSno=' + cartSno + '）');
                    });
                } else {
                    console.warn('❌ 数据库返回的 customization_data 缺少 canvasJSON，尝试 fallback');
                    restoreCanvasFromLocalStorage(cartSno, currentGoodsNo);
                }
            })
            .catch(function (err) {
                console.warn('❌ 从数据库读取画布数据失败，fallback 到 localStorage', err);
                restoreCanvasFromLocalStorage(cartSno, currentGoodsNo);
            });
    }

    /**
     * 从 localStorage 读取画布数据并还原（fallback）
     * @param {string} cartSno - 购物车编号
     * @param {string} currentGoodsNo - 当前商品编号
     */
    function restoreCanvasFromLocalStorage(cartSno, currentGoodsNo) {
        var restored = false;

        // ✅ 优先使用每个购物车项专用的 custom_design_{cartSno} 本地缓存进行还原
        var perCartKey = 'custom_design_' + cartSno;
        var rawPerCart = localStorage.getItem(perCartKey);
        if (rawPerCart) {
            try {
                var parsedPerCart = JSON.parse(rawPerCart);
                var storedGoodsNoPer = String(parsedPerCart.goodsNo || '');
                var canvasJSONPer = parsedPerCart.canvasJSON;
                var designStatesPer = parsedPerCart.designStates || null;
                var currentDesignIdPer = parsedPerCart.currentDesignId || 'base';
                var customerPatternImagesPer = parsedPerCart.customerPatternImages || [];

                // ⭐ 还原 localStorage 中记录的颜色与底图
                if (parsedPerCart.selectedColor) {
                    editor.selectedColor = parsedPerCart.selectedColor;
                    try {
                        const items = document.querySelectorAll('.color-item');
                        items.forEach(it => it.classList.remove('active'));
                        const activeItem = document.querySelector(`.color-item[data-color="${parsedPerCart.selectedColor}"]`);
                        if (activeItem) {
                            activeItem.classList.add('active');
                        }
                    } catch (e) {
                        console.warn('同步本地缓存颜色高亮失败', e);
                    }
                }
                if (parsedPerCart.selectedColorImage) {
                    editor.selectedColorImage = parsedPerCart.selectedColorImage;
                    try {
                        const dropdown = document.querySelector('.design-dropdown');
                        if (dropdown) {
                            const baseItem = dropdown.querySelector('.design-dropdown-item[data-design-id=\"base\"]');
                            if (baseItem) {
                                baseItem.setAttribute('data-image-src', parsedPerCart.selectedColorImage);
                            }
                        }
                    } catch (e) {
                        console.warn('更新本地缓存下拉 base 图片失败', e);
                    }
                    const bgEl = document.getElementById('backgroundImage');
                    if (bgEl) {
                        bgEl.src = parsedPerCart.selectedColorImage;
                    }
                }

                // ⭐ 新数据结构：存在 designStates → 还原多设计画布
                if (designStatesPer && storedGoodsNoPer && currentGoodsNo && storedGoodsNoPer === currentGoodsNo) {
                    editor.isRestoringHistory = true;
                    editor.designStates = JSON.parse(JSON.stringify(designStatesPer));
                    editor.currentDesignId = currentDesignIdPer;
                    syncDesignDropdownState(currentDesignIdPer);

                    // 按当前设计决定是否启用裁剪（与 switchDesign 逻辑一致）
                    editor.clipEnabled = (currentDesignIdPer !== '4');

                    editor.restoreDesignState(currentDesignIdPer).then(function () {
                        editor.isRestoringHistory = false;

                        // 恢复上传图案数组，保证再次加入购物车时还能带上 customer_pattern_image
                        applyCustomerPatternImages(customerPatternImagesPer);

                        if (typeof editor.applyClipPathForAllObjects === 'function') {
                            editor.applyClipPathForAllObjects();
                        }
                        if (typeof editor.updateClipOverlayVisibility === 'function') {
                            editor.updateClipOverlayVisibility();
                        }

                        editor.addToHistory();
                        editor.updateToolbarState();
                        console.log('✅ 已从 localStorage custom_design_* 还原多设计画布（cartSno=' + cartSno + '）');
                    }).catch(function (err) {
                        editor.isRestoringHistory = false;
                        console.warn('restoreDesignState 失败，降级为单画布还原', err);
                    });

                    restored = true;
                }
                // 兼容旧结构：只有单个 canvasJSON
                else if (canvasJSONPer && storedGoodsNoPer && currentGoodsNo && storedGoodsNoPer === currentGoodsNo) {
                    editor.isRestoringHistory = true;
                    syncDesignDropdownState(currentDesignIdPer);
                    editor.canvas.loadFromJSON(canvasJSONPer, function () {
                        editor.canvas.renderAll();
                        editor.isRestoringHistory = false;

                        applyCustomerPatternImages(customerPatternImagesPer);

                        if (typeof editor.applyClipPathForAllObjects === 'function') {
                            editor.applyClipPathForAllObjects();
                        }
                        if (typeof editor.updateClipOverlayVisibility === 'function') {
                            editor.updateClipOverlayVisibility();
                        }

                        editor.addToHistory();
                        editor.updateToolbarState();
                        console.log('✅ 已从 localStorage custom_design_* 还原单画布（legacy，cartSno=' + cartSno + '）');
                    });
                    restored = true;
                }
            } catch (parseError) {
                console.warn('解析 custom_design_* 本地缓存失败', parseError);
            }
        }

        // ✅ 若没有 per-cart 缓存，则回退到原有的全局临时缓存方案（customOrderCanvasJSON）
        if (!restored) {
            var storedGoodsNo = localStorage.getItem('customOrderGoodsNo');
            var rawCanvasJSON = localStorage.getItem('customOrderCanvasJSON');

            // 只在"从购物车进入 + 商品编号一致 + 有画布数据"时还原，避免串商品
            if (rawCanvasJSON && storedGoodsNo && currentGoodsNo && storedGoodsNo === currentGoodsNo) {
                var canvasJSON = JSON.parse(rawCanvasJSON);
                editor.isRestoringHistory = true;

                editor.canvas.loadFromJSON(canvasJSON, function () {
                    editor.canvas.renderAll();
                    editor.isRestoringHistory = false;
                    applyCustomerPatternImages(getGlobalPatternImages());
                    editor.addToHistory();
                    editor.updateToolbarState();
                    console.log('✅ 已从 localStorage customOrderCanvasJSON 还原完整画布（legacy）');
                });
            }
        }
    }
    
    // ✅ 初始化 전체 디자인 下拉（切换商品背景图 + 独立画布状态）
    try {
        const dropdown = document.querySelector('.design-dropdown');
        if (dropdown) {
            const items = dropdown.querySelectorAll('.design-dropdown-item');

            items.forEach(item => {
                item.addEventListener('click', () => {
                    const src = item.getAttribute('data-image-src') || '';
                    const designId = item.getAttribute('data-design-id') || 'base';
                    if (!src) return;

                    if (typeof editor.switchDesign === 'function') {
                        editor.switchDesign(designId, src);
                    }

                    // 每次点击 패키지(design-id="4") 都弹出提示，并且必须点击确认按钮关闭
                    if (designId === '4' && typeof editor.showPackageGuideModal === 'function') {
                        editor.showPackageGuideModal();
                    }

                    // 更新激活状态
                    items.forEach(btn => btn.classList.remove('is-active'));
                    item.classList.add('is-active');
                });
            });
        }
    } catch (e) {
        console.warn('初始化 전체 디자인 드롭다운失败', e);
    }
    
    // 在控制台暴露编辑器实例，方便调试
    window.customEditor = editor;
    console.log('定制编辑器已初始化，可以通过 window.customEditor 访问');
    console.log('使用 customEditor.testCanvasState() 测试画布状态');
});
