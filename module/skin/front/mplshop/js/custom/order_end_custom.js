/**
 * order_end.html 定制图片提交脚本
 *
 * 依赖全局变量（由 order_end.html 内联注入）：
 *   window.CUSTOM_ORDER_NO  - 当前订单号
 *
 * 功能：
 *   - 从 localStorage 读取定制图片（支持多图 JSON / 单图兼容）
 *   - 过滤 Base64 数据，只保留选项信息
 *   - Ajax POST 到 /custom/goods_custom.php?mode=orderImage&action=save
 *   - 保存成功后清理 localStorage
 */
$(function () {
    var orderNo = window.CUSTOM_ORDER_NO;

    if (!orderNo) {
        return;
    }

    var customizationImage = '';
    var customerPatternImage = '';
    var customizationDataJson = '';

    try {
        // 优先读取 내 디자인 多图 JSON 数组
        var customizationImagesJson = localStorage.getItem('customOrderCustomizationImages') || '';
        if (customizationImagesJson) {
            customizationImage = customizationImagesJson;
            try {
                var parsedCustomization = JSON.parse(customizationImagesJson);
                console.log('✅ 订单 내 디자인：多图模式，数量:', Array.isArray(parsedCustomization) ? parsedCustomization.length : 0);
            } catch (parseErr) {
                console.warn('解析 내 디자인 多图JSON失败，将退回单图逻辑', parseErr);
            }
        }

        // 兼容旧的单图数据
        if (!customizationImage) {
            customizationImage = localStorage.getItem('customOrderCustomizationImage') || '';
            if (customizationImage) {
                console.log('✅ 订单 내 디자인：单图模式');
            }
        }

        // 优先读取 고객 패턴 多图JSON数组
        var customerPatternImagesJson = localStorage.getItem('customOrderCustomerPatternImages') || '';
        if (customerPatternImagesJson) {
            customerPatternImage = customerPatternImagesJson;
            console.log('✅ 订单图片：多图模式，数量:', JSON.parse(customerPatternImagesJson).length);
        } else {
            customerPatternImage = localStorage.getItem('customOrderCustomerPatternImage') || '';
            if (customerPatternImage) {
                console.log('✅ 订单图片：单图模式');
            }
        }

        // 读取整单级 customization_data
        customizationDataJson = localStorage.getItem('customOrderCustomizationData') || '';

        // 过滤 customization_data，去除 Base64 相关数据，只保留选项信息
        if (customizationDataJson) {
            try {
                var parsedDataList = JSON.parse(customizationDataJson);
                if (Array.isArray(parsedDataList)) {
                    var cleanedDataList = parsedDataList.map(function(item) {
                        var cleanedItem = {
                            goodsNo: item.goodsNo,
                            quantity: item.quantity,
                            selected_color: item.selected_color,
                            selected_size: item.selected_size,
                            extra_price_total: item.extra_price_total
                        };

                        // 保留选项信息，过滤 canvasJSON / designStates / customerPatternImages 等 Base64 字段
                        if (item.customization_data) {
                            cleanedItem.customization_data = {
                                extraOptions: item.customization_data.extraOptions || null,
                                extra_price_info: item.customization_data.extra_price_info || null,
                                extraoption: item.customization_data.extraoption || '',
                                currentDesignId: item.customization_data.currentDesignId || 'base'
                            };
                        }

                        return cleanedItem;
                    });
                    customizationDataJson = JSON.stringify(cleanedDataList);
                    console.log('✅ customization_data 已过滤 Base64 数据，保留选项信息');
                }
            } catch (parseError) {
                console.warn('过滤 customization_data 失败，使用原始数据', parseError);
            }
        }
    } catch (e) {
        console.warn('读取定制图片数据失败', e);
    }

    if (!customizationImage && !customerPatternImage && !customizationDataJson) {
        console.log('⚠️ 无定制图片或定制数据，跳过保存');
        return;
    }

    console.log('✅ 开始保存订单定制图片，订单号:', orderNo);

    $.ajax({
        url: '/custom/goods_custom.php?mode=orderImage&action=save',
        type: 'POST',
        dataType: 'json',
        data: {
            orderNo: orderNo,
            customizationImage: customizationImage,
            customerPatternImage: customerPatternImage,
            customizationData: customizationDataJson
        }
    }).done(function (response) {
        console.log('✅ 订单图片保存成功:', response);
    }).fail(function (xhr, status, error) {
        console.error('❌ 订单图片保存失败:', error);
    }).always(function () {
        try {
            localStorage.removeItem('customOrderCustomizationImage');
            localStorage.removeItem('customOrderCustomizationImages');
            localStorage.removeItem('customOrderCustomerPatternImage');
            localStorage.removeItem('customOrderCustomerPatternImages');
            localStorage.removeItem('customOrderCustomizationData');
            console.log('✅ 临时图片数据已清理');
        } catch (e) {
            console.warn('清理临时数据失败', e);
        }
    });
});
