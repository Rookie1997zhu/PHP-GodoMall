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
                <div id="customImageContainer">
                    <img id="customImageDisplay" class="img-fluid" style="max-width: 100%; max-height: 400px;" alt="커스터마이징 이미지">
                </div>
                <div id="customImageError" class="alert alert-danger" style="display: none;">
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
$(document).ready(function() {
    // 커스터마이징 이미지 열 추가
    addCustomizationColumn();
    
    // 모달 이벤트 설정
    setupCustomImageModal();
});

// 커스터마이징 이미지 열 추가
function addCustomizationColumn() {
    // 테이블 헤더에 열 추가
    var headerRow = $('.order-list thead tr');
    if (headerRow.length > 0) {
        headerRow.append('<th style="width: 100px; text-align: center;">커스터마이징</th>');
    }
    
    // 각 행에 커스터마이징 이미지 열 추가
    $('.order-list tbody tr').each(function() {
        var orderNo = $(this).find('td:has(a[data-order-no]) a[data-order-no]').text();
        if (orderNo) {
            var customizationCell = $('<td style="text-align: center;"></td>');
            
            // AJAX로 커스터마이징 이미지 확인
            checkCustomizationImage(orderNo, customizationCell);
            
            $(this).append(customizationCell);
        }
    });
}

// 커스터마이징 이미지 확인
function checkCustomizationImage(orderNo, cell) {
    $.ajax({
        url: './order_ps.php',
        type: 'POST',
        data: {
            mode: 'check_customization_image',
            orderNo: orderNo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data && response.data.customization_image) {
                // 커스터마이징 이미지가 있는 경우
                var viewButton = $('<button type="button" class="btn btn-sm btn-primary js-view-custom-image" data-order-no="' + orderNo + '" data-image-url="' + response.data.customization_image + '">보기</button>');
                cell.append(viewButton);
            } else {
                // 커스터마이징 이미지가 없는 경우
                cell.html('&nbsp;');
            }
        },
        error: function() {
            cell.html('&nbsp;');
        }
    });
}

// 커스터마이징 이미지 모달 설정
function setupCustomImageModal() {
    // 보기 버튼 클릭 이벤트
    $(document).on('click', '.js-view-custom-image', function() {
        var orderNo = $(this).data('order-no');
        var imageUrl = $(this).data('image-url');
        
        showCustomImageModal(orderNo, imageUrl);
    });
    
    // 다운로드 버튼 클릭 이벤트
    $('#downloadCustomImage').on('click', function() {
        var orderNo = $('#customImageModal').data('order-no');
        var imageUrl = $('#customImageModal').data('image-url');
        
        if (orderNo && imageUrl) {
            downloadCustomImage(orderNo, imageUrl);
        }
    });
}

// 커스터마이징 이미지 모달 표시
function showCustomImageModal(orderNo, imageUrl) {
    $('#customImageModal').data('order-no', orderNo);
    $('#customImageModal').data('image-url', imageUrl);
    
    $('#customImageDisplay').attr('src', imageUrl);
    $('#customImageContainer').show();
    $('#customImageError').hide();
    
    $('#customImageModal').modal('show');
}

// 커스터마이징 이미지 다운로드
function downloadCustomImage(orderNo, imageUrl) {
    var link = document.createElement('a');
    link.href = imageUrl;
    link.download = 'order_' + orderNo + '_custom.' + getFileExtension(imageUrl);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// 파일 확장자 추출
function getFileExtension(url) {
    var extension = url.split('.').pop().split('?')[0];
    return extension || 'jpg';
}

// 전역 함수로 노출 (다른 파일에서 사용할 수 있도록)
window.showCustomizationImage = showCustomImageModal;
window.downloadCustomizationImage = downloadCustomImage;

// 커스터마이징 이미지 모달 표시
function showCustomImageModal(orderNo, imageUrl) {
    // 모달 데이터 설정
    $('#customImageModal').data('order-no', orderNo);
    $('#customImageModal').data('image-url', imageUrl);
    
    // 이미지 로드
    var img = $('#customImageDisplay');
    var errorDiv = $('#customImageError');
    
    // 로딩 상태 초기화
    errorDiv.hide();
    img.hide();
    
    // 이미지 로드 시도
    img.on('load', function() {
        img.show();
        errorDiv.hide();
    }).on('error', function() {
        img.hide();
        errorDiv.show();
    }).attr('src', imageUrl);
    
    // 모달 표시
    $('#customImageModal').modal('show');
}

// 커스터마이징 이미지 다운로드
function downloadCustomImage(orderNo, imageUrl) {
    // 이미지 URL에서 파일 확장자 추출
    var extension = 'jpg';
    if (imageUrl.toLowerCase().includes('.png')) {
        extension = 'png';
    }
    
    // 다운로드 링크 생성
    var link = document.createElement('a');
    link.href = imageUrl;
    link.download = 'order_' + orderNo + '_custom.' + extension;
    link.target = '_blank';
    
    // 다운로드 실행
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
