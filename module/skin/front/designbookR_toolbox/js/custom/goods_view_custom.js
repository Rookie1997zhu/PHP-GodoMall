/**
 * goods_view.html 定制功能脚本
 *
 * 依赖全局变量（由 goods_view.html 内联注入）：
 *   window.CUSTOM_GOODS_NO  - 当前商品编号
 *
 * 包含：
 *   1. 작업 가이드（制作指南）加载与渲染
 *   2. 商品定制状态检查（is_customizable）
 *   3. goToCustomization() - 跳转定制页面
 */

/* =====================================================
 * 1. 작업 가이드 加载脚本
 * ===================================================== */
(function($) {
    function renderWorkGuidePreview(path) {
        var $container = $('#workGuideContainer');
        var $empty = $('#workGuideEmpty');
        if (!$container.length) {
            return;
        }
        if (!path) {
            $container.find('.work_guide_preview').remove();
            $empty.show();
            return;
        }

        var lowerPath = path.toLowerCase();
        var previewHtml = '';

        if (lowerPath.endsWith('.pdf')) {
            previewHtml = '<iframe class="work_guide_preview" src="' + path + '" style="width:80%; max-width:960px; height:520px; border:none;"></iframe>';
        } else if (lowerPath.endsWith('.jpg') || lowerPath.endsWith('.jpeg') || lowerPath.endsWith('.png') || lowerPath.endsWith('.svg')) {
            previewHtml = '<img class="work_guide_preview" src="' + path + '" alt="작업 가이드" style="max-width:80%; border:1px solid #eee; border-radius:6px;">';
        } else {
            previewHtml = '<div class="work_guide_preview" style="padding:12px; border:1px solid #eee; border-radius:6px; background:#fafafa;">'
                + '<a href="' + path + '" target="_blank" style="color:#0d6efd;">' + path + '</a>'
                + '</div>';
        }

        $container.find('.work_guide_preview').remove();
        $container.append(previewHtml);
        $empty.hide();
    }

    function loadWorkGuide() {
        var goodsNo = window.CUSTOM_GOODS_NO;
        if (!goodsNo) {
            return;
        }

        $.ajax({
            url: '/admin/custom_color.php',
            type: 'POST',
            dataType: 'json',
            data: {
                mode: 'getWorkGuideAPI',
                action: 'getWorkGuideAPI',
                goods_id: goodsNo
            },
            success: function(response) {
                if (response && response.success && response.data && response.data.work_guide_path) {
                    renderWorkGuidePreview(response.data.work_guide_path);
                } else {
                    renderWorkGuidePreview(null);
                }
            },
            error: function() {
                renderWorkGuidePreview(null);
            }
        });
    }

    $(document).ready(function() {
        loadWorkGuide();
    });
})(jQuery);

/* =====================================================
 * 2. 商品定制状态检查
 * ===================================================== */
$(document).ready(function() {
    console.log('========================================');
    console.log('[定制功能调试] 页面加载完成');
    console.log('[定制功能调试] 当前商品编号:', window.CUSTOM_GOODS_NO);
    console.log('[定制功能调试] #customPurchaseSection 元素存在:', $('#customPurchaseSection').length > 0);
    console.log('[定制功能调试] #customPurchaseSection 当前显示状态:', $('#customPurchaseSection').css('display'));
    console.log('========================================');

    loadCustomizationStatus();
});

function loadCustomizationStatus() {
    var goodsNo = window.CUSTOM_GOODS_NO;

    console.log('[定制功能] 开始加载定制状态...');
    console.log('[定制功能] 商品编号:', goodsNo);

    if (!goodsNo) {
        console.error('[定制功能] ❌ 商品编号为空，无法加载定制状态');
        return;
    }

    var ajaxUrl = '/admin/custom_color.php';
    var ajaxData = {
        mode: 'getCustomizationStatusAPI',
        goods_id: goodsNo
    };

    console.log('[定制功能] AJAX 请求 URL:', ajaxUrl);
    console.log('[定制功能] AJAX 请求数据:', ajaxData);

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: ajaxData,
        dataType: 'json',
        beforeSend: function() {
            console.log('[定制功能] 🔄 正在发送 AJAX 请求...');
        },
        success: function(response) {
            console.log('========================================');
            console.log('[定制功能] ✅ AJAX 请求成功');
            console.log('[定制功能] 完整响应数据:', JSON.stringify(response, null, 2));
            console.log('========================================');

            if (response.success && response.data) {
                var data = response.data;
                console.log('[定制功能] 数据对象:', data);
                console.log('[定制功能] is_customizable 值:', data.is_customizable);
                console.log('[定制功能] is_customizable 类型:', typeof data.is_customizable);
                console.log('[定制功能] custom_width:', data.custom_width);
                console.log('[定制功能] custom_height:', data.custom_height);

                if (data.is_customizable == 1 || data.is_customizable == '1' || data.is_customizable === true) {
                    console.log('[定制功能] ✅ 商品已启用定制功能，准备显示按钮');
                    console.log('[定制功能] 显示前状态:', $('#customPurchaseSection').css('display'));

                    $('#customPurchaseSection').show();

                    console.log('[定制功能] 显示后状态:', $('#customPurchaseSection').css('display'));
                    console.log('[定制功能] ✅ 定制按钮已显示');

                    if (data.custom_width && data.custom_height) {
                        var sizeText = data.custom_width + ' x ' + data.custom_height + ' cm';
                        $('#customSizeText').text(sizeText);
                        $('#customSizeInfo').show();
                        console.log('[定制功能] ✅ 显示自定义尺寸:', sizeText);
                    } else {
                        console.log('[定制功能] ℹ️ 未设置自定义尺寸');
                    }
                } else {
                    console.log('[定制功能] ❌ 商品未启用定制功能');
                    console.log('[定制功能] is_customizable 值:', data.is_customizable);
                    $('#customPurchaseSection').hide();
                }
            } else {
                console.error('[定制功能] ❌ 响应数据格式错误');
                console.error('[定制功能] response.success:', response.success);
                console.error('[定制功能] response.data:', response.data);
                console.error('[定制功能] 完整响应:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('========================================');
            console.error('[定制功能] ❌ AJAX 请求失败');
            console.error('[定制功能] 状态:', status);
            console.error('[定制功能] 错误:', error);
            console.error('[定制功能] HTTP 状态码:', xhr.status);
            console.error('[定制功能] 响应文本:', xhr.responseText);
            console.error('========================================');
        },
        complete: function() {
            console.log('[定制功能] 🏁 AJAX 请求完成');
        }
    });
}

/* =====================================================
 * 3. 跳转定制页面
 * ===================================================== */
function goToCustomization(goodsNo) {
    console.log('========================================');
    console.log('[定制跳转] 用户点击了定制按钮');
    console.log('[定制跳转] 传入的商品编号:', goodsNo);
    console.log('========================================');

    if (!goodsNo) {
        console.error('[定制跳转] ❌ 商品编号为空');
        alert('상품 정보가 없습니다.');
        return;
    }

    console.log('[定制跳转] ✅ 商品编号有效:', goodsNo);

    var customUrl = '../custom/goods_custom.php?goodsNo=' + goodsNo;

    var selectedColor = $('#selectedColor').val() || '';
    var selectedSize = $('#selectedSize').val() || '';

    if (selectedColor) {
        customUrl += '&color=' + encodeURIComponent(selectedColor);
        console.log('[定制跳转] ✅ 选择的颜色:', selectedColor);
    } else {
        console.log('[定制跳转] ℹ️ 未选择颜色');
    }
    if (selectedSize) {
        customUrl += '&size=' + encodeURIComponent(selectedSize);
        console.log('[定制跳转] ✅ 选择的尺寸:', selectedSize);
    } else {
        console.log('[定制跳转] ℹ️ 未选择尺寸');
    }

    console.log('========================================');
    console.log('[定制跳转] 🚀 准备跳转到定制页面');
    console.log('[定制跳转] 完整 URL:', customUrl);
    console.log('[定制跳转] 当前页面 URL:', window.location.href);
    console.log('========================================');

    window.location.href = customUrl;
}
