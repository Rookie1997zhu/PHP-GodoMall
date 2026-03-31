/**
 * cart.html 定制功能脚本
 *
 * 无模板变量依赖，可完整外部化。
 * 注：gd_cart_process / gd_order_all 等官方函数内部仍调用
 *     removeCustomDesignCacheForCartSnos，该函数在此文件声明后全局可用。
 *
 * 包含：
 *   1. removeCustomDesignCacheForCartSnos  - 删除定制本地缓存
 *   2. viewCustomizationDetail             - 打开定制详情旧版弹窗
 *   3. closeCustomModal / updateCustomModalImage - 弹窗控制
 *   4. loadCustomizationImagesViaAjax      - Ajax 加载定制图片缩略图
 *   5. renderCustomizationCell             - 渲染「내 디자인」单元格
 *   6. renderPatternCell                   - 渲染「업로드 이미지」单元格
 *   7. .js-custom-image-preview 点击 → 图片放大预览弹窗
 *   8. #customDetailModal .modal_nav 点击 → 图片切换
 */

/* =====================================================
 * 1. 删除定制本地缓存
 * ===================================================== */

/**
 * 根据 cartSno 列表删除对应的定制本地缓存
 * @param {Array} cartSnos 购物车 SNO 列表
 */
function removeCustomDesignCacheForCartSnos(cartSnos) {
    if (!window.localStorage || !Array.isArray(cartSnos)) {
        return;
    }
    for (var i = 0; i < cartSnos.length; i++) {
        var snoNum = parseInt(cartSnos[i], 10);
        if (!isNaN(snoNum) && snoNum > 0) {
            var key = 'custom_design_' + snoNum;
            localStorage.removeItem(key);
        }
    }
}

/* =====================================================
 * 2. 定制详情旧版弹窗（viewCustomizationDetail）
 * ===================================================== */

function viewCustomizationDetail(cartSno) {
    $.ajax({
        url: './cart_custom_detail.php',
        type: 'GET',
        data: { sno: cartSno },
        success: function(response) {
            var modalHtml = '<div id="customDetailModal" class="custom_detail_modal">' +
                '<div class="modal_content">' +
                '<span class="close" onclick="closeCustomModal()" style="position:absolute;top:10px;right:10px;width:32px;height:32px;line-height:32px;text-align:center;background:#ff5722;color:#fff;border-radius:50%;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.2);transition:background 0.2s;">&times;</span>' +
                response +
                '</div></div>';

            $('body').append(modalHtml);
            $('#customDetailModal').show();
        },
        error: function() {
            alert('정제 정보를 불러올 수 없습니다.');
        }
    });
}

function closeCustomModal() {
    $('#customDetailModal').remove();
}

function updateCustomModalImage($modal, targetIndex) {
    var slides = $modal.data('slides');
    if (!slides || slides.length === 0) {
        return;
    }
    var total = slides.length;
    var normalized = ((targetIndex % total) + total) % total;
    var nextSlide = slides[normalized];
    $modal.data('index', normalized);
    $modal.find('.custom_image_zoom img')
        .attr('src', nextSlide.src)
        .attr('alt', nextSlide.alt || '미리보기');
}

$(document).on('click', '#customDetailModal .modal_nav', function (e) {
    try {
        e.preventDefault();
        var $modal = $('#customDetailModal');
        var slides = $modal.data('slides');
        if (!slides || slides.length <= 1) {
            return;
        }
        var direction = parseInt($(this).data('direction'), 10) || 0;
        updateCustomModalImage($modal, ($modal.data('index') || 0) + direction);
    } catch (err) {
        console.warn('定制图片切换失败', err);
    }
});

/* =====================================================
 * 3. Ajax 加载定制图片缩略图
 * ===================================================== */

$(document).ready(function () {
    loadCustomizationImagesViaAjax();
});

/**
 * 通过 AJAX 加载定制图片并渲染到购物车表格
 */
function loadCustomizationImagesViaAjax() {
    try {
        var cartSnos = [];
        $('.order_table_type tbody .td_customization_image').each(function () {
            var sno = $(this).data('cart-sno');
            if (sno && !isNaN(parseInt(sno, 10))) {
                cartSnos.push(parseInt(sno, 10));
            }
        });

        if (cartSnos.length === 0) {
            $('.td_customization_image .js-custom-loading').text('-');
            $('.td_customer_pattern .js-pattern-loading').text('-');
            return;
        }

        console.log('✅ [cart.html] 准备 AJAX 请求定制图片，cartSnos:', cartSnos);

        $.ajax({
            url: '../custom/goods_custom.php?mode=cartExtraPrice',
            type: 'POST',
            contentType: 'application/json; charset=UTF-8',
            data: JSON.stringify({ cart_snos: cartSnos }),
            dataType: 'json',
            success: function (response) {
                console.log('✅ [cart.html] AJAX 响应:', response);

                if (!response.success || !response.data) {
                    $('.td_customization_image .js-custom-loading').text('-');
                    $('.td_customer_pattern .js-pattern-loading').text('-');
                    return;
                }

                var data = response.data;

                $('.order_table_type tbody .td_customization_image').each(function () {
                    var $cell = $(this);
                    var sno = $cell.data('cart-sno');
                    var goodsNo = $cell.data('goods-no') || '';
                    var rowData = data[sno] || {};
                    renderCustomizationCell($cell, rowData.customization_image, goodsNo, sno);
                });

                $('.order_table_type tbody .td_customer_pattern').each(function () {
                    var $cell = $(this);
                    var sno = $cell.data('cart-sno');
                    var rowData = data[sno] || {};
                    renderPatternCell($cell, rowData.customer_pattern_image);
                });

                // 注意：不清理 localStorage，保留给 order_end.html 写入定制订单
            },
            error: function (xhr, status, error) {
                console.warn('❌ [cart.html] AJAX 请求失败:', status, error);
                $('.td_customization_image .js-custom-loading').text('-');
                $('.td_customer_pattern .js-pattern-loading').text('-');
            }
        });
    } catch (e) {
        console.warn('定制图片 AJAX 加载失败', e);
        $('.td_customization_image .js-custom-loading').text('-');
        $('.td_customer_pattern .js-pattern-loading').text('-');
    }
}

/* =====================================================
 * 4. 渲染「내 디자인」单元格
 * ===================================================== */

/**
 * @param {jQuery} $cell - td.td_customization_image
 * @param {string|null} customizationImage - JSON 数组字符串或单个 Base64
 * @param {string} goodsNo - 商品编号
 * @param {string|number} cartSno - 购物车 sno
 */
function renderCustomizationCell($cell, customizationImage, goodsNo, cartSno) {
    if (!customizationImage || customizationImage === 'null') {
        $cell.html('-');
        return;
    }

    var parsedImages = [];
    if (typeof customizationImage === 'string') {
        var trimmed = customizationImage.trim();
        if (trimmed.charAt(0) === '[') {
            try {
                parsedImages = JSON.parse(trimmed);
            } catch (err) {
                console.warn('解析 customization_image JSON 失败，当作单图处理', err);
                parsedImages = [customizationImage];
            }
        } else {
            parsedImages = [customizationImage];
        }
    }

    if (!parsedImages || parsedImages.length === 0) {
        $cell.html('-');
        return;
    }

    var firstImage = parsedImages[0];
    var badge = parsedImages.length > 1
        ? '<span style="position:absolute;top:-5px;left:-5px;background:#4caf50;color:#fff;font-size:10px;padding:2px 4px;border-radius:3px;">+' + (parsedImages.length - 1) + '</span>'
        : '';

    var editUrl = '../custom/goods_custom.php?goodsNo=' + encodeURIComponent(goodsNo);
    if (cartSno) {
        editUrl += '&cartSno=' + encodeURIComponent(cartSno);
    }

    $cell.html(
        '<span class="custom_thumb" style="position:relative;">' +
            '<a href="#" class="js-custom-image-preview js-multi-custom" data-images="' + encodeURIComponent(JSON.stringify(parsedImages)) + '">' +
                '<img src="' + firstImage + '" alt="내 디자인" style="max-width:60px;max-height:60px;border:1px solid #ddd;border-radius:4px;" />' +
            '</a>' +
            badge +
        '</span>' +
        '<div class="custom_edit_button_wrap">' +
            '<a href="' + editUrl + '" ' +
               'class="btn_edit_custom_design" ' +
               'style="display:inline-block;margin-top:6px;padding:4px 10px;background:#1c64f2;color:#ffffff;font-size:11px;border-radius:4px;text-decoration:none;">' +
                '편집하기' +
            '</a>' +
        '</div>'
    );
}

/* =====================================================
 * 5. 渲染「업로드 이미지」单元格
 * ===================================================== */

/**
 * @param {jQuery} $cell - td.td_customer_pattern
 * @param {string|null} customerPatternImage - JSON 数组字符串或单个 Base64
 */
function renderPatternCell($cell, customerPatternImage) {
    if (!customerPatternImage || customerPatternImage === 'null') {
        $cell.html('-');
        return;
    }

    var parsedImages = [];
    if (typeof customerPatternImage === 'string') {
        var trimmed = customerPatternImage.trim();
        if (trimmed.charAt(0) === '[') {
            try {
                parsedImages = JSON.parse(trimmed);
            } catch (err) {
                console.warn('解析 customer_pattern_image JSON 失败，当作单图处理', err);
                parsedImages = [customerPatternImage];
            }
        } else {
            parsedImages = [customerPatternImage];
        }
    }

    if (!parsedImages || parsedImages.length === 0) {
        $cell.html('-');
        return;
    }

    var firstImage = parsedImages[0];
    var badge = parsedImages.length > 1
        ? '<span style="position:absolute;top:-5px;left:-5px;background:#4caf50;color:#fff;font-size:10px;padding:2px 4px;border-radius:3px;">+' + (parsedImages.length - 1) + '</span>'
        : '';

    $cell.html(
        '<span class="custom_thumb" style="position:relative;">' +
            '<a href="#" class="js-custom-image-preview js-multi-pattern" data-images="' + encodeURIComponent(JSON.stringify(parsedImages)) + '">' +
                '<img src="' + firstImage + '" alt="업로드 이미지" style="max-width:60px;max-height:60px;border:1px solid #ddd;border-radius:4px;" />' +
            '</a>' +
            badge +
        '</span>'
    );
}

/* =====================================================
 * 6. 图片放大预览弹窗（.js-custom-image-preview 点击）
 * ===================================================== */

$(document).on('click', '.js-custom-image-preview', function (e) {
    try {
        e.preventDefault();
        var clicked = this;
        var slides = [];
        var currentIndex = 0;

        var $row = $(clicked).closest('tr');

        // 1. 获取 내 디자인 多图
        var customImages = [];
        var $multiCustom = $row.find('.td_customization_image .js-multi-custom');
        if ($multiCustom.length) {
            var customImagesJson = $multiCustom.data('images');
            if (customImagesJson) {
                try {
                    customImages = JSON.parse(decodeURIComponent(customImagesJson));
                } catch (err) {
                    console.warn('내 디자인 多图数据解析失败，将忽略多图', err);
                    customImages = [];
                }
            }
        } else {
            var customizationSrc = $row.find('.td_customization_image .js-custom-image-preview').data('src');
            if (customizationSrc) {
                customImages = [customizationSrc];
            }
        }

        if (customImages && customImages.length > 0) {
            customImages.forEach(function (imgSrc, idx) {
                var customIndex = slides.push({
                    src: imgSrc,
                    alt: '내 디자인 #' + (idx + 1)
                }) - 1;

                if ($(clicked).closest('.td_customization_image').length && idx === 0) {
                    currentIndex = customIndex;
                }
            });
        }

        // 2. 获取所有图案图片
        var patternImages = [];
        var $multiPattern = $row.find('.js-multi-pattern');
        if ($multiPattern.length) {
            var imagesJson = $multiPattern.data('images');
            if (imagesJson) {
                try {
                    patternImages = JSON.parse(decodeURIComponent(imagesJson));
                } catch (err) {
                    console.warn('多图数据解析失败', err);
                }
            }
        }

        if (patternImages.length === 0) {
            var patternSrc = $row.find('.td_customer_pattern .js-custom-image-preview').data('src');
            if (patternSrc) {
                patternImages = [patternSrc];
            }
        }

        for (var i = 0; i < patternImages.length; i++) {
            var patternIndex = slides.push({
                src: patternImages[i],
                alt: '패턴 이미지 ' + (i + 1)
            }) - 1;

            if ($(clicked).closest('.td_customer_pattern').length && i === 0) {
                currentIndex = patternIndex;
            }
        }

        if (slides.length === 0) {
            return;
        }

        closeCustomModal();
        var currentSlide = slides[currentIndex];
        var modalHtml = '' +
            '<div id="customDetailModal" class="custom_detail_modal" style="display:flex;align-items:center;justify-content:center;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;">' +
            '    <div class="modal_content" style="position:relative;background:#ffffff;padding:50px;border-radius:8px;max-width:90%;max-height:90%;overflow:auto;box-shadow:0 10px 30px rgba(0,0,0,0.25);">' +
            '        <span class="close" onclick="closeCustomModal()" style="position:absolute;top:10px;right:10px;width:32px;height:32px;line-height:32px;text-align:center;background:#ff5722;color:#fff;border-radius:50%;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.2);transition:background 0.2s;">&times;</span>' +
            '        <div class="modal_hint" style="margin:0 0 10px 0;color:#666;font-size:13px;text-align:center;">마우스 휠로 이미지를 전환할 수 있습니다. (총 ' + slides.length + '개)</div>' +
            '        <div class="custom_image_zoom" style="text-align:center;">' +
            '            <img src="' + currentSlide.src + '" alt="' + currentSlide.alt + '" style="max-width:100%;height:auto;display:block;" />' +
            '        </div>' +
            '    </div>' +
            '</div>';
        $('body').append(modalHtml);
        var $modal = $('#customDetailModal');
        $modal.data('slides', slides);
        $modal.data('index', currentIndex);

        $('#customDetailModal .modal_content').on('wheel', function (e) {
            var allSlides = $modal.data('slides') || [];
            if (allSlides.length < 2) return;
            e.preventDefault();
            var idx = $modal.data('index') || 0;
            var deltaY = (e.originalEvent && typeof e.originalEvent.deltaY === 'number') ? e.originalEvent.deltaY : 0;
            if (deltaY > 0) {
                idx = (idx + 1) % allSlides.length;
            } else if (deltaY < 0) {
                idx = (idx - 1 + allSlides.length) % allSlides.length;
            } else {
                return;
            }
            $modal.data('index', idx);
            var s = allSlides[idx];
            $modal.find('.custom_image_zoom img').attr('src', s.src).attr('alt', s.alt);
        });
    } catch (err) {
        console.warn('定制图片放大预览失败', err);
    }
});
