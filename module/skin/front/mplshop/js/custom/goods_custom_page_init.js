// ✅ 页面配置数据（从 customGoodsView 获取）
var rawPageConfig = (document.getElementById('pageConfig').textContent || '').trim();
var pageConfig = rawPageConfig ? JSON.parse(rawPageConfig) : {};
var rawColors = (document.getElementById('colorsData').textContent || '').trim();
var colorsData = rawColors ? JSON.parse(rawColors) : [];
// 追加选项直接从 DOM 构建，不再解析 extraOptionsData 的 JSON
var extraOptionsState = {};
window.extraOptionsState = extraOptionsState;
var officialOptionTextState = {};

// ✅ 尺寸数据已从后端获取，无需 AJAX 动态加载（字段名与数据库一致）
var selectedSize = {
    width: pageConfig.custom_width,
    height: pageConfig.custom_height,
    display: pageConfig.custom_size_display
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeColorOptions();
    bindEvents();
    initializeExtraOptions();
    updateOrderSummary();
});

// ✅ 初始化颜色选项（从 customGoodsView.colors 获取）
function initializeColorOptions() {
    const colorItems = document.querySelectorAll('.color-item');
    colorItems.forEach((item, index) => {
        const hex = item.getAttribute('data-color-hex');
        if (hex) {
            item.style.backgroundColor = hex;
        }
        item.addEventListener('click', function() {
            if (this.classList.contains('soldout')) {
                alert('품절된 색상입니다.');
                return;
            }
            // 移除其他颜色的active类
            colorItems.forEach(c => c.classList.remove('active'));
            // 添加当前颜色的active类
            this.classList.add('active');

            // 如果有SVG图片，更新背景图片
            const svgPath = this.getAttribute('data-svg-path');
            if (svgPath) {
                const bgImage = document.getElementById('backgroundImage');
                if (bgImage) {
                    bgImage.src = svgPath;
                }
            }
        });
    });
}

// ✅ 绑定基础事件（已简化）
function bindEvents() {
    const MAX_QTY = 10;

    const syncQuantityButtons = () => {
        const input = document.getElementById('quantityInput');
        const btnIncrease = document.getElementById('btnIncrease');
        const btnDecrease = document.getElementById('btnDecrease');
        const currentValue = input ? (parseInt(input.value) || 1) : 1;

        if (btnDecrease) {
            btnDecrease.disabled = currentValue <= 1;
        }
        if (btnIncrease) {
            btnIncrease.disabled = currentValue >= MAX_QTY;
        }
    };

    // 数量增加
    const btnIncrease = document.getElementById('btnIncrease');
    if (btnIncrease) {
        btnIncrease.addEventListener('click', function() {
            const input = document.getElementById('quantityInput');
            const currentValue = parseInt(input.value) || 1;
            input.value = Math.min(MAX_QTY, currentValue + 1);
            updateOrderSummary();
            syncQuantityButtons();
        });
    }

    // 数量减少
    const btnDecrease = document.getElementById('btnDecrease');
    if (btnDecrease) {
        btnDecrease.addEventListener('click', function() {
            const input = document.getElementById('quantityInput');
            const currentValue = parseInt(input.value) || 1;
            input.value = Math.max(1, currentValue - 1);
            updateOrderSummary();
            syncQuantityButtons();
        });
    }

    // 数量手动输入
    const quantityInput = document.getElementById('quantityInput');
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            const value = parseInt(this.value) || 1;
            this.value = Math.min(MAX_QTY, Math.max(1, value));
            updateOrderSummary();
            syncQuantityButtons();
        });
    }

    // 初始化时同步一次按钮状态
    syncQuantityButtons();
}

// ✅ 初始化追加选项事件与状态（直接从 DOM 读取 data-* 属性）
function initializeExtraOptions() {
    var nodes = document.querySelectorAll('.extra-option-item');
    if (!nodes.length) {
        return;
    }

    nodes.forEach(function (node) {
        var optionId = String(node.getAttribute('data-option-id') || '');
        if (!optionId) {
            return;
        }

        var state = {
            optionId: optionId,
            optionGroup: node.getAttribute('data-option-group') || '',
            inputType: node.getAttribute('data-input-type') || 'checkbox',
            extraPrice: parseInt(node.getAttribute('data-extra-price') || '0', 10) || 0,
            priceType: node.getAttribute('data-price-type') || 'per_unit',
            isRequired: parseInt(node.getAttribute('data-is-required') || '0', 10) === 1,
            selected: false,
            selectedColor: null
        };

        extraOptionsState[optionId] = state;

        var checkbox = node.querySelector('.extra-option-checkbox');
        if (checkbox) {
            checkbox.addEventListener('change', function () {
                state.selected = !!checkbox.checked;
                updateOrderSummary();
            });
        }

        var radio = node.querySelector('.extra-option-radio');
        if (radio) {
            radio.addEventListener('change', function () {
                Object.keys(extraOptionsState).forEach(function (id) {
                    var s = extraOptionsState[id];
                    if (!s) return;
                    if (s.optionGroup === state.optionGroup && s.inputType === 'radio') {
                        s.selected = (id === optionId);
                        var relatedRadio = document.querySelector(
                            '.extra-option-item[data-option-id="' + id + '"] .extra-option-radio'
                        );
                        if (relatedRadio) {
                            relatedRadio.checked = (id === optionId);
                        }
                    }
                });
                updateOrderSummary();
            });
        }

        var palette = node.querySelector('.extra-color-palette');
        if (palette) {
            var colorItems = palette.querySelectorAll('.extra-color-item');
            colorItems.forEach(function (colorItem) {
                var colorHex = colorItem.getAttribute('data-color-hex');
                if (colorHex) {
                    colorItem.style.backgroundColor = colorHex;
                }
                colorItem.addEventListener('click', function () {
                    var alreadyActive = colorItem.classList.contains('active');
                    colorItems.forEach(function (ci) {
                        ci.classList.remove('active');
                    });

                    if (!alreadyActive || state.isRequired) {
                        colorItem.classList.add('active');
                        state.selected = true;
                        state.selectedColor =
                            colorItem.getAttribute('data-color-code') ||
                            colorItem.getAttribute('data-color-hex') || '';
                    } else {
                        state.selected = false;
                        state.selectedColor = null;
                    }
                    updateOrderSummary();
                });
            });

            // 必选 + 有颜色时，默认选第一种颜色
            if (state.isRequired && colorItems.length > 0) {
                var first = colorItems[0];
                first.classList.add('active');
                state.selected = true;
                state.selectedColor =
                    first.getAttribute('data-color-code') ||
                    first.getAttribute('data-color-hex') || '';
            }
        }
    });
}

// ✅ 更新订单摘要（只显示：商品基础价 + 官方选项价格；不叠加自定义附加选项金额）
function updateOrderSummary() {
    const quantity = parseInt(document.getElementById('quantityInput').value) || 1;
    const basePrice = pageConfig.goodsPrice || 0;

    // 仍然计算 extra（供 addToCart 内的 getExtraOptionsPriceSummaryForRequest 使用），
    // 但本函数不把 extra 金额叠加到展示价格中
    const extra = calculateExtraPrices(quantity);

    // 获取官方选项价格
    const officialOptionPrice = getSelectedOfficialOptionPrice();

    // 单价 = 商品基础价 + 官方选项价（不含任何自定义附加选项金额）
    const unitPrice = basePrice + officialOptionPrice;
    const totalPrice = unitPrice * quantity;

    // 更新数量显示
    const summaryQuantity = document.getElementById('summaryQuantity');
    if (summaryQuantity) {
        summaryQuantity.textContent = quantity;
    }

    // 在 summaryPrice 上仅显示“基础价 + 官方选项价”的合计
    const summaryPriceElement = document.getElementById('summaryPrice');
    if (summaryPriceElement) {
        summaryPriceElement.textContent = formatPrice(totalPrice);
    }
}

// ✅ 获取当前选中的官方选项价格（支持一体型和分离型）
function getSelectedOfficialOptionPrice() {
    // 一体型选项
    var select = document.getElementById('officialOptionSelect');
    if (select) {
        var selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value) {
            return parseInt(selectedOption.getAttribute('data-option-price'), 10) || 0;
        }
        return 0;
    }

    // 分离型选项 - 从 optionSnoInput 解析价格
    var optionSnoInput = document.querySelector('input[name="optionSnoInput"]');
    if (optionSnoInput && optionSnoInput.value) {
        // 格式: sno||price^^value... 提取 price
        var parts = optionSnoInput.value.split('||');
        if (parts.length >= 2) {
            var pricePart = parts[1].split('^^')[0];
            return parseInt(pricePart, 10) || 0;
        }
    }
    return 0;
}

// ✅ 获取当前选中的官方选项 sno（支持一体型和分离型）
function getSelectedOfficialOptionSno() {
    // 一体型选项
    var select = document.getElementById('officialOptionSelect');
    if (select && select.value) {
        return parseInt(select.value, 10) || 0;
    }

    // 分离型选项 - 从 optionSnoInput 解析 sno
    var optionSnoInput = document.querySelector('input[name="optionSnoInput"]');
    if (optionSnoInput && optionSnoInput.value) {
        // 格式: sno||price^^value... 提取 sno
        var parts = optionSnoInput.value.split('||');
        if (parts.length >= 1) {
            return parseInt(parts[0], 10) || 0;
        }
    }
    return 0;
}

// ✅ 官方选项变更处理函数（一体型）
function handleOfficialOptionChange(selectElement) {
    updateOrderSummary();
}

// ✅ 分离型选项变更处理函数（参考 Godomall 原生 gd_goods_view.js）
function handleDivisionOptionChange(selectElement, currentIndex, nextOptionName) {
    var currentIdx = parseInt(currentIndex, 10);
    var optionCntInput = document.querySelector('input[name="optionCntInput"]');
    var optionCnt = optionCntInput ? parseInt(optionCntInput.value, 10) : 0;
    var goodsNo = pageConfig.goodsNo;
    var stockFl = pageConfig.stockFl;

    // 收集已选择的值（数组格式）
    var optionVal = [];
    for (var i = 0; i <= currentIdx; i++) {
        var sel = document.querySelector('select[name="optionNo_' + i + '"]');
        optionVal[i] = sel ? sel.value : '';

        // 如果某层没有选择，禁用后续所有选项
        if (optionVal[i] === '') {
            for (var j = i + 1; j < optionCnt; j++) {
                var nextSel = document.querySelector('select[name="optionNo_' + j + '"]');
                if (nextSel) {
                    nextSel.disabled = true;
                }
            }
            document.querySelector('input[name="optionSnoInput"]').value = '';
            updateOrderSummary();
            return;
        }
    }

    // 清空已选择的选项 sno
    document.querySelector('input[name="optionSnoInput"]').value = '';

    // 通过 AJAX 获取下一层选项（使用数组格式的 optionVal）
    $.post('../goods/goods_ps.php', {
        'mode': 'option_select',
        'optionVal': optionVal,
        'optionKey': currentIdx,
        'goodsNo': goodsNo,
        'mileageFl': 'n'
    }, function(data) {
        // 如果返回了 optionSno，说明已选择完所有层级
        if (typeof data.optionSno === 'string') {
            var optionInfo = data.optionSno.split('||');
            if (data.optionPrice && data.optionPrice[0]) {
                optionInfo[1] = data.optionPrice[0];
            }
            var optionSnoValue = optionInfo.join('||') + '^^' + optionVal.join('/');
            document.querySelector('input[name="optionSnoInput"]').value = optionSnoValue;
            updateOrderSummary();
            return;
        }

        // 还有下一层选项需要加载
        for (var i = 0; i < optionCnt; i++) {
            var sel = document.querySelector('select[name="optionNo_' + i + '"]');
            if (!sel) continue;

            if (i <= data.nextKey) {
                sel.disabled = false;

                // 填充下一层选项
                if (i == data.nextKey && data.nextOption) {
                    sel.innerHTML = '';
                    var addSelectOption = '';

                    for (var j = 0; j < data.nextOption.length; j++) {
                        if (data.optionViewFl && data.optionViewFl[j] === 'y') {
                            if (j === 0) {
                                // 默认提示选项
                                addSelectOption += '<option value="">= ' + nextOptionName + ' 선택';
                                // 最后一层显示价格和库存
                                if ((parseInt(data.nextKey) + 1) == parseInt(optionCnt)) {
                                    addSelectOption += ' : 가격';
                                    if (stockFl === 'y') {
                                        addSelectOption += ' : 재고';
                                    }
                                }
                                addSelectOption += ' =</option>';
                            }

                            // 选项值
                            var isDisabled = false;
                            var soldOutText = '';

                            if (data.optionSellFl && (data.optionSellFl[j] === 'n' || data.optionSellFl[j] === 't')) {
                                isDisabled = true;
                                soldOutText = '[품절]';
                            } else if (stockFl === 'y' && data.stockCnt && parseInt(data.stockCnt[j]) === 0) {
                                isDisabled = true;
                                soldOutText = '[품절]';
                            }

                            addSelectOption += '<option value="' + data.nextOption[j] + '"';
                            if (isDisabled) {
                                addSelectOption += ' disabled="disabled"';
                            }
                            addSelectOption += '>' + data.nextOption[j];

                            // 最后一层显示价格
                            if ((parseInt(data.nextKey) + 1) == parseInt(optionCnt) && data.optionPrice && data.optionPrice[j]) {
                                var price = parseInt(data.optionPrice[j]);
                                if (price !== 0) {
                                    addSelectOption += ' : ' + (price > 0 ? '+' : '') + price.toLocaleString() + '원';
                                }
                            }

                            addSelectOption += soldOutText + '</option>';
                        }
                    }

                    sel.innerHTML = addSelectOption;
                }
            } else {
                sel.disabled = true;
                // 重置后续选项的提示文字
                var prevOptionName = '';
                var prevSel = document.querySelector('select[name="optionNo_' + (i-1) + '"]');
                if (prevSel && prevSel.options[0]) {
                    var match = prevSel.options[0].textContent.match(/=\s*(.+?)\s*선택/);
                    if (match) {
                        prevOptionName = match[1];
                    }
                }
                sel.innerHTML = '<option value="">= ' + prevOptionName + '을 먼저 선택해 주세요 =</option>';
            }
        }

        updateOrderSummary();
    }, 'json');
}

// ✅ 文本输入选项变更处理函数
function handleOptionTextChange(inputElement) {
    var sno = inputElement.getAttribute('data-option-text-sno');
    if (sno) {
        officialOptionTextState[sno] = inputElement.value.trim();
    }
    updateOrderSummary();
}

// 暴露到全局，便于 goods_custom.js 调用
window.getSelectedOfficialOptionSno = getSelectedOfficialOptionSno;
window.getSelectedOfficialOptionPrice = getSelectedOfficialOptionPrice;
window.handleOfficialOptionChange = handleOfficialOptionChange;
window.handleDivisionOptionChange = handleDivisionOptionChange;
window.handleOptionTextChange = handleOptionTextChange;
window.getOfficialOptionTextPayload = function () {
    var payload = {};
    Object.keys(officialOptionTextState).forEach(function (key) {
        var value = officialOptionTextState[key];
        if (typeof value === 'string' && value.trim() !== '') {
            payload[key] = value.trim();
        }
    });
    return payload;
};

// ✅ 计算追加选项价格
function calculateExtraPrices(quantity) {
    var perUnitExtra = 0;
    var perOrderExtra = 0;

    Object.keys(extraOptionsState).forEach(function (optionId) {
        var state = extraOptionsState[optionId];
        if (!state || !state.selected) {
            return;
        }
        var price = state.extraPrice || 0;
        if (price <= 0) {
            return;
        }
        if (state.priceType === 'per_order') {
            perOrderExtra += price;
        } else {
            perUnitExtra += price;
        }
    });

    return {
        perUnitExtra: perUnitExtra,
        perOrderExtra: perOrderExtra
    };
}

// ⭐ 新增：给 goods_custom.js 用的“附加费用”汇总（按当前数量）
function getExtraOptionsPriceSummaryForRequest() {
    var quantityInput = document.getElementById('quantityInput');
    var quantity = quantityInput ? parseInt(quantityInput.value, 10) || 1 : 1;

    var extra = calculateExtraPrices(quantity) || {};
    var perUnit = extra.perUnitExtra || 0;
    var perOrder = extra.perOrderExtra || 0;

    return {
        perUnitExtra: perUnit,
        perOrderExtra: perOrder,
        totalExtraPrice: (perUnit * quantity) + perOrder
    };
}

// ✅ 格式化价格
function formatPrice(price) {
    return price.toLocaleString('ko-KR') + '원';
}

// ✅ 导出当前追加选项选择，供 goods_custom.js 使用
function getExtraOptionsPayload() {
    var payload = {
        options: [],
        colorPalettes: []
    };

    Object.keys(extraOptionsState).forEach(function (optionId) {
        var state = extraOptionsState[optionId];
        if (!state || !state.selected) {
            return;
        }

        payload.options.push({
            option_id: state.optionId,
            option_group: state.optionGroup,
            input_type: state.inputType,
            extra_price: state.extraPrice,
            price_type: state.priceType
        });

        if (state.inputType === 'color_palette' && state.selectedColor) {
            payload.colorPalettes.push({
                option_id: state.optionId,
                selected_color: state.selectedColor
            });
        }
    });

    return payload;
}

// ✅ 将当前已选追加选项转成文本，用于写入 customization_data.extraoption
function getExtraOptionsSummaryText() {
    var lines = [];
    if (!window.extraOptionsState) {
        return '';
    }

    Object.keys(extraOptionsState).forEach(function (optionId) {
        var state = extraOptionsState[optionId];
        if (!state || !state.selected) {
            return;
        }

        var selector = '.extra-option-item[data-option-id="' + optionId + '"]';
        var node = document.querySelector(selector);
        if (!node) {
            return;
        }

        var text = (node.innerText || node.textContent || '').replace(/\s+/g, ' ').trim();
        if (text) {
            lines.push(text);
        }
    });

    return lines.join(' / ');
}

// 暴露到全局，便于其它脚本访问
window.getExtraOptionsPayload = getExtraOptionsPayload;
window.getExtraOptionsSummaryText = getExtraOptionsSummaryText;
window.getExtraOptionsPriceSummaryForRequest = getExtraOptionsPriceSummaryForRequest;

// goods_custom_mobile_app.js 包装用
window.updateOrderSummary = updateOrderSummary;

// ✅ 获取当前选中的尺寸（已简化）
function getSelectedSize() {
    return selectedSize;
}
