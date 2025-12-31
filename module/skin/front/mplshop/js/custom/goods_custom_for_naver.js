// goods_custom_for_naver.js
// 목적: goods_custom.js는 수정하지 않고, 외부몰(네이버 등) 주문 모드 UI/동작만 "추가"한다.
// - purchase(구매) 모드: 기존 장바구니 플로우 유지
// - external(외부몰 주문) 모드: "주문 확인" 팝업으로 전환 + 다운로드 버튼 표시
// 현재 단계: 프론트만 구현 (서버로 메일 전송은 이후 단계)

document.addEventListener('DOMContentLoaded', function () {
    var editor = window.customEditor;
    if (!editor) {
        console.warn('[goods_custom_for_naver] window.customEditor 가 없습니다. (goods_custom.js 초기화 확인 필요)');
        return;
    }

    // 기본 모드
    editor.orderMode = 'purchase';

    // ✅ 고객에게 제공할 "외부 주문 모드" 전용 URL 지원
    // 예) /custom/goods_custom.php?goodsNo=...&orderMode=external
    var ORDER_MODE_PARAM = 'orderMode';
    var ORDER_MODE_VALUE = 'external';
    function getUrlOrderModeIsExternal() {
        try {
            var url = new URL(window.location.href);
            return url.searchParams.get(ORDER_MODE_PARAM) === ORDER_MODE_VALUE;
        } catch (e) {
            return false;
        }
    }

    // ✅ 플랫폼 구분 파라미터 지원
    // - 추천: &platform=naver | &platform=cafe24
    // - 호환: &naver=1 또는 &cafe24=1
    // - 추가(1A): 주소창 마스킹 후 새로고침을 대비해 k=naver_xxx / k=cafe24_xxx 를 인식
    function getMaskedPlatformFromK() {
        try {
            var url = new URL(window.location.href);
            var k = (url.searchParams.get('k') || '').toLowerCase();
            if (k.indexOf('naver_') === 0) return 'naver';
            if (k.indexOf('cafe24_') === 0) return 'cafe24';
            return '';
        } catch (e) {
            return '';
        }
    }
    function getExternalPlatform() {
        try {
            var url = new URL(window.location.href);
            var sp = url.searchParams;
            var platform = (sp.get('platform') || '').toLowerCase();
            if (platform === 'naver' || platform === 'cafe24') {
                return platform;
            }
            if (sp.get('naver') === '1') return 'naver';
            if (sp.get('cafe24') === '1') return 'cafe24';
            // 마스킹된 k에서 복원
            return getMaskedPlatformFromK() || '';
        } catch (e) {
            return getMaskedPlatformFromK() || '';
        }
    }
    editor.externalPlatform = getExternalPlatform(); // 'naver' | 'cafe24' | ''

    // 외부 모드 진입 판단:
    // - 원본 URL: orderMode=external
    // - 마스킹 URL(1A): k=naver_xxx 또는 k=cafe24_xxx 이면 외부모드로 간주
    if (getUrlOrderModeIsExternal() || !!getMaskedPlatformFromK()) {
        editor.orderMode = 'external';
    }

    // ✅ URL 노출 최소화(프론트 단): 외부몰 모드라면 주소창에서 orderMode/naver/cafe24 파라미터를 제거하고
    // goodsNo 뒤에 임의의 난수 파라미터(k=...)만 남긴다. (1A: k에 플랫폼 접두어를 넣어 새로고침 시 복원)
    (function maskExternalOrderUrlParams() {
        try {
            if (editor.orderMode !== 'external') return;
            var url = new URL(window.location.href);
            var goodsNo = url.searchParams.get('goodsNo') || url.searchParams.get('goods_no');
            if (!goodsNo) return;

            function rand(len) {
                var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                var out = '';
                for (var i = 0; i < len; i++) {
                    out += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return out;
            }

            var prefix = '';
            if (editor.externalPlatform === 'naver') prefix = 'naver_';
            else if (editor.externalPlatform === 'cafe24') prefix = 'cafe24_';

            var k = prefix + rand(24);
            var masked = url.origin + url.pathname
                + '?goodsNo=' + encodeURIComponent(goodsNo)
                + '&k=' + encodeURIComponent(k);
            window.history.replaceState({}, document.title, masked);
        } catch (e) {
            // ignore
        }
    })();

    // 외부몰 주문 팝업: 첨부 이미지(다중) 상태
    // [{ name, type, size, dataUrl, rawText? }]
    editor.externalOrderImages = [];

    // ✅ 첨부 이미지 미리보기(모달/오버레이): 새 창 열기 없이 현재 페이지에서 확대 확인
    var __externalPreviewIndex = 0;
    function __externalPreviewList() {
        try {
            return (editor && editor.externalOrderImages && editor.externalOrderImages.length)
                ? editor.externalOrderImages
                : [];
        } catch (e) {
            return [];
        }
    }

    function ensureExternalImagePreviewModal() {
        var id = 'externalOrderImagePreviewModal';
        var modal = document.getElementById(id);
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = id;
        modal.style.cssText = [
            'display:none',
            'position:fixed',
            'inset:0',
            'z-index:99999',
            'background:rgba(0,0,0,.72)',
            'align-items:center',
            'justify-content:center',
            'padding:20px'
        ].join(';');

        var box = document.createElement('div');
        box.style.cssText = [
            'position:relative',
            'max-width:92vw',
            'max-height:92vh',
            'background:#111',
            'border-radius:10px',
            'overflow:hidden',
            'box-shadow:0 10px 30px rgba(0,0,0,.35)'
        ].join(';');

        var btnClose = document.createElement('button');
        btnClose.type = 'button';
        btnClose.textContent = '×';
        btnClose.setAttribute('aria-label', '닫기');
        btnClose.style.cssText = [
            'position:absolute',
            'top:8px',
            'right:10px',
            'width:34px',
            'height:34px',
            'border:none',
            'border-radius:50%',
            'background:rgba(255,255,255,.14)',
            'color:#fff',
            'font-size:22px',
            'cursor:pointer',
            'line-height:34px'
        ].join(';');

        var img = document.createElement('img');
        img.id = id + '__img';
        img.alt = 'preview';
        img.style.cssText = [
            'display:block',
            'max-width:92vw',
            'max-height:92vh',
            'width:auto',
            'height:auto',
            'object-fit:contain'
        ].join(';');

        btnClose.addEventListener('click', function () {
            modal.style.display = 'none';
        });
        modal.addEventListener('click', function (e) {
            // 클릭한 곳이 배경(오버레이)일 때만 닫기
            if (e.target === modal) modal.style.display = 'none';
        });
        // 마우스 휠로 이전/다음 이미지 전환
        modal.addEventListener('wheel', function (e) {
            try {
                var list = __externalPreviewList();
                if (!list.length) return;
                e.preventDefault();
                var dir = (e.deltaY > 0) ? 1 : -1;
                __externalPreviewIndex = (__externalPreviewIndex + dir + list.length) % list.length;
                var imgEl = document.getElementById('externalOrderImagePreviewModal__img');
                if (imgEl) imgEl.src = list[__externalPreviewIndex].dataUrl;
            } catch (err) {}
        }, { passive: false });

        if (!document.__externalOrderPreviewEscBound) {
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    var m = document.getElementById('externalOrderImagePreviewModal');
                    if (m && m.style.display !== 'none') {
                        m.style.display = 'none';
                    }
                }
            });
            document.__externalOrderPreviewEscBound = true;
        }

        box.appendChild(btnClose);
        box.appendChild(img);
        modal.appendChild(box);
        document.body.appendChild(modal);
        return modal;
    }

    function openExternalImagePreview(dataUrl, index) {
        var modal = ensureExternalImagePreviewModal();
        var img = document.getElementById('externalOrderImagePreviewModal__img');
        if (!img) return;
        var list = __externalPreviewList();
        if (typeof index === 'number' && list.length) {
            __externalPreviewIndex = (index + list.length) % list.length;
        } else {
            __externalPreviewIndex = 0;
        }
        img.src = dataUrl || (list[__externalPreviewIndex] ? list[__externalPreviewIndex].dataUrl : '');
        modal.style.display = 'flex';
    }

    function renderExternalOrderImagesPreview() {
        var wrap = document.getElementById('externalOrderImagesPreview');
        if (!wrap) return;

        var previewBox = document.getElementById('externalOrderImagePreviewBox');
        var previewImg = document.getElementById('externalOrderImagePreviewImg');
        var previewEmpty = document.getElementById('externalOrderImagePreviewEmpty');

        function setPreview(idx) {
            try {
                var list = (editor && editor.externalOrderImages) ? editor.externalOrderImages : [];
                if (!list.length || !previewImg || !previewEmpty) return;
                var safeIdx = (typeof idx === 'number' && idx >= 0) ? idx : 0;
                if (!list[safeIdx]) safeIdx = 0;

                var item = list[safeIdx] || list[0];
                previewImg.src = item.dataUrl;
                previewImg.style.display = 'block';
                previewEmpty.style.display = 'none';
                editor.__externalOrderPreviewSelectedIndex = safeIdx;

                // ✅ 썸네일 active 표시 동기화
                try {
                    var all = wrap.querySelectorAll('.externalOrderThumbItem');
                    all.forEach(function (el) { el.classList.remove('is-active'); });
                    var el2 = wrap.querySelector('.externalOrderThumbItem[data-idx="' + safeIdx + '"]');
                    if (el2) el2.classList.add('is-active');
                } catch (e) {}
            } catch (e) {}
        }

        wrap.innerHTML = '';
        if (!editor.externalOrderImages || !editor.externalOrderImages.length) {
            // 빈 상태
            try {
                if (previewImg) previewImg.style.display = 'none';
                if (previewEmpty) previewEmpty.style.display = 'block';
            } catch (e) {}
            return;
        }

        editor.externalOrderImages.forEach(function (img, idx) {
            var item = document.createElement('div');
            item.className = 'externalOrderThumbItem';
            item.setAttribute('data-idx', String(idx));
            item.style.cssText = 'position:relative;width:64px;height:64px;border:1px solid #C8E3FF;border-radius:6px;overflow:hidden;background:#fff;box-sizing:border-box;';

            var thumb = document.createElement('img');
            thumb.src = img.dataUrl;
            thumb.alt = img.name || ('image_' + idx);
            thumb.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;cursor:pointer;';
            // ✅ 클릭 시: 오른쪽 미리보기만 갱신
            thumb.addEventListener('click', function (e) {
                try {
                    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
                    setPreview(idx);
                } catch (err) {}
            });
            item.appendChild(thumb);

            wrap.appendChild(item);
        });

        // ✅ 기본: 첫 이미지(또는 마지막 선택) 프리뷰 표시
        setPreview(editor.__externalOrderPreviewSelectedIndex || 0);

        // ✅ 오른쪽 프리뷰 클릭 시: 오버레이 미리보기 + 휠 안내(1회)
        if (previewBox && !previewBox.__boundClick) {
            previewBox.style.cursor = 'pointer';
            previewBox.addEventListener('click', function () {
                try {
                    var list = (editor && editor.externalOrderImages && editor.externalOrderImages.length)
                        ? editor.externalOrderImages
                        : [];
                    if (!list.length) return;
                    var idx = editor.__externalOrderPreviewSelectedIndex || 0;
                    if (!list[idx]) idx = 0;
                    openExternalImagePreview(list[idx].dataUrl, idx);

                    if (!editor.__wheelTipShown) {
                        editor.__wheelTipShown = true;
                        setTimeout(function () {
                            alert('마우스 휠로 이미지를 전환할 수 있습니다.');
                        }, 0);
                    }
                } catch (err) {}
            });
            previewBox.__boundClick = true;
        }
    }

    // ✅ 첨부 이미지 영역 클릭 시: 첫 이미지 미리보기 + 안내(한 번만)
    (function bindExternalOrderImagesPreviewClick() {
        var wrap = document.getElementById('externalOrderImagesPreview');
        if (!wrap || wrap.__boundClick) return;

        wrap.addEventListener('click', function (e) {
            try {
                // ✅ 오른쪽 프리뷰 박스가 있으면, 여기서는 아무 것도 하지 않음(중복 동작 방지)
                if (document.getElementById('externalOrderImagePreviewBox')) {
                    return;
                }
                // 삭제 버튼 클릭은 무시
                var t = e && e.target ? e.target : null;
                if (t && (t.tagName === 'BUTTON' || (t.closest && t.closest('button')))) {
                    return;
                }

                var list = (editor && editor.externalOrderImages && editor.externalOrderImages.length)
                    ? editor.externalOrderImages
                    : [];
                if (!list.length) return;

                openExternalImagePreview(list[0].dataUrl, 0);

                if (!editor.__wheelTipShown) {
                    editor.__wheelTipShown = true;
                    // ✅ 먼저 미리보기(오버레이)를 띄운 뒤, 안내 팝업을 노출
                    setTimeout(function () {
                        alert('마우스 휠로 이미지를 전환할 수 있습니다.');
                    }, 0);
                }
            } catch (err) {}
        });

        wrap.style.cursor = 'pointer';
        wrap.__boundClick = true;
    })();

    // ✅ 자동 첨부: 합성 SVG + "이미지 업로드"로 올린 모든 도안(원본 dataUrl)
    // - 합성 SVG: editor.generateCustomizedSVG() 사용
    // - 업로드 도안: editor.uploadedImages[{dataUrl,fileName,...}] 사용 (goods_custom.js에서 누적)
    function buildAutoAttachmentsForExternalOrder(force) {
        try {
            // force=false 이고 이미 외부첨부가 있으면 재생성 생략
            if (!force && editor.externalOrderImages && editor.externalOrderImages.length) {
                return Promise.resolve(true);
            }
        } catch (e) {}

        if (!editor || typeof editor.generateCustomizedSVG !== 'function') {
            alert('커스텀 이미지(SVG) 생성 함수를 찾을 수 없습니다.');
            return Promise.resolve(false);
        }

        // 생성 중 UI(있으면)
        try {
            if (typeof editor.showLoadingOverlay === 'function') {
                editor.showLoadingOverlay('첨부 이미지를 준비하는 중입니다...');
            }
        } catch (e) {}

        var backgroundImg = document.getElementById('backgroundImage');
        var dropdown = document.querySelector('.design-dropdown');
        var items = dropdown ? dropdown.querySelectorAll('.design-dropdown-item') : null;

        // 생성 중 원상복구용
        var originalDesignId = (editor.currentDesignId || 'base');
        var originalBgSrc = backgroundImg ? (backgroundImg.getAttribute('src') || backgroundImg.src || '') : '';
        var originalCanvasState = null;
        try {
            if (editor.canvas && typeof editor.canvas.toJSON === 'function') {
                originalCanvasState = editor.canvas.toJSON();
            }
        } catch (e) {}

        // 현재 편집중 상태 저장 (멀티 디자인 복원용)
        try {
            if (typeof editor.saveDesignState === 'function') {
                editor.saveDesignState(originalDesignId);
            }
        } catch (e) {}

        function safeName(s) {
            s = String(s || '').trim();
            s = s.replace(/[\\\/:*?"<>|]+/g, '_');
            s = s.replace(/\s+/g, ' ');
            return s || 'design';
        }

        function setDesignVisualState(designId, imageSrc) {
            try {
                // clip 규칙: goods_custom.js addToCart 로직과 동일하게 유지
                editor.clipEnabled = (designId !== '4');
                if (typeof editor.applyClipPathForAllObjects === 'function') {
                    editor.applyClipPathForAllObjects();
                }
                if (typeof editor.updateClipOverlayVisibility === 'function') {
                    editor.updateClipOverlayVisibility();
                }
            } catch (e) {}

            try {
                if (backgroundImg) {
                    var src = imageSrc || '';
                    if (designId === 'base' && editor.selectedColorImage) {
                        src = editor.selectedColorImage;
                    }
                    if (src) {
                        backgroundImg.src = src;
                    }
                }
            } catch (e) {}
        }

        function runOne(item, idx) {
            var designId = 'base';
            var imageSrc = '';
            var displayName = '';
            try {
                designId = item.getAttribute('data-design-id') || 'base';
                imageSrc = item.getAttribute('data-image-src') || '';
                displayName = (item.textContent || '').trim();
            } catch (e) {}

            return Promise.resolve()
                .then(function () {
                    // 해당 디자인의 상태로 전환
                    if (typeof editor.restoreDesignState === 'function') {
                        editor.currentDesignId = designId;
                        return editor.restoreDesignState(designId);
                    }
                })
                .then(function () {
                    setDesignVisualState(designId, imageSrc);
                    return editor.generateCustomizedSVG();
                })
                .then(function (svgText) {
                    svgText = String(svgText || '');
                    if (!svgText) {
                        return null;
                    }
                    var fn = 'customized_' + String(idx + 1) + '_' + safeName(displayName || designId) + '.svg';
                    return {
                        name: fn,
                        type: 'image/svg+xml',
                        size: svgText.length,
                        dataUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(svgText),
                        rawText: svgText
                    };
                });
        }

        return Promise.resolve()
            .then(function () {
                var list = [];

                // 디자인 영역 드롭다운이 있으면: 항목 수만큼 합성 SVG 생성
                // (텍스트만 추가한 경우도 canvas에 객체가 있으므로 정상적으로 합성됨)
                if (items && items.length) {
                    var seq = Promise.resolve();
                    Array.from(items).forEach(function (it, idx) {
                        seq = seq.then(function () {
                            return runOne(it, idx);
                        }).then(function (att) {
                            if (att) list.push(att);
                        });
                    });
                    return seq.then(function () { return list; });
                }

                // 드롭다운이 없으면 단일 합성으로 fallback
                return Promise.resolve()
                    .then(function () { return editor.generateCustomizedSVG(); })
                    .then(function (svgText) {
                        svgText = String(svgText || '');
                        if (!svgText) throw new Error('empty svg');
                        list.push({
                            name: 'customized-product.svg',
                            type: 'image/svg+xml',
                            size: svgText.length,
                            dataUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(svgText),
                            rawText: svgText
                        });
                        return list;
                    });
            })
            .then(function (list) {
                list = Array.isArray(list) ? list : [];

                // 업로드한 모든 도안 파일(원본) — 없으면 그냥 넘어감
                var uploaded = (editor.uploadedImages && Array.isArray(editor.uploadedImages)) ? editor.uploadedImages : [];
                uploaded.forEach(function (u, idx) {
                    if (!u || !u.dataUrl) return;
                    var fileName = u.fileName || ('pattern_' + (idx + 1));
                    var dataUrl = String(u.dataUrl);
                    var m = dataUrl.match(/^data:([^;,\s]+)[;,]/i);
                    var mime = (m && m[1]) ? m[1] : '';
                    list.push({
                        name: fileName,
                        type: mime,
                        size: dataUrl.length,
                        dataUrl: dataUrl
                    });
                });

                editor.externalOrderImages = list;
                renderExternalOrderImagesPreview();
                return true;
            })
            .catch(function (e) {
                console.error('[goods_custom_for_naver] auto attachments failed', e);
                alert('첨부 이미지 생성에 실패했습니다. 다시 시도해 주세요.');
                return false;
            })
            .finally(function () {
                // ✅ 원래 편집하던 디자인 상태로 복구 (사용자 편집 화면 영향 최소화)
                try {
                    if (backgroundImg && originalBgSrc) {
                        backgroundImg.src = originalBgSrc;
                    }
                } catch (e) {}
                try {
                    editor.currentDesignId = originalDesignId;
                    if (typeof editor.restoreDesignState === 'function') {
                        editor.restoreDesignState(originalDesignId);
                    } else if (editor.canvas && originalCanvasState) {
                        editor.canvas.clear();
                        editor.canvas.loadFromJSON(originalCanvasState, function () {
                            editor.canvas.renderAll();
                        });
                    }
                    setDesignVisualState(originalDesignId, originalBgSrc);
                } catch (e) {}
                try {
                    if (typeof editor.hideLoadingOverlay === 'function') {
                        editor.hideLoadingOverlay();
                    }
                } catch (e) {}
            });
    }

    function resetExternalOrderImages() {
        editor.externalOrderImages = [];
        var input = document.getElementById('externalOrderImages');
        if (input) input.value = '';
        renderExternalOrderImagesPreview();
    }

    function bindExternalOrderImageInput() {
        var input = document.getElementById('externalOrderImages');
        if (!input || input.__bound) return;

        input.addEventListener('change', function (e) {
            var files = (e.target && e.target.files) ? Array.from(e.target.files) : [];
            if (!files.length) return;

            var MAX_COUNT = 5;
            var MAX_SIZE = 5 * 1024 * 1024; // 5MB

            var remain = Math.max(0, MAX_COUNT - (editor.externalOrderImages ? editor.externalOrderImages.length : 0));
            if (remain <= 0) {
                alert('이미지는 최대 ' + MAX_COUNT + '장까지 업로드 가능합니다.');
                input.value = '';
                return;
            }

            files = files.slice(0, remain);

            var tasks = files.map(function (file) {
                return new Promise(function (resolve) {
                    var isPngJpg = /^image\/(png|jpeg)$/.test(file.type);
                    var isSvg = (file.type === 'image/svg+xml') || /\.svg$/i.test(file.name);

                    if (!isPngJpg && !isSvg) {
                        alert('JPG/PNG/SVG 파일만 업로드 가능합니다.');
                        return resolve(null);
                    }

                    if (file.size > MAX_SIZE) {
                        alert('각 이미지는 5MB 이하만 가능합니다.');
                        return resolve(null);
                    }

                    var reader = new FileReader();
                    reader.onerror = function () { resolve(null); };

                    if (isSvg) {
                        reader.onload = function () {
                            var svgText = String(reader.result || '');
                            // 미리보기용 dataUrl(utf8)
                            var dataUrl = 'data:image/svg+xml;utf8,' + encodeURIComponent(svgText);
                            resolve({
                                name: file.name,
                                type: 'image/svg+xml',
                                size: file.size,
                                dataUrl: dataUrl,
                                rawText: svgText
                            });
                        };
                        reader.readAsText(file);
                    } else {
                        reader.onload = function () {
                            resolve({
                                name: file.name,
                                type: file.type,
                                size: file.size,
                                dataUrl: reader.result
                            });
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });

            Promise.all(tasks).then(function (results) {
                results.filter(Boolean).forEach(function (img) {
                    editor.externalOrderImages.push(img);
                });
                renderExternalOrderImagesPreview();
                input.value = ''; // 같은 파일 재선택 허용
            });
        });

        input.__bound = true;
    }

    // UI 업데이트
    editor.updateOrderModeUI = function () {
        var btnAddToCart = document.getElementById('btnAddToCart');
        var downloadBtn = document.getElementById('btnDownload');
        var notice = document.querySelector('.purchase-notice small');

        if (this.orderMode === 'external') {
            if (btnAddToCart) {
                btnAddToCart.innerHTML = '<i class="icon-cart"></i> 주문 확인';
            }
            // 다운로드 버튼은 현재 UI에서 제거됨(goods_custom.html에서 삭제)
            // 아래 로직은 추후 다시 노출할 때를 대비해 보존
            if (downloadBtn) {
                downloadBtn.style.display = 'flex';
            }
            if (notice) {
                notice.textContent = '외부몰 주문 모드입니다. 디자인을 확인한 후 주문서를 판매자에게 전송하세요.';
            }
        } else {
            if (btnAddToCart) {
                btnAddToCart.innerHTML = '<i class="icon-cart"></i> 장바구니에 추가';
            }
            // 다운로드 버튼은 현재 UI에서 제거됨(goods_custom.html에서 삭제)
            // 아래 로직은 추후 다시 노출할 때를 대비해 보존
            if (downloadBtn) {
                downloadBtn.style.display = 'none';
            }
            if (notice) {
                notice.textContent = '💡 맞춤 상품은 장바구니에 추가되며, 장바구니에서 확인 및 결제하실 수 있습니다';
            }
        }
    };

    // ✅ 외부몰 주문 전 체크: 디자인 영역(드롭다운) 각 항목에 "이미지 또는 텍스트"가 존재하는지 확인
    function hasCanvasRealContent() {
        try {
            if (!editor || !editor.canvas || typeof editor.canvas.getObjects !== 'function') return false;
            var canvas = editor.canvas;
            var objects = canvas.getObjects() || [];
            return objects.some(function (o) {
                return o !== canvas.backgroundImage &&
                    o.customType !== 'clipOverlay' &&
                    o.customType !== 'removeTag';
            });
        } catch (e) {
            return false;
        }
    }

    function checkAllDesignAreasHasContent() {
        return Promise.resolve().then(function () {
            var dropdown = document.querySelector('.design-dropdown');
            var items = dropdown ? dropdown.querySelectorAll('.design-dropdown-item') : null;

            // 드롭다운이 없으면 현재 캔버스만 검사
            if (!items || !items.length) {
                if (!hasCanvasRealContent()) {
                    alert('이미지 또는 텍스트를 추가해주세요.');
                    return false;
                }
                return true;
            }

            var originalDesignId = editor.currentDesignId || 'base';

            // 현재 작업중인 상태 저장 (전환 중 데이터 유실 방지)
            try {
                if (typeof editor.saveDesignState === 'function') {
                    editor.saveDesignState(originalDesignId);
                }
            } catch (e) {}

            var emptyName = '';
            var seq = Promise.resolve();
            Array.from(items).forEach(function (item) {
                seq = seq.then(function () {
                    if (emptyName) return; // 이미 빈 항목을 찾았으면 중단
                    var designId = 'base';
                    var name = '';
                    try {
                        designId = item.getAttribute('data-design-id') || 'base';
                        name = (item.textContent || '').trim() || designId;
                    } catch (e) {
                        name = designId;
                    }

                    if (typeof editor.restoreDesignState === 'function') {
                        editor.currentDesignId = designId;
                        return editor.restoreDesignState(designId).then(function () {
                            if (!hasCanvasRealContent()) {
                                emptyName = name;
                            }
                        });
                    }

                    // restoreDesignState 가 없으면 현재만 검사 (방어)
                    if (!hasCanvasRealContent()) {
                        emptyName = name;
                    }
                });
            });

            return seq.then(function () {
                // 원래 디자인으로 복구
                try {
                    editor.currentDesignId = originalDesignId;
                } catch (e) {}

                if (typeof editor.restoreDesignState === 'function') {
                    return editor.restoreDesignState(originalDesignId).then(function () {
                        if (emptyName) {
                            alert('디자인 영역 "' + emptyName + '"에 이미지 또는 텍스트를 추가해주세요.');
                            return false;
                        }
                        return true;
                    });
                }

                if (emptyName) {
                    alert('디자인 영역 "' + emptyName + '"에 이미지 또는 텍스트를 추가해주세요.');
                    return false;
                }
                return true;
            });
        });
    }

    // ✅ 외부몰 주문 팝업: 페이지(1/2) 전환
    function setExternalOrderStep(step) {
        var p1 = document.getElementById('externalOrderPage1');
        var p2 = document.getElementById('externalOrderPage2');
        if (!p1 || !p2) return;
        p1.style.display = (step === 1) ? 'block' : 'none';
        p2.style.display = (step === 2) ? 'block' : 'none';
    }

    // 팝업 열기 + "표(테이블)" 형태로 요약해서 표시 (clone 금지)
    editor.showExternalOrderModal = function () {
        var modal = document.getElementById('externalOrderModal');
        if (!modal) {
            console.error('[goods_custom_for_naver] externalOrderModal 을 찾을 수 없습니다.');
            return;
        }

        // 입력 초기화
        var accountIdInput = document.getElementById('externalOrderAccountId');
        var receiverInput = document.getElementById('externalOrderReceiver');
        var emailInput = document.getElementById('externalOrderEmail');
        var contactInput = document.getElementById('externalOrderContact');
        var noteInput = document.getElementById('externalOrderRequestNote');
        if (accountIdInput) {
            accountIdInput.value = '';
            var p = (editor.externalPlatform || '').toLowerCase();
            if (p === 'cafe24') {
                accountIdInput.placeholder = 'Cafe24 아이디를 입력해주세요.';
            } else if (p === 'naver') {
                accountIdInput.placeholder = '네이버 아이디를 입력해주세요.';
            } else {
                accountIdInput.placeholder = '네이버 아이디를 입력해주세요.';
            }
        }
        if (receiverInput) receiverInput.value = '';
        if (emailInput) emailInput.value = '';
        if (contactInput) contactInput.value = '';
        if (noteInput) noteInput.value = '';

        // 항상 1페이지부터 시작
        setExternalOrderStep(1);

        // 1) 현재 선택값 수집
        var selectedColorName = '';
        try {
            var activeColor = document.querySelector('.product-info .color-item.active');
            if (activeColor) {
                selectedColorName = activeColor.getAttribute('title') || '';
            }
        } catch (e) {}

        // 공식 옵션 요약 (일체형/분리형/텍스트 입력)
        var officialLines = [];
        try {
            var officialSelect = document.getElementById('officialOptionSelect');
            if (officialSelect && officialSelect.value) {
                var opt = officialSelect.options[officialSelect.selectedIndex];
                if (opt) officialLines.push((opt.textContent || '').trim());
            } else {
                var selects = document.querySelectorAll('select[name^="optionNo_"]');
                selects.forEach(function (sel) {
                    if (!sel || !sel.value) return;
                    var dl = sel.closest('dl');
                    var label = '';
                    if (dl) {
                        var dt = dl.querySelector('dt');
                        if (dt) label = (dt.textContent || '').replace(/\s+/g, ' ').trim();
                    }
                    var text = (sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : sel.value) || '';
                    text = String(text).replace(/\s+/g, ' ').trim();
                    if (label) officialLines.push(label + ' = ' + text);
                    else officialLines.push(text);
                });

                var textInputs = document.querySelectorAll('input[name^="optionTextInput_"]');
                textInputs.forEach(function (inp) {
                    if (!inp) return;
                    var v = (inp.value || '').trim();
                    if (!v) return;
                    var dl2 = inp.closest('dl');
                    var label2 = '';
                    if (dl2) {
                        var dt2 = dl2.querySelector('dt');
                        if (dt2) label2 = (dt2.textContent || '').replace(/\s+/g, ' ').trim();
                    }
                    officialLines.push((label2 ? (label2 + ' = ') : '') + v);
                });
            }
        } catch (e) {}

        // 추가 옵션 요약 (체크/라디오/컬러팔레트)
        var extraLines = [];
        try {
            var checkedInputs = document.querySelectorAll('.extra-options-section input.extra-option-input:checked');
            checkedInputs.forEach(function (inp) {
                var item = inp.closest('.extra-option-item');
                if (!item) return;
                var labelNode = item.querySelector('.extra-option-label');
                var label = labelNode ? (labelNode.textContent || '').replace(/\s+/g, ' ').trim() : '';
                if (label) extraLines.push(label);
            });

            var activeColors = document.querySelectorAll('.extra-options-section .extra-color-item.active');
            activeColors.forEach(function (c) {
                var item2 = c.closest('.extra-option-item');
                if (!item2) return;
                var labelNode3 = item2.querySelector('.extra-option-label');
                var label3 = labelNode3 ? (labelNode3.textContent || '').replace(/\s+/g, ' ').trim() : '';
                var colorName = c.getAttribute('title') || c.getAttribute('data-color-code') || c.getAttribute('data-color-hex') || '';
                colorName = String(colorName).trim();
                if (label3 && colorName) extraLines.push(label3 + ' = ' + colorName);
                else if (label3) extraLines.push(label3);
            });

            // 去重
            if (Array.isArray(extraLines) && extraLines.length > 1) {
                extraLines = Array.from(new Set(extraLines));
            }
        } catch (e) {}

        // 수량/가격
        var qty = 1;
        try {
            var qtyInput = document.getElementById('quantityInput');
            qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
        } catch (e) {}

        var summaryPrice = '';
        try {
            var summaryPriceEl = document.getElementById('summaryPrice');
            summaryPrice = summaryPriceEl ? (summaryPriceEl.textContent || '').trim() : '';
        } catch (e) {}

        // 2) ✅ 네이버 결제 금액 안내(정확 DOM 기반) 레이아웃 생성
        function esc(s) {
            return String(s || '').replace(/[&<>"']/g, function (m) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);
            });
        }
        function formatWon(n) {
            n = parseInt(n, 10) || 0;
            return n.toLocaleString('ko-KR') + '원';
        }
        function getQty() {
            var q = 1;
            try {
                var qEl = document.getElementById('quantityInput');
                q = qEl ? (parseInt(qEl.value, 10) || 1) : 1;
            } catch (e) { q = 1; }
            if (!q || q < 1) q = 1;
            return q;
        }
        function getLabelFromDl(el) {
            try {
                var dl = el.closest('dl');
                var dt = dl ? dl.querySelector('dt') : null;
                var t = dt ? (dt.textContent || '') : '';
                return t.replace(/\s+/g, ' ').trim();
            } catch (e) { return ''; }
        }
        function getSelectedText(select) {
            if (!select) return '';
            var opt = (select.options && select.selectedIndex >= 0) ? select.options[select.selectedIndex] : null;
            if (!opt) return '';
            var t = (opt.textContent || '').replace(/\s+/g, ' ').trim();
            t = t.replace(/\s*:\s*[+\-]?\s*\d[\d,]*\s*원.*$/i, '').trim();
            t = t.replace(/\[품절\]/g, '').trim();
            return t;
        }

        var qty2 = getQty();

        var goodsName2 = '';
        try {
            if (typeof pageConfig === 'object' && pageConfig && pageConfig.goodsNm) {
                goodsName2 = pageConfig.goodsNm;
            }
        } catch (e) {}

        var basePrice = 0;
        try {
            if (typeof pageConfig === 'object' && pageConfig && pageConfig.goodsPrice != null) {
                basePrice = parseInt(pageConfig.goodsPrice, 10) || 0;
            }
        } catch (e) {}

        var rows = [];

        // 상품 기본
        rows.push({
            title: goodsName2 || '상품',
            sub: (selectedColorName ? (selectedColorName + ' / ' + qty2 + '개') : (qty2 + '개')),
            price: basePrice * qty2
        });

        // 공식 옵션(일체형 우선)
        var officialPerUnit = 0;
        var officialLabel2 = '';
        var officialValue2 = '';
        try {
            var officialSelect2 = document.getElementById('officialOptionSelect');
            if (officialSelect2 && officialSelect2.value) {
                officialLabel2 = getLabelFromDl(officialSelect2) || '옵션';
                officialValue2 = getSelectedText(officialSelect2);
                var opt2 = officialSelect2.options[officialSelect2.selectedIndex];
                officialPerUnit = opt2 ? (parseInt(opt2.getAttribute('data-option-price'), 10) || 0) : 0;
            } else {
                var selects2 = document.querySelectorAll('select[name^="optionNo_"]');
                var lastSel = null;
                Array.from(selects2).forEach(function (s) {
                    if (s && s.value) lastSel = s;
                });
                if (lastSel) {
                    officialLabel2 = getLabelFromDl(lastSel) || '옵션';
                    officialValue2 = getSelectedText(lastSel);
                }
                if (typeof window.getSelectedOfficialOptionPrice === 'function') {
                    officialPerUnit = parseInt(window.getSelectedOfficialOptionPrice(), 10) || 0;
                }
            }
        } catch (e) {}

        if ((officialValue2 && officialValue2 !== '') || officialPerUnit > 0) {
            rows.push({
                title: officialLabel2 || '옵션',
                sub: officialValue2 || '',
                price: officialPerUnit * qty2
            });
        }

        // 텍스트 옵션(입력형) + addPrice (per_order)
        var textAddTotal = 0;
        try {
            var textInputs = document.querySelectorAll('input[name^="optionTextInput_"]');
            Array.from(textInputs).forEach(function (inp) {
                if (!inp) return;
                var v = (inp.value || '').trim();
                if (!v) return;

                var dl = inp.closest('dl');
                var dtLabel = dl ? dl.querySelector('dt .extra-option-label') : null;
                var label = dtLabel ? (dtLabel.textContent || '').replace(/\s+/g, ' ').trim() : '텍스트 옵션';

                var dd = dl ? dl.querySelector('dd') : null;
                var hiddens = dd ? dd.querySelectorAll('input[type="hidden"]') : null;
                var addPrice = 0;
                if (hiddens && hiddens.length) {
                    addPrice = parseInt(hiddens[hiddens.length - 1].value, 10) || 0;
                }

                textAddTotal += addPrice;
                rows.push({
                    title: label,
                    sub: v,
                    price: addPrice
                });
            });
        } catch (e) {}

        // 추가 옵션 (checkbox/radio/color_palette)
        var extraTotal = 0;
        try {
            var extraNodes = document.querySelectorAll('.extra-option-item');
            Array.from(extraNodes).forEach(function (node) {
                if (!node) return;
                var inputType = (node.getAttribute('data-input-type') || '').toLowerCase();
                var priceType = (node.getAttribute('data-price-type') || '').toLowerCase();
                var extraPrice = parseInt(node.getAttribute('data-extra-price') || '0', 10) || 0;
                if (extraPrice <= 0) return;

                var selected = false;
                var sub = '';

                if (inputType === 'checkbox') {
                    var c = node.querySelector('.extra-option-checkbox');
                    selected = !!(c && c.checked);
                    sub = selected ? '선택' : '';
                } else if (inputType === 'radio') {
                    var r = node.querySelector('.extra-option-radio');
                    selected = !!(r && r.checked);
                    sub = selected ? '선택' : '';
                } else if (inputType === 'color_palette') {
                    var act = node.querySelector('.extra-color-item.active');
                    selected = !!act;
                    sub = act ? (act.getAttribute('title') || '') : '';
                }

                if (!selected) return;

                var labelEl = node.querySelector('.extra-option-label');
                var label2 = labelEl ? (labelEl.textContent || '').replace(/\s+/g, ' ').trim() : '추가 옵션';

                var linePrice = (priceType === 'per_order') ? extraPrice : (extraPrice * qty2);
                extraTotal += linePrice;
                rows.push({
                    title: label2,
                    sub: sub,
                    price: linePrice
                });
            });
        } catch (e) {}

        var total = (basePrice + officialPerUnit) * qty2 + textAddTotal + extraTotal;

        var html = '';
        html += '<div style="font-size:13px;">';
        rows.forEach(function (r) {
            html += '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:10px 2px;">';
            html += '  <div style="min-width:0;">';
            // ✅ Pretendard Medium, 12pt(≈16px), #4D5053
            html += '    <div style="font-family:Pretendard, sans-serif;font-weight:500;font-size:16px;color:#4D5053;">' + esc(r.title) + '</div>';
            // ✅ Pretendard Medium, 10pt(≈13px), #9DA0A3
            if (r.sub) {
                html += '    <div style="margin-top:4px;font-family:Pretendard, sans-serif;font-weight:500;font-size:13px;color:#9DA0A3;line-height:1.3;">' + esc(r.sub) + '</div>';
            }
            html += '  </div>';
            // ✅ Pretendard Medium, 12pt(≈16px), #4D5053
            html += '  <div style="flex:0 0 auto;font-family:Pretendard, sans-serif;font-weight:500;font-size:16px;color:#4D5053;">' + formatWon(r.price) + '</div>';
            html += '</div>';
        });
        html += '<div style="border-top:1px dashed #C5C8CB;margin:8px 0;"></div>';
        // ✅ Pretendard Bold, 14pt(≈19px), #25282B
        html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 2px;font-family:Pretendard, sans-serif;font-weight:700;font-size:19px;color:#25282B;">';
        html += '  <div>총 결제 금액</div>';
        html += '  <div>' + formatWon(total) + '</div>';
        html += '</div>';
        html += '</div>';

        var container = document.getElementById('externalOrderCustomizationInfo');
        if (container) {
            container.innerHTML = html;
        }

        // 이미지 업로드 바인딩 + 초기화(매번 새로 시작)
        bindExternalOrderImageInput();
        resetExternalOrderImages();

        // 수동 업로드는 선택사항으로 두되, 외부몰 주문은 자동 첨부가 기본
        // (사용자가 별도로 파일을 고르지 않아도 합성 SVG + 업로드 도안을 자동 첨부)
        buildAutoAttachmentsForExternalOrder(true);

        modal.style.display = 'block';
    };

    // 현재 단계: 데이터 수집만 (서버 전송 X)
    editor.collectExternalOrderData = function () {
        var accountIdInput = document.getElementById('externalOrderAccountId');
        var receiverInput = document.getElementById('externalOrderReceiver');
        var emailInput = document.getElementById('externalOrderEmail');
        var contactInput = document.getElementById('externalOrderContact');
        var noteInput = document.getElementById('externalOrderRequestNote');

        var platformAccountId = accountIdInput ? accountIdInput.value.trim() : '';
        var receiver = receiverInput ? receiverInput.value.trim() : '';
        var email = emailInput ? emailInput.value.trim() : '';
        var contact = contactInput ? contactInput.value.trim() : '';
        var requestNote = noteInput ? noteInput.value.trim() : '';

        if (!receiver) {
            alert('수령인을 입력해주세요.');
            if (receiverInput) receiverInput.focus();
            return null;
        }
        if (!email) {
            alert('이메일을 입력해주세요.');
            if (emailInput) emailInput.focus();
            return null;
        }
        // 이메일 형식 검증
        if (emailInput && typeof emailInput.checkValidity === 'function' && !emailInput.checkValidity()) {
            alert('이메일 형식으로 입력해주세요.');
            emailInput.focus();
            return null;
        }
        // 브라우저에 따라 checkValidity가 약할 수 있어 정규식도 한 번 더 체크
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('이메일 형식으로 입력해주세요.');
            if (emailInput) emailInput.focus();
            return null;
        }
        if (!contact) {
            alert('연락처를 입력해주세요.');
            if (contactInput) contactInput.focus();
            return null;
        }
        // 첨부 이미지 최소 1장 필수
        if (!editor.externalOrderImages || !editor.externalOrderImages.length) {
            alert('첨부 이미지를 1장 이상 업로드해주세요.');
            return null;
        }

        var qty = editor.quantity || parseInt((document.getElementById('summaryQuantity') || {}).textContent || '1', 10) || 1;
        var summaryPriceEl = document.getElementById('summaryPrice');
        var summaryPrice = summaryPriceEl ? summaryPriceEl.textContent : '';
        var infoContainer = document.getElementById('externalOrderCustomizationInfo');

        var goodsName = '';
        try {
            if (typeof pageConfig === 'object' && pageConfig && pageConfig.goodsNm) {
                goodsName = pageConfig.goodsNm;
            }
        } catch (e) {}

        return {
            goods_no: editor.getGoodsNo ? editor.getGoodsNo() : null,
            goods_name: goodsName,
            platform: editor.externalPlatform || '',
            platform_account_id: platformAccountId,
            request_note: requestNote,
            receiver: receiver,
            email: email,
            contact: contact,
            quantity: qty,
            summary_price: summaryPrice,
            custom_info_html: infoContainer ? infoContainer.innerHTML : '',
            images: (editor.externalOrderImages || []).map(function (x) {
                return {
                    name: x.name,
                    type: x.type,
                    size: x.size,
                    dataUrl: x.dataUrl,
                    rawText: x.rawText || null
                };
            })
        };
    };

    // ✅ 수동 토글 버튼 제거: URL 파라미터(orderMode=external)로만 외부모드 진입
    editor.updateOrderModeUI();

    // 拦截 장바구니 버튼：外部模式下弹框
    var btnAddToCart = document.getElementById('btnAddToCart');
    if (btnAddToCart) {
        // 捕获阶段，抢先阻止 goods_custom.js 内 addToCart()
        btnAddToCart.addEventListener('click', function (e) {
            if (editor.orderMode === 'external') {
                e.preventDefault();
                e.stopImmediatePropagation();
                checkAllDesignAreasHasContent().then(function (ok) {
                    if (!ok) return;
                    editor.showExternalOrderModal();
                });
            }
        }, true);
    }

    // 弹框内按钮：当前仅前端提示
    var btnSendExternalOrder = document.getElementById('btnSendExternalOrder');
    if (btnSendExternalOrder) {
        btnSendExternalOrder.addEventListener('click', function () {
            // 최신 상태로 자동 첨부를 먼저 구성한 뒤 전송
            buildAutoAttachmentsForExternalOrder(true).then(function () {
                var data = editor.collectExternalOrderData();
                if (!data) return;

                // 서버로 전송
                try {
                    if (typeof editor.showLoadingOverlay === 'function') {
                        editor.showLoadingOverlay('주문 요청을 전송하는 중입니다...');
                        if (typeof editor.setEditorProcessingState === 'function') {
                            editor.setEditorProcessingState(true);
                        }
                    }
                } catch (e) {}

                // trace 회신은 비활성화됨(보안/정보 노출 방지)
                fetch('../custom/custom_goods_mail.php?mode=sendExternalOrder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }).then(function (res) {
                    return res.text().then(function (text) {
                        var json;
                        try {
                            json = JSON.parse(text);
                        } catch (e) {
                            throw new Error('HTTP ' + res.status + ' 응답이 JSON이 아닙니다: ' + String(text || '').slice(0, 300));
                        }
                        // HTTP 에러 상태라도 본문 JSON을 우선 사용 (서버가 message 를 내려주는 경우)
                        if (!res.ok && json && json.success === true) {
                            // 이상 케이스 방어: http 에러지만 success=true 인 경우는 그대로 성공 처리
                            return json;
                        }
                        json.__httpStatus = res.status;
                        return json;
                    });
                }).then(function (result) {
                    if (result && result.success) {
                        var modal = document.getElementById('externalOrderModal');
                        if (modal) modal.style.display = 'none';
                        // ✅ 强提示：确保用户一定能看到发送成功
                        alert('주문 요청 전송이 완료되었습니다.');
                        if (typeof editor.showSuccessMessage === 'function') {
                            editor.showSuccessMessage(result.message || '주문 요청이 전송되었습니다.');
                        } else {
                            alert(result.message || '주문 요청이 전송되었습니다.');
                        }
                    } else {
                        // ✅ 429: 限流（不做动态倒计时，仅提示剩余秒数）
                        if (result && (result.__httpStatus === 429 || result.retry_after)) {
                            var sec = parseInt(result.retry_after || 0, 10) || 0;
                            var msg = sec > 0
                                ? ('요청이 너무 많습니다. ' + sec + '초 후 다시 시도해 주세요.')
                                : '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.';
                            alert(msg);
                            return;
                        }

                        if (typeof editor.showErrorMessage === 'function') {
                            editor.showErrorMessage((result && result.message) ? result.message : '주문 요청 전송에 실패했습니다.');
                        } else {
                            alert((result && result.message) ? result.message : '주문 요청 전송에 실패했습니다.');
                        }
                    }
                }).catch(function (err) {
                    console.error('[goods_custom_for_naver] sendExternalOrder error', err);
                    if (typeof editor.showErrorMessage === 'function') {
                        editor.showErrorMessage('네트워크 오류가 발생했습니다. 다시 시도해 주세요.');
                    } else {
                        alert('네트워크 오류가 발생했습니다. 다시 시도해 주세요.');
                    }
                }).finally(function () {
                    try {
                        if (typeof editor.hideLoadingOverlay === 'function') {
                            editor.hideLoadingOverlay();
                        }
                        if (typeof editor.setEditorProcessingState === 'function') {
                            editor.setEditorProcessingState(false);
                        }
                    } catch (e) {}
                });
            });
        });
    }

    // ✅ 다음 버튼: 1페이지 필수값 확인 후 2페이지로 이동
    var btnExternalOrderNext = document.getElementById('btnExternalOrderNext');
    if (btnExternalOrderNext && !btnExternalOrderNext.__bound) {
        btnExternalOrderNext.addEventListener('click', function () {
            var receiverInput = document.getElementById('externalOrderReceiver');
            var emailInput = document.getElementById('externalOrderEmail');
            var contactInput = document.getElementById('externalOrderContact');

            var receiver = receiverInput ? receiverInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            var contact = contactInput ? contactInput.value.trim() : '';

            if (!receiver) {
                alert('수령인을 입력해주세요.');
                if (receiverInput) receiverInput.focus();
                return;
            }
            if (!email) {
                alert('이메일을 입력해주세요.');
                if (emailInput) emailInput.focus();
                return;
            }
            if (emailInput && typeof emailInput.checkValidity === 'function' && !emailInput.checkValidity()) {
                alert('이메일 형식으로 입력해주세요.');
                emailInput.focus();
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('이메일 형식으로 입력해주세요.');
                if (emailInput) emailInput.focus();
                return;
            }
            if (!contact) {
                alert('연락처를 입력해주세요.');
                if (contactInput) contactInput.focus();
                return;
            }

            if (!editor.externalOrderImages || !editor.externalOrderImages.length) {
                alert('첨부 이미지를 1장 이상 업로드해주세요.');
                return;
            }

            setExternalOrderStep(2);
        });
        btnExternalOrderNext.__bound = true;
    }

    // ✅ 이전 버튼: 2페이지 -> 1페이지
    var btnExternalOrderPrev = document.getElementById('btnExternalOrderPrev');
    if (btnExternalOrderPrev && !btnExternalOrderPrev.__bound) {
        btnExternalOrderPrev.addEventListener('click', function () {
            setExternalOrderStep(1);
        });
        btnExternalOrderPrev.__bound = true;
    }

    // 弹框关闭
    var btnCloseExternalOrder = document.getElementById('btnCloseExternalOrder');
    if (btnCloseExternalOrder) {
        btnCloseExternalOrder.addEventListener('click', function () {
            var modal = document.getElementById('externalOrderModal');
            if (modal) modal.style.display = 'none';
        });
    }

    var externalModal = document.getElementById('externalOrderModal');
    if (externalModal) {
        externalModal.addEventListener('click', function (e) {
            if (e.target === externalModal) {
                externalModal.style.display = 'none';
            }
        });
    }
});


