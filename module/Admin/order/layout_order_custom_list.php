<?php
$data = isset($data) && is_array($data) ? $data : [];
$orderNos = [];
if (!empty($data) && is_array($data)) {
    foreach ($data as $orderNo => $orderRow) {
        $orderNos[] = $orderNo;
    }
}

/** @var \Custom\Component\CustomOrderExtension $orderExtension */
$orderExtension = \App::load('\\Custom\\Component\\CustomOrderExtension');

$pageSize = 5;
$currentPage = isset($_GET['custom_page']) ? (int)$_GET['custom_page'] : 1;
$currentPage = $currentPage > 0 ? $currentPage : 1;

$totalCount = $orderNos ? $orderExtension->getImagesCountByOrderNos($orderNos) : 0;
$needPagination = $totalCount > $pageSize;

if ($needPagination) {
    $customImages = $orderExtension->getImagesByOrderNosWithPagination($orderNos, $currentPage, $pageSize);
    $totalPages = (int)ceil($totalCount / $pageSize);

    $queryParams = $_GET;
    unset($queryParams['custom_page']);
    $baseQuery = http_build_query($queryParams);
    $baseQuery = $baseQuery ? $baseQuery . '&' : '';
    $pageFile = basename($_SERVER['PHP_SELF']);
    $buildPageUrl = function ($page) use ($baseQuery, $pageFile) {
        return './' . $pageFile . '?' . $baseQuery . 'custom_page=' . $page;
    };
} else {
    $customImages = $orderNos ? $orderExtension->getImagesByOrderNos($orderNos) : [];
    $totalPages = 1;
    $buildPageUrl = null;
}
?><div class="table-title">
    <h4>맞춤 주문 목록</h4>
</div>
<div class="table-responsive">
    <table class="table table-rows">
        <thead>
        <tr>
            <th style="width:120px;">주문번호</th>
            <th style="width:160px;">커스터마이징</th>
            <th style="width:160px;">고객 패턴</th>
            <th style="width:120px;">상세</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($customImages)) { ?>
            <tr>
                <td class="text-center" colspan="4">맞춤 주문 데이터가 없습니다</td>
            </tr>
        <?php } else { ?>
            <?php foreach ($customImages as $orderNo => $imageRow) { ?>
                <tr>
                    <td class="text-center font-num"><?= $orderNo ?></td>
                    <td class="text-center">
                        <?php if (!empty($imageRow['customization_image'])) { ?>
                            <?php
                            // ✅ 检测是否为JSON数组（多图模式）
                            $customData = $imageRow['customization_image'];
                            $isCustomMultiple = false;
                            $customImageList = [];

                            if (is_string($customData) && strpos(trim($customData), '[') === 0) {
                                try {
                                    $decodedCustom = json_decode($customData, true);
                                    if (is_array($decodedCustom) && count($decodedCustom) > 0) {
                                        $isCustomMultiple = true;
                                        $customImageList = $decodedCustom;
                                    }
                                } catch (Exception $e) {
                                    // JSON解析失败，当作单图处理
                                }
                            }
                            ?>

                            <?php if ($isCustomMultiple && !empty($customImageList)) { ?>
                                <!-- 커스터마이징 多图：下拉菜单 -->
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-white dropdown-toggle" data-toggle="dropdown">
                                        다운로드 (<?= count($customImageList) ?>개) <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($customImageList as $idx => $imgBase64) { ?>
                                            <li>
                                                <a href="#"
                                                   class="js-download-custom-image"
                                                   data-image-base64="<?= htmlspecialchars($imgBase64, ENT_QUOTES) ?>"
                                                   data-order-no="<?= $orderNo ?>"
                                                   data-index="<?= $idx + 1 ?>">
                                                    커스터마이징 이미지 <?= $idx + 1 ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <li class="divider"></li>
                                        <li>
                                            <a href="#"
                                               class="js-download-all-customs"
                                               data-order-no="<?= $orderNo ?>"
                                               data-images="<?= htmlspecialchars(json_encode($customImageList), ENT_QUOTES) ?>">
                                                전체 다운로드
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php } else { ?>
                                <!-- 单图：保留原有后端下载方式 -->
                                <a class="btn btn-sm btn-white"
                                   href="/admin/custom/custom_order_image.php?mode=download&amp;type=custom&amp;orderNo=<?= $orderNo ?>">
                                    다운로드
                                </a>
                            <?php } ?>
                        <?php } else { ?>
                            <span class="text-muted">데이터 없음</span>
                        <?php } ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($imageRow['customer_pattern_image'])) { ?>
                            <?php
                            // ✅ 检测是否为JSON数组（多图模式）
                            $patternData = $imageRow['customer_pattern_image'];
                            $isMultiple = false;
                            $imageList = [];
                            
                            if (is_string($patternData) && strpos(trim($patternData), '[') === 0) {
                                try {
                                    $decoded = json_decode($patternData, true);
                                    if (is_array($decoded) && count($decoded) > 0) {
                                        $isMultiple = true;
                                        $imageList = $decoded;
                                    }
                                } catch (Exception $e) {
                                    // JSON解析失败，当作单图处理
                                }
                            }
                            
                            if (!$isMultiple) {
                                $imageList = [$patternData];
                            }
                            ?>
                            
                            <?php if (count($imageList) === 1) { ?>
                                <!-- 单图：直接下载按钮 -->
                                <a class="btn btn-sm btn-white" 
                                   href="/admin/custom/custom_order_image.php?mode=download&amp;type=customer&amp;orderNo=<?= $orderNo ?>">
                                    다운로드
                                </a>
                            <?php } else { ?>
                                <!-- 多图：下拉菜单 -->
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-white dropdown-toggle" data-toggle="dropdown">
                                        다운로드 (<?= count($imageList) ?>개) <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($imageList as $idx => $imgBase64) { ?>
                                            <li>
                                                <a href="#" class="js-download-pattern-image" 
                                                   data-image-base64="<?= htmlspecialchars($imgBase64, ENT_QUOTES) ?>"
                                                   data-order-no="<?= $orderNo ?>"
                                                   data-index="<?= $idx + 1 ?>">
                                                    패턴 이미지 <?= $idx + 1 ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <li class="divider"></li>
                                        <li>
                                            <a href="#" class="js-download-all-patterns" 
                                               data-order-no="<?= $orderNo ?>"
                                               data-images="<?= htmlspecialchars(json_encode($imageList), ENT_QUOTES) ?>">
                                                전체 다운로드
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <span class="text-muted">데이터 없음</span>
                        <?php } ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($imageRow['customization_data'])) { ?>
                            <button type="button"
                                    class="btn btn-sm btn-white js-customization-detail"
                                    data-order-no="<?= $orderNo ?>"
                                    data-customization='<?= htmlspecialchars($imageRow['customization_data'], ENT_QUOTES, 'UTF-8') ?>'>
                                상세
                            </button>
                        <?php } else { ?>
                            <span class="text-muted">데이터 없음</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
</div>

<!-- 定制详情弹窗 -->
<div id="customDetailModalBackdrop" style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:9998;"></div>
<div id="customDetailModal" style="display:none;position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);background:#ffffff;z-index:9999;width:640px;max-width:95%;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.3);">
    <div class="modal-header" style="padding:10px 15px;border-bottom:1px solid #ddd;overflow:hidden;">
        <strong id="customDetailModalTitle">정제 옵션 상세</strong>
        <button type="button" class="close js-custom-detail-close" style="float:right;border:0;background:none;font-size:18px;line-height:1;">&times;</button>
    </div>
    <div class="modal-body" style="padding:15px;max-height:70vh;overflow:auto;">
        <div id="customDetailExtraoption" style="margin-bottom:12px;padding:8px 10px;background:#fff7e6;border-left:4px solid #ff9800;font-weight:bold;color:#d35400;white-space:pre-wrap;word-break:break-all;"></div>
        <pre id="customDetailJson" style="background:#f8f8f8;border:1px solid #eee;padding:10px;font-size:12px;line-height:1.4;white-space:pre-wrap;word-break:break-all;"></pre>
    </div>
</div>
<?php if ($needPagination && $totalPages > 1 && $buildPageUrl !== null) { ?>
    <div class="text-center" style="margin-top:20px;">
        <nav>
            <ul class="pagination pagination-sm">
                <?php if ($currentPage > 1) { ?>
                    <li class="front-page front-page-first"><a href="<?= htmlspecialchars($buildPageUrl(1), ENT_QUOTES, 'UTF-8') ?>">맨앞</a></li>
                    <li class="front-page front-page-prev"><a href="<?= htmlspecialchars($buildPageUrl($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>">이전</a></li>
                <?php } ?>
                <?php for ($pageIdx = 1; $pageIdx <= $totalPages; $pageIdx++) { ?>
                    <?php if ($pageIdx == $currentPage) { ?>
                        <li class="active"><span><?= $pageIdx ?></span></li>
                    <?php } else { ?>
                        <li><a href="<?= htmlspecialchars($buildPageUrl($pageIdx), ENT_QUOTES, 'UTF-8') ?>"><?= $pageIdx ?></a></li>
                    <?php } ?>
                <?php } ?>
                <?php if ($currentPage < $totalPages) { ?>
                    <li class="front-page front-page-next"><a href="<?= htmlspecialchars($buildPageUrl($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>">다음</a></li>
                    <li class="front-page front-page-last"><a href="<?= htmlspecialchars($buildPageUrl($totalPages), ENT_QUOTES, 'UTF-8') ?>">맨뒤</a></li>
                <?php } ?>
            </ul>
        </nav>
    </div>
<?php } ?>

<!-- 多图下载脚本 -->
<script type="text/javascript">
$(document).ready(function() {
    // 下载单个图案
    $(document).on('click', '.js-download-pattern-image', function(e) {
        e.preventDefault();
        var base64 = $(this).data('image-base64');
        var orderNo = $(this).data('order-no');
        var index = $(this).data('index');
        
        if (!base64) {
            alert('이미지 데이터가 없습니다.');
            return;
        }
        
        downloadBase64FileWithFormat(base64, 'customer_pattern_' + orderNo + '_' + index);
    });

    // 定制数据详情查看（弹窗）
    $(document).on('click', '.js-customization-detail', function(e) {
        e.preventDefault();
        var rawData = $(this).data('customization'); // 可能是对象，也可能是字符串
        var orderNo = $(this).data('order-no') || '';

        if (!rawData) {
            alert('정제 옵션 데이터가 없습니다.');
            return;
        }

        var parsed = null;
        var extraText = '';
        var pretty = '';

        try {
            if (typeof rawData === 'string') {
                parsed = JSON.parse(rawData);
            } else if (typeof rawData === 'object') {
                parsed = rawData;
            }

            if (parsed && typeof parsed === 'object') {
                extraText = parsed.extraoption || '';
                pretty = JSON.stringify(parsed, null, 2);
            } else {
                pretty = String(rawData);
            }
        } catch (err) {
            pretty = String(rawData);
        }

        var title = '정제 옵션 상세';
        if (orderNo) {
            title = '주문번호 ' + orderNo + ' 정제 옵션 상세';
        }
        $('#customDetailModalTitle').text(title);
        $('#customDetailExtraoption').text(extraText || '추가 옵션 선택 내용이 없습니다.');
        $('#customDetailJson').text(pretty);

        $('#customDetailModalBackdrop').show();
        $('#customDetailModal').show();
    });

    // 关闭定制详情弹窗
    $(document).on('click', '.js-custom-detail-close, #customDetailModalBackdrop', function(e) {
        e.preventDefault();
        $('#customDetailModal').hide();
        $('#customDetailModalBackdrop').hide();
    });

    // 下载单个 커스터마이징 이미지
    $(document).on('click', '.js-download-custom-image', function(e) {
        e.preventDefault();
        var base64 = $(this).data('image-base64');
        var orderNo = $(this).data('order-no');
        var index = $(this).data('index');

        if (!base64) {
            alert('이미지 데이터가 없습니다.');
            return;
        }

        downloadBase64FileWithFormat(base64, 'customization_' + orderNo + '_' + index);
    });

    // 下载所有图案
    $(document).on('click', '.js-download-all-patterns', function(e) {
        e.preventDefault();
        var orderNo = $(this).data('order-no');
        var images = $(this).data('images');
        
        if (typeof images === 'string') {
            try {
                images = JSON.parse(images);
            } catch (err) {
                alert('이미지 데이터 파싱 실패');
                return;
            }
        }
        
        if (!images || images.length === 0) {
            alert('다운로드할 이미지가 없습니다.');
            return;
        }
        
        // 逐个下载，延迟避免浏览器阻止
        images.forEach(function(base64, idx) {
            setTimeout(function() {
                downloadBase64FileWithFormat(base64, 'customer_pattern_' + orderNo + '_' + (idx + 1));
            }, idx * 500);
        });
        
        alert(images.length + '개 이미지 다운로드를 시작합니다.');
    });

    // 下载所有 커스터마이징 이미지
    $(document).on('click', '.js-download-all-customs', function(e) {
        e.preventDefault();
        var orderNo = $(this).data('order-no');
        var images = $(this).data('images');

        if (typeof images === 'string') {
            try {
                images = JSON.parse(images);
            } catch (err) {
                alert('이미지 데이터 파싱 실패');
                return;
            }
        }

        if (!images || images.length === 0) {
            alert('다운로드할 이미지가 없습니다.');
            return;
        }

        images.forEach(function(base64, idx) {
            setTimeout(function() {
                downloadBase64FileWithFormat(base64, 'customization_' + orderNo + '_' + (idx + 1));
            }, idx * 500);
        });

        alert(images.length + '개 커스터마이징 이미지를 다운로드합니다.');
    });
});

/**
 * Base64下载辅助函数（自动检测格式）
 */
function downloadBase64FileWithFormat(base64String, fileNamePrefix) {
    if (!base64String) {
        console.error('Base64 데이터가 비어있습니다.');
        return;
    }
    
    var extension = 'png';
    var mimeType = 'image/png';
    
    // 检测MIME类型
    if (base64String.indexOf('data:image/svg') !== -1) {
        extension = 'svg';
        mimeType = 'image/svg+xml';
    } else if (base64String.indexOf('data:image/jpeg') !== -1 || base64String.indexOf('data:image/jpg') !== -1) {
        extension = 'jpg';
        mimeType = 'image/jpeg';
    } else if (base64String.indexOf('data:image/gif') !== -1) {
        extension = 'gif';
        mimeType = 'image/gif';
    } else if (base64String.indexOf('data:image/webp') !== -1) {
        extension = 'webp';
        mimeType = 'image/webp';
    }
    
    var fileName = fileNamePrefix + '.' + extension;
    
    try {
        // 分离Base64数据
        var base64Data = base64String.split(',')[1] || base64String;
        var byteString = atob(base64Data);
        var ab = new ArrayBuffer(byteString.length);
        var ia = new Uint8Array(ab);
        
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        
        var blob = new Blob([ab], { type: mimeType });
        var url = URL.createObjectURL(blob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        
        // 清理
        setTimeout(function() {
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }, 100);
        
        console.log('✅ 다운로드 시작:', fileName);
    } catch (error) {
        console.error('❌ 다운로드 실패:', error);
        alert('이미지 다운로드 중 오류가 발생했습니다: ' + error.message);
    }
}
</script>
