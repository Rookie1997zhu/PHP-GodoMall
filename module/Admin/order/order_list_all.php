<div class="page-header js-affix">
    <h3><?php echo end($naviMenu->location); ?>
        <small>취소/환불/반품/교환을 포함한 전체 주문리스트입니다.</small>
    </h3>
    <?php if (!isset($isProvider) && $isProvider != true) { ?>
        <div class="btn-group">
            <a href="order_write.php" class="btn btn-red-line">수기주문 등록</a>
        </div>
    <?php } ?>
</div>

<?php include $layoutOrderSearchForm;// 검색 및 프린트 폼 ?>

<?php include __DIR__ . '/layout_order_custom_list.php'; ?>

<form id="frmOrderStatus" action="./order_ps.php" method="post">
    <input type="hidden" name="mode" value="combine_status_change"/>
    <input type="hidden" id="orderStatus" name="changeStatus" value=""/>
    <div class="table-action-dropdown">
        <div class="table-action mgt0 mgb0">
            <?php if ($search['view'] !== 'order') { ?>
                <div class="pull-left form-inline">
                    <span class="action-title">선택한 주문을</span>
                    <?php echo gd_select_box('orderStatusTop', null, $selectBoxOrderStatus, null, null, '=주문상태='); ?>
                    <button type="submit" class="btn btn-white js-order-status"/>
                    일괄처리</button>
                </div>
            <?php } ?>
            <div class="pull-right">
                <div class="form-inline">
                    <?php if ($search['userHandleAdmFl'] != 'orderGoods') { ?>
                    <div class="dropdown">
                        <button type="button" id="btnSmsLayer" class="btn btn-red js-sms-layer-open dropdown-toggle dropdown-arr" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">SMS발송</button>
                        <ul class="dropdown-menu mgt10" aria-labelledby="btnSmsLayer">
                            <li class="dropdown-item"><a class="js-sms-send" data-type="select" data-opener="order" data-target-selector="input[name*=statusCheck]:checked">선택 주문 배송</a></li>
                            <li class="dropdown-item"><a class="js-sms-send" data-type="search" data-opener="order" data-target-selector="#frmSearchOrder">검색 주문 배송</a></li>
                        </ul>
                    </div>
                    <?php } ?>
                    <?= gd_select_box(
                        'orderPrintMode', null, [
                        'report'         => '주문내역서',
                        'customerReport' => '주문내역서 (고객용)',
                        'reception'      => '간이영수증',
                        'particular'     => '거래명세서',
                        'taxInvoice'     => '세금계산서',
                    ], null, null, '=인쇄 선택=', null
                    ) ?>
                    <input type="button" onclick="order_print_popup($('#orderPrintMode').val(), 'frmOrderPrint', 'frmOrderStatus', 'statusCheck[', <?= $isProvider ? 'true' : 'false' ?>);" value="프린트" class="btn btn-white btn-icon-print"/>
                </div>
            </div>
        </div>
    </div>

    <?php include $layoutOrderList;// 주문리스트 ?>

    <div class="table-action">
        <?php if ($search['view'] !== 'order') { ?>
            <div class="pull-left form-inline">
                <span class="action-title">선택한 주문을</span>
                <?php echo gd_select_box('orderStatusBottom', 'orderStatusBottom', $selectBoxOrderStatus, null, null, '=주문상태='); ?>
                <button type="submit" class="btn btn-white js-order-status"/>
                일괄처리</button>
            </div>
        <?php } ?>
        <div class="pull-right">
            <button type="button" class="btn btn-white btn-icon-excel js-excel-download" data-target-form="frmSearchOrder" data-search-count="<?= $page->recode['total'] ?>" data-total-count="<?= $page->recode['amount'] ?>"
                    data-state-code="<?= $currentStatusCode ?>" data-target-list-form="frmOrderStatus" data-target-list-sno="statusCheck">엑셀다운로드
            </button>
        </div>
    </div>
</form>
<div class="text-center"><?= $page->getPage(); ?></div>

<!-- 커스터마이징 이미지 모달 -->
<div class="modal fade" id="customImageModal" tabindex="-1" role="dialog" aria-labelledby="customImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customImageModalLabel">커스터마이징 이미지</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="customImageContainer" class="custom-image-body" style="display:none;">
                    <img id="customImageDisplay" class="img-fluid" style="max-width: 100%; max-height: 400px;" alt="커스터마이징 이미지">
                </div>
                <div id="customImageError" class="alert alert-danger custom-image-empty" style="display: none;">
                    로드 실패
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="downloadCustomImage">다운로드</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 客户图案 모달 -->
<div class="modal fade" id="customerPatternModal" tabindex="-1" role="dialog" aria-labelledby="customerPatternModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerPatternModalLabel">客户图案</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="customerPatternContainer" class="custom-image-body" style="display:none;">
                    <img id="customerPatternDisplay" class="img-fluid" style="max-width: 100%; max-height: 400px;" alt="客户图案">
                </div>
                <div id="customerPatternError" class="alert alert-danger custom-image-empty" style="display: none;">
                    加载失败
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="downloadCustomerPattern">下载</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/orderList.js?ts=<?= time(); ?>"></script>

<!-- 커스터마이징 이미지 관련 스타일 -->
<style>
.customization-image-container {
    text-align: center;
    padding: 5px;
}

.customization-thumbnail {
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.customization-thumbnail:hover {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
}

.customization-actions {
    margin-top: 5px;
}

.customization-actions .btn {
    padding: 2px 6px;
    font-size: 11px;
}

.customization-actions .btn i {
    font-size: 10px;
}

.text-muted {
    color: #6c757d !important;
    font-style: italic;
}
</style>

<!-- 커스터마이징 이미지 관련 스크립트 -->
<script type="text/javascript">
$(document).ready(function () {
    $('#downloadCustomImage').on('click', function () {
        var $modal = $('#customImageModal');
        downloadBase64File($modal.data('image-base64'), 'order_' + ($modal.data('order-no') || '') + '_custom.png');
    });

    $('#downloadCustomerPattern').on('click', function () {
        var $modal = $('#customerPatternModal');
        downloadBase64File($modal.data('image-base64'), 'customer_pattern_' + ($modal.data('order-no') || '') + '.png');
    });
});

function downloadBase64File(base64String, fileName) {
    if (!base64String) {
        return;
    }

    try {
        var mimeType = 'application/octet-stream';
        var base64Data = base64String;

        if (base64String.indexOf('base64,') !== -1) {
            var matches = base64String.match(/^data:(.*);base64,(.*)$/);
            if (matches && matches.length === 3) {
                mimeType = matches[1] || mimeType;
                base64Data = matches[2];
            } else {
                base64Data = base64String.split('base64,')[1];
        }
        }

        var byteCharacters = atob(base64Data);
        var byteNumbers = new Array(byteCharacters.length);
        for (var i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        var byteArray = new Uint8Array(byteNumbers);
        var blob = new Blob([byteArray], { type: mimeType });
        var url = URL.createObjectURL(blob);

    var link = document.createElement('a');
        link.href = url;
        link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Base64 다운로드 실패', error);
}
}
</script>
