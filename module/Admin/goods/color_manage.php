<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>상품 맞춤 색상 관리</title>
    <style>
        .color-manage-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border: 1px solid #e5e5e5;
        }

        function updateColorStockStatus(colorId, stockStatus) {
            if (!colorId) return;
            const message = stockStatus == 1
                ? '이 색상을 재입고 처리하시겠습니까?'
                : '이 색상을 품절 처리하시겠습니까?';
            if (!confirm(message)) {
                return;
            }
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'updateColorStockAPI',
                    color_id: colorId,
                    stock_status: stockStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('재고 상태가 업데이트되었습니다');
                        loadColorList();
                    } else {
                        alert('업데이트 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('재고 상태 업데이트 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('업데이트 요청 실패');
                }
            });
        }
        .color-manage-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        .color-manage-header h2 {
            margin: 0;
            color: #333;
        }
        .color-toolbar {
            margin-bottom: 15px;
        }
        .color-table {
            width: 100%;
            border-collapse: collapse;
        }
        .color-table th,
        .color-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .color-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .color-preview {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            display: inline-block;
            border-radius: 4px;
        }
        .svg-preview img {
            max-width: 60px;
            max-height: 60px;
            border: 1px solid #ddd;
        }
        .btn {
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-dialog {
            position: relative;
            margin: 50px auto;
            max-width: 600px;
            background: white;
            border-radius: 8px;
            padding: 20px;
        }
        .modal-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .modal-body {
            margin-bottom: 15px;
        }
        .modal-footer {
            text-align: right;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        /* 隐藏原生文件上传按钮 */
        .form-group input[type="file"] {
            display: none;
        }
        /* 自定义文件上传按钮样式（韩语） */
        .custom-file-upload {
            display: inline-block;
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
            cursor: pointer;
            position: relative;
        }
        .custom-file-upload:hover {
            background-color: #e9ecef;
        }
        .custom-file-upload .file-button {
            display: inline-block;
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border-radius: 3px;
            margin-right: 10px;
            font-size: 14px;
        }
        .custom-file-upload .file-name {
            color: #666;
            font-size: 14px;
        }
        .form-group input[type="color"] {
            width: 100%;
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="color-manage-container">
        <div class="color-manage-header">
            <h2>상품 맞춤 색상 관리</h2>
            <p>현재 상품 ID: <span id="currentGoodsId"></span></p>
            
            <!-- 맞춤 기능 스위치 -->
            <div class="customization-toggle" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px; border: 1px solid #dee2e6;">
                <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 0;">
                    <input type="checkbox" id="isCustomizableToggle" style="width: 20px; height: 20px; margin-right: 10px; cursor: pointer;">
                    <span style="font-size: 16px; font-weight: bold; color: #333;">
                        상품 맞춤 기능 활성화
                    </span>
                </label>
                <small style="display: block; margin-top: 8px; color: #666; line-height: 1.5;">
                    ✨ 활성화 후 사용자는 상품 상세 페이지에서 "상품 맞춤하기" 버튼을 볼 수 있으며, 클릭하여 맞춤 편집기로 들어가 개인화 맞춤을 진행할 수 있습니다
                </small>
                <div id="customizationStatus" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none; font-size: 14px; font-weight: 500;"></div>
            </div>
            
            <!-- 맞춤 사이즈 설정（多尺寸） -->
            <div class="custom-size-setting" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px; border: 1px solid #dee2e6;">
                <div style="margin-bottom: 10px;">
                    <span style="font-size: 16px; font-weight: bold; color: #333;">
                        📏 맞춤 사이즈 설정
                    </span>
                </div>
                <small style="display: block; margin-bottom: 10px; color: #666; line-height: 1.5;">
                    하나의 상품에 여러 개의 사이즈 정보를 등록할 수 있습니다. (예: 응원봉 전체 사이즈 / 손잡이 하단 커스텀 영역 등)
                </small>
                
                <div style="margin-bottom: 10px; text-align: right;">
                    <button type="button" class="btn btn-primary" onclick="showCustomSizeModal()">사이즈 추가</button>
                    </div>
                    
                <table class="color-table" id="customSizeTable">
                    <thead>
                    <tr>
                        <th style="width: 60px;">정렬</th>
                        <th style="width: 200px;">사이즈 이름</th>
                        <th style="width: 120px;">너비 (mm)</th>
                        <th style="width: 120px;">높이 (mm)</th>
                        <th style="width: 80px;">사용</th>
                        <th style="width: 160px;">작업</th>
                    </tr>
                    </thead>
                    <tbody id="customSizeTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center;">로딩 중...</td>
                    </tr>
                    </tbody>
                </table>
                    </div>
                    
            <!-- 맞춤 사이즈 추가/수정 모달 -->
            <div id="customSizeModal" class="modal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>맞춤 사이즈 설정</h3>
                    </div>
                    <div class="modal-body">
                        <form id="customSizeForm">
                            <input type="hidden" id="custom_size_id">
                            <div class="form-group">
                                <label>사이즈 이름 <span style="color: red;">*</span></label>
                                <input type="text" id="size_name" placeholder="예: 응원봉 전체 사이즈" required>
                            </div>
                            <div class="form-group">
                                <label>너비 (mm) <span style="color: red;">*</span></label>
                                <input type="number" id="size_width" step="0.1" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>높이 (mm) <span style="color: red;">*</span></label>
                                <input type="number" id="size_height" step="0.1" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>사용 여부</label>
                                <label style="margin-left:10px;">
                                    <input type="checkbox" id="size_status" value="1" checked> 사용함
                        </label>
                    </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" type="button" onclick="submitCustomSize()">저장</button>
                        <button class="btn" type="button" onclick="hideCustomSizeModal()">취소</button>
                </div>
                </div>
            </div>
                    </div>
                    
        <div class="work-guide-section" style="margin-top: 20px; padding: 15px; background: #fff7e6; border: 1px solid #ffd8a8; border-radius: 6px;">
            <h3 style="margin: 0 0 10px; color: #d9480f;">작업 가이드</h3>
            <small style="display: block; margin-bottom: 10px; color: #ad7f00;">PDF/JPG/PNG/SVG 파일을 업로드하면 미리보기로 확인할 수 있습니다.</small>
            <div class="form-group" style="margin-bottom: 10px;">
                <input type="file" id="workGuideFile" accept=".pdf,.jpg,.jpeg,.png,.svg,image/*,application/pdf" style="display: none;">
                <label for="workGuideFile" class="custom-file-upload">
                    <span class="file-button">파일 선택</span>
                    <span class="file-name" id="workGuideFileName">선택된 파일 없음</span>
                </label>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-success" onclick="uploadWorkGuide()">업로드</button>
                <button type="button" class="btn btn-danger" onclick="clearWorkGuide()">삭제</button>
            </div>
            <div id="workGuidePreview" style="margin-top: 15px; padding: 10px; background: #fff; border: 1px solid #ffe8cc; border-radius: 4px; min-height: 80px;">
                <span style="color: #999;">작업 가이드가 없습니다</span>
                    </div>
                </div>
                
        <!-- 맞춤 템플릿 & 커스텀 영역 관리 -->
        <div class="template-clip-section" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px;">
            <h3 style="margin: 0 0 10px; color: #333;">맞춤 템플릿 &amp; 커스텀 영역</h3>
            <small style="display:block; margin-bottom:10px; color:#666; line-height:1.5;">
                SVG 템플릿의 viewBox 및 고객 맞춤 가능 영역(사각형/Path)을 설정합니다. 프론트 맞춤 편집기는 이 정보를 사용해 업로드 이미지/텍스트의 편집 가능 범위를 제한합니다.
            </small>

            <div class="form-group">
                <label>템플릿 viewBox (폭 × 높이)</label>
                <div style="display:flex; gap:10px; align-items:center; margin-top:5px;">
                    <input type="number" id="template_viewbox_width" step="0.01" min="0" placeholder="예: 258.42" style="flex:1;">
                    <span style="font-weight:bold;">×</span>
                    <input type="number" id="template_viewbox_height" step="0.01" min="0" placeholder="예: 668.98" style="flex:1;">
                    <button type="button" class="btn btn-success" onclick="saveCustomTemplateViewBox()">viewBox 저장</button>
                </div>
                <small style="display:block; margin-top:5px; color:#999;">
                    ※ viewBox는 SVG 원본의 좌표계입니다. 커스텀 영역 좌표는 이 좌표계를 기준으로 입력합니다.
                </small>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label>커스텀 영역 목록 (Clip Regions)</label>
                <div style="text-align:right; margin-bottom:10px;">
                    <button type="button" class="btn btn-primary" onclick="showClipRegionModal()">영역 추가</button>
                </div>
                <table class="color-table" id="clipRegionTable">
                    <thead>
                        <tr>
                            <th style="width:60px;">정렬</th>
                            <th style="width:80px;">타입</th>
                            <th style="width:80px;">X</th>
                            <th style="width:80px;">Y</th>
                            <th style="width:80px;">W</th>
                            <th style="width:80px;">H</th>
                            <th>Path d</th>
                            <th style="width:160px;">작업</th>
                        </tr>
                    </thead>
                    <tbody id="clipRegionTableBody">
                        <tr>
                            <td colspan="8" style="text-align:center;">로딩 중...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 커스텀 영역 추가/수정 모달 -->
        <div id="clipRegionModal" class="modal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3>커스텀 영역 설정</h3>
                </div>
                <div class="modal-body">
                    <form id="clipRegionForm">
                        <input type="hidden" id="clip_region_id">
                        <div class="form-group">
                            <label>영역 타입 <span style="color:red;">*</span></label>
                            <select id="clip_type" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="rect">사각형 (rect)</option>
                                <option value="path">경로 (path)</option>
                            </select>
                        </div>

                        <div id="clip_rect_fields">
                            <div class="form-group">
                                <label>X (left)</label>
                                <input type="number" id="clip_x" step="0.01" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                            </div>
                            <div class="form-group">
                                <label>Y (top)</label>
                                <input type="number" id="clip_y" step="0.01" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                            </div>
                            <div class="form-group">
                                <label>W (width)</label>
                                <input type="number" id="clip_w" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                            </div>
                            <div class="form-group">
                                <label>H (height)</label>
                                <input type="number" id="clip_h" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                            </div>
                        </div>

                        <div id="clip_path_fields" style="display:none;">
                            <div class="form-group">
                                <label>Path d 문자열 <span style="color:red;">*</span></label>
                                <textarea id="clip_d" rows="4" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"
                                    placeholder="예: M250.24,129.94c0-66.84-54.19-121.03-121.03-121.03S8.18,63.09,8.18,129.94c0,18.87,4.34,36.71,12.04,52.63h217.98c7.7-15.92,12.04-33.76,12.04-52.63h0Z"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>정렬 (sort)</label>
                            <input type="number" id="clip_sort" value="0" min="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" type="button" onclick="submitClipRegion()">저장</button>
                    <button class="btn" type="button" onclick="hideClipRegionModal()">취소</button>
                </div>
            </div>
        </div>
        
        <!-- 부가 상품 이미지(포장/부속품) 업로드 및 관리 -->
        <div class="extra-image-section" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px;">
            <h3 style="margin: 0 0 10px; color: #333;">부가 상품 이미지 (포장/부속품)</h3>
            <small style="display: block; margin-bottom: 10px; color: #666; line-height: 1.5;">
                SVG 형식의 포장 외관, 부속품 이미지를 업로드하면, 프론트 맞춤 편집기에서 선택용 소재로 사용할 수 있습니다.
            </small>

            <div class="form-group" style="margin-bottom: 10px;">
                <input type="file" id="extraImageFile" accept=".svg,image/svg+xml" style="display:none;">
                <label for="extraImageFile" class="custom-file-upload">
                    <span class="file-button">파일 선택</span>
                    <span class="file-name" id="extraImageFileName">선택된 파일 없음</span>
                </label>
                </div>
            <div style="display:flex; gap:10px; margin-bottom:10px;">
                <button type="button" class="btn btn-success" onclick="uploadExtraImage()">업로드</button>
            </div>

            <table class="color-table" id="extraImageTable">
                <thead>
                <tr>
                    <th style="width:60px;">정렬</th>
                    <th style="width:200px;">이미지 이름</th>
                    <th style="width:120px;">미리보기</th>
                    <th>경로</th>
                    <th style="width:160px;">작업</th>
                </tr>
                </thead>
                <tbody id="extraImageTableBody">
                <tr>
                    <td colspan="5" style="text-align:center;">로딩 중...</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- 부가 상품 이미지 이름 수정 모달 -->
        <div id="extraImageModal" class="modal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3>부가 이미지 이름 수정</h3>
                </div>
                <div class="modal-body">
                    <form id="extraImageForm">
                        <input type="hidden" id="extraImage_id">
                        <div class="form-group">
                            <label>이미지 이름 <span style="color:red;">*</span></label>
                            <input type="text" id="extraImage_name" placeholder="예: 기본 포장 이미지" required>
                        </div>
                        <div class="form-group">
                            <label>경로</label>
                            <input type="text" id="extraImage_path" readonly>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" type="button" onclick="submitEditExtraImage()">저장</button>
                    <button class="btn" type="button" onclick="hideExtraImageModal()">취소</button>
                </div>
            </div>
        </div>
        
        <div class="color-toolbar">
            <button class="btn btn-primary" onclick="showAddColorModal()">색상 추가</button>
            <button class="btn" onclick="window.close()">창 닫기</button>
        </div>
        
        <table class="color-table" id="colorTable">
            <thead>
                <tr>
                    <th style="width: 50px;">정렬</th>
                    <th style="width: 150px;">색상 이름</th>
                    <th style="width: 100px;">HEX 미리보기</th>
                    <th style="width: 120px;">SVG 미리보기</th>
                    <th>SVG 경로</th>
                    <th style="width: 100px;">재고 상태</th>
                    <th style="width: 180px;">작업</th>
                </tr>
            </thead>
            <tbody id="colorTableBody">
                <tr>
                    <td colspan="6" style="text-align: center;">로딩 중...</td>
                </tr>
            </tbody>
        </table>

        <!-- 추가 옵션 관리 영역 -->
        <div class="extra-option-section" style="margin-top: 30px;">
            <h3 style="margin: 0 0 10px; color: #333;">추가 옵션 관리</h3>
            <small style="display: block; margin-bottom: 10px; color: #666; line-height: 1.5;">
                LED 단색 지정, 손잡이 인쇄 등 색상 이외의 부가 옵션을 상품별로 설정합니다.
            </small>
            <div class="color-toolbar" style="margin-bottom: 10px;">
                <button class="btn btn-primary" type="button" onclick="showAddExtraOptionModal()">추가 옵션</button>
            </div>
            <table class="color-table" id="extraOptionTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">정렬</th>
                        <th style="width: 120px;">옵션 그룹</th>
                        <th style="width: 200px;">옵션명</th>
                        <th style="width: 100px;">타입</th>
                        <th style="width: 100px;">추가금액</th>
                        <th style="width: 100px;">가격방식</th>
                        <th style="width: 60px;">필수</th>
                        <th style="width: 60px;">사용</th>
                        <th style="width: 150px;">작업</th>
                    </tr>
                </thead>
                <tbody id="extraOptionTableBody">
                    <tr>
                        <td colspan="9" style="text-align: center;">로딩 중...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 색상 추가 모달 -->
    <div id="addColorModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>색상 추가</h3>
            </div>
            <div class="modal-body">
                <form id="addColorForm">
                    <div class="form-group">
                        <label>색상 이름 <span style="color: red;">*</span></label>
                        <input type="text" name="color_name" required placeholder="예: 네이비 블루">
                    </div>
                    
                    <div class="form-group">
                        <label>색상 코드</label>
                        <input type="text" name="color_code" placeholder="예: navy">
                    </div>
                    
                    <div class="form-group">
                        <label>색상 HEX 값 <span style="color: red;">*</span></label>
                        <input type="color" name="color_hex" value="#000000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>SVG 이미지 업로드 (선택)</label>
                        <input type="file" name="svg_file" id="add_svg_file" accept=".svg,image/svg+xml">
                        <label for="add_svg_file" class="custom-file-upload">
                            <span class="file-button">파일 선택</span>
                            <span class="file-name" id="add_file_name">선택된 파일 없음</span>
                        </label>
                        <small style="color: #666;">선택적으로 SVG 이미지를 업로드하며, 업로드하지 않으면 색상 HEX 블록만 표시됩니다</small>
                    </div>
                    
                    <div class="form-group">
                        <label>정렬</label>
                        <input type="number" name="sort_order" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>재고 상태</label>
                        <select name="stock_status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="1">재고있음</option>
                            <option value="0">품절</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" onclick="submitAddColor()">저장</button>
                <button class="btn" onclick="hideAddColorModal()">취소</button>
            </div>
        </div>
    </div>

    <!-- 색상 수정 모달 -->
    <div id="editColorModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>색상 수정</h3>
            </div>
            <div class="modal-body">
                <form id="editColorForm">
                    <input type="hidden" name="color_id" id="edit_color_id">
                    
                    <div class="form-group">
                        <label>색상 이름 <span style="color: red;">*</span></label>
                        <input type="text" name="color_name" id="edit_color_name" required placeholder="예: 네이비 블루">
                    </div>
                    
                    <div class="form-group">
                        <label>색상 코드</label>
                        <input type="text" name="color_code" id="edit_color_code" placeholder="예: navy">
                    </div>
                    
                    <div class="form-group">
                        <label>색상 HEX 값 <span style="color: red;">*</span></label>
                        <input type="color" name="color_hex" id="edit_color_hex" required>
                    </div>
                    
                    <div class="form-group">
                        <label>현재 SVG 미리보기</label>
                        <div id="current_svg_preview" style="margin-bottom: 10px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>SVG 이미지 변경 (선택)</label>
                        <input type="file" name="svg_file" id="edit_svg_file" accept=".svg,image/svg+xml">
                        <label for="edit_svg_file" class="custom-file-upload">
                            <span class="file-button">파일 선택</span>
                            <span class="file-name" id="edit_file_name">선택된 파일 없음</span>
                        </label>
                        <small style="color: #666;">비워두면 기존 SVG가 유지됩니다</small>
                    </div>
                    
                    <div class="form-group">
                        <label>정렬</label>
                        <input type="number" name="sort_order" id="edit_sort_order" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>재고 상태</label>
                        <select name="stock_status" id="edit_stock_status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="1">재고있음</option>
                            <option value="0">품절</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" onclick="submitEditColor()">저장</button>
                <button class="btn" onclick="hideEditColorModal()">취소</button>
            </div>
        </div>
    </div>

    <!-- 추가 옵션 추가 모달 -->
    <div id="addExtraOptionModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>추가 옵션 추가</h3>
            </div>
            <div class="modal-body">
                <form id="addExtraOptionForm">
                    <div class="form-group">
                        <label>옵션 그룹 Key <span style="color: red;">*</span></label>
                        <input type="text" name="option_group" placeholder="예: led_basic, led_single, handle_print" required>
                    </div>
                    <div class="form-group">
                        <label>옵션명(표시 제목) <span style="color: red;">*</span></label>
                        <input type="text" name="option_label" placeholder="예: LED 단색 지정 옵션(+1,000원)" required>
                    </div>
                    <div class="form-group">
                        <label>설명(선택)</label>
                        <input type="text" name="option_desc" placeholder="예: 필수 선택이 아닌 자유 선택입니다.">
                    </div>
                    <div class="form-group">
                        <label>입력 타입</label>
                        <select name="input_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="checkbox">체크박스</option>
                            <option value="radio">단일 선택</option>
                            <option value="color_palette">색상 팔레트</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>추가 금액 (원)</label>
                        <input type="number" name="extra_price" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>가격 방식</label>
                        <select name="price_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="per_unit">수량당</option>
                            <option value="per_order">주문당</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>기타 설정</label>
                        <div>
                            <label style="margin-right: 15px;">
                                <input type="checkbox" name="is_required" value="1"> 필수 옵션
                            </label>
                            <label>
                                <input type="checkbox" name="status" value="1" checked> 사용함
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>정렬</label>
                        <input type="number" name="sort_order" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" type="button" onclick="submitAddExtraOption()">저장</button>
                <button class="btn" type="button" onclick="hideAddExtraOptionModal()">취소</button>
            </div>
        </div>
    </div>

    <!-- 추가 옵션 수정 모달 -->
    <div id="editExtraOptionModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>추가 옵션 수정</h3>
            </div>
            <div class="modal-body">
                <form id="editExtraOptionForm">
                    <input type="hidden" name="option_id" id="edit_option_id">
                    <div class="form-group">
                        <label>옵션 그룹 Key <span style="color: red;">*</span></label>
                        <input type="text" name="option_group" id="edit_option_group" required>
                    </div>
                    <div class="form-group">
                        <label>옵션명(표시 제목) <span style="color: red;">*</span></label>
                        <input type="text" name="option_label" id="edit_option_label" required>
                    </div>
                    <div class="form-group">
                        <label>설명(선택)</label>
                        <input type="text" name="option_desc" id="edit_option_desc">
                    </div>
                    <div class="form-group">
                        <label>입력 타입</label>
                        <select name="input_type" id="edit_input_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="checkbox">체크박스</option>
                            <option value="radio">단일 선택</option>
                            <option value="color_palette">색상 팔레트</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>추가 금액 (원)</label>
                        <input type="number" name="extra_price" id="edit_extra_price" min="0">
                    </div>
                    <div class="form-group">
                        <label>가격 방식</label>
                        <select name="price_type" id="edit_price_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="per_unit">수량당</option>
                            <option value="per_order">주문당</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>기타 설정</label>
                        <div>
                            <label style="margin-right: 15px;">
                                <input type="checkbox" name="is_required" id="edit_is_required" value="1"> 필수 옵션
                            </label>
                            <label>
                                <input type="checkbox" name="status" id="edit_status" value="1"> 사용함
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>정렬</label>
                        <input type="number" name="sort_order" id="edit_sort_order_extra" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" type="button" onclick="submitEditExtraOption()">저장</button>
                <button class="btn" type="button" onclick="hideEditExtraOptionModal()">취소</button>
            </div>
        </div>
    </div>

    <!-- 추가 옵션 색상 관리 모달 -->
    <div id="extraOptionColorsModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>옵션 색상 관리</h3>
                <div id="extraOptionColorsSubtitle" style="margin-top:5px; font-size:13px; color:#666;"></div>
            </div>
            <div class="modal-body">
                <table class="color-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">정렬</th>
                            <th style="width: 160px;">색상 이름</th>
                            <th style="width: 120px;">HEX 미리보기</th>
                            <th style="width: 120px;">색상 코드</th>
                            <th style="width: 150px;">작업</th>
                        </tr>
                    </thead>
                    <tbody id="extraOptionColorsTableBody">
                        <tr>
                            <td colspan="5" style="text-align: center;">로딩 중...</td>
                        </tr>
                    </tbody>
                </table>

                <hr style="margin: 15px 0;">

                <form id="extraOptionColorForm">
                    <input type="hidden" id="extraColor_mode" value="add">
                    <input type="hidden" id="extraColor_color_id">
                    <input type="hidden" id="extraColor_option_id">

                    <div class="form-group">
                        <label>색상 이름 <span style="color: red;">*</span></label>
                        <input type="text" id="extraColor_name" placeholder="예: 레드" required>
                    </div>
                    <div class="form-group">
                        <label>색상 코드 (선택)</label>
                        <input type="text" id="extraColor_code" placeholder="예: red01">
                    </div>
                    <div class="form-group">
                        <label>색상 HEX 값 <span style="color: red;">*</span></label>
                        <input type="color" id="extraColor_hex" value="#FF0000" required>
                    </div>
                    <div class="form-group">
                        <label>정렬</label>
                        <input type="number" id="extraColor_sort_order" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" type="button" onclick="submitExtraOptionColor()">저장</button>
                <button class="btn" type="button" onclick="resetExtraOptionColorForm()">새 색상</button>
                <button class="btn" type="button" onclick="hideExtraOptionColorsModal()">닫기</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 文件上传按钮 - 显示选中的文件名（韩语）
        document.getElementById('add_svg_file').addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : '선택된 파일 없음';
            document.getElementById('add_file_name').textContent = fileName;
        });
        
        document.getElementById('edit_svg_file').addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : '선택된 파일 없음';
            document.getElementById('edit_file_name').textContent = fileName;
        });
        
        document.getElementById('workGuideFile').addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : '선택된 파일 없음';
            document.getElementById('workGuideFileName').textContent = fileName;
        });

        // 부가 상품 이미지 파일 선택시 파일명 표시
        const extraImageInput = document.getElementById('extraImageFile');
        if (extraImageInput) {
            extraImageInput.addEventListener('change', function() {
                const fileName = this.files.length > 0 ? this.files[0].name : '선택된 파일 없음';
                const nameSpan = document.getElementById('extraImageFileName');
                if (nameSpan) {
                    nameSpan.textContent = fileName;
                }
            });
        }
        
        // URL에서 상품 ID 가져오기
        const urlParams = new URLSearchParams(window.location.search);
        const goodsId = urlParams.get('goods_id');
        
        console.log('페이지 초기화', {
            url: window.location.href,
            searchParams: window.location.search,
            goodsId: goodsId,
            urlParams: Object.fromEntries(urlParams.entries())
        });
        
        if (!goodsId) {
            console.error('상품 ID 파라미터 누락');
            alert('상품 ID 파라미터가 없습니다');
            window.close();
        }
        
        // 상품 ID가 양의 정수인지 확인
        if (!/^\d+$/.test(goodsId) || parseInt(goodsId) <= 0) {
            console.error('상품 ID 형식 오류', {
                goodsId: goodsId,
                isNumeric: /^\d+$/.test(goodsId),
                isPositive: parseInt(goodsId) > 0
            });
            alert('상품 ID는 유효한 양의 정수여야 합니다');
            window.close();
        }
        
        console.log('상품 ID 검증 완료', {
            goodsId: goodsId,
            goodsIdInt: parseInt(goodsId)
        });
        
        document.getElementById('currentGoodsId').textContent = goodsId;
        
        // 페이지 로드 시 색상 목록 가져오기
        $(document).ready(function() {
            console.log('페이지 DOM 로드 완료, 색상 목록 로드 시작');
            loadColorList();
            loadCustomizationStatus(); // 맞춤 기능 상태 로드
            loadCustomSizes(); // 맞춤 사이즈 목록 로드
            loadWorkGuide(); // 작업 가이드 로드
            loadExtraOptionsList(); // 추가 옵션 로드
            loadExtraImages(); // 부가 상품 이미지 로드
            loadCustomTemplateAndRegions(); // 맞춤 템플릿 & 커스텀 영역 로드
        });
        
        // 색상 목록 로드
        function loadColorList() {
            console.log('색상 목록 로드 시작', {
                goodsId: goodsId,
                url: '../custom_color.php',
                method: 'GET'
            });
            
            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: { 
                    mode: 'getColorsAPI',
                    goods_id: goodsId 
                },
                dataType: 'json',
                success: function(response) {
                    console.log('색상 목록 로드 성공', {
                        response: response,
                        success: response.success,
                        dataCount: response.data ? response.data.length : 0,
                        data: response.data
                    });
                    
                    if (response.success && response.data && response.data.length > 0) {
                        renderColorTable(response.data);
                    } else {
                        $('#colorTableBody').html('<tr><td colspan="6" style="text-align: center;">색상 설정이 없습니다</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('색상 목록 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $('#colorTableBody').html('<tr><td colspan="6" style="text-align: center;">로드 실패</td></tr>');
                }
            });
        }
        
        // 색상 테이블 렌더링
        function renderColorTable(colors) {
            let html = '';
            colors.forEach(function(color) {
                html += '<tr>';
                html += '<td>' + color.sort_order + '</td>';
                html += '<td>' + color.color_name + '</td>';
                html += '<td><div class="color-preview" style="background-color:' + color.color_hex + '"></div></td>';
                if (color.svg_image_path) {
                    html += '<td class="svg-preview"><img src="' + color.svg_image_path + '" alt="SVG 미리보기"></td>';
                    html += '<td>' + color.svg_image_path + '</td>';
                } else {
                    html += '<td class="svg-preview"><span style="color:#666;">SVG 미업로드</span></td>';
                    html += '<td><span style="color:#999;">--</span></td>';
                }
                html += '<td>' + (color.stock_status == 1 ? '재고있음' : '품절') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary" onclick="showEditColorModal(' + JSON.stringify(color).replace(/"/g, '&quot;') + ')" style="margin-right: 5px;">수정</button>';
                html += '<button class="btn" style="margin-right:5px;" onclick="updateColorStockStatus(' + color.color_id + ', ' + (color.stock_status == 1 ? 0 : 1) + ')">'
                    + (color.stock_status == 1 ? '품절 처리' : '재입고 처리') + '</button>';
                html += '<button class="btn btn-danger" onclick="deleteColor(\'' + color.color_id + '\')">삭제</button>';
                html += '</td>';
                html += '</tr>';
            });
            $('#colorTableBody').html(html);
        }
        
        // ============================
        // 추가 옵션 목록 로드 / 렌더링
        // ============================
        function loadExtraOptionsList() {
            console.log('추가 옵션 목록 로드 시작', {
                goodsId: goodsId,
                url: '../custom_color.php',
                method: 'GET'
            });

            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: {
                    mode: 'getExtraOptionsAPI',
                    goods_id: goodsId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('추가 옵션 목록 로드 성공', response);

                    if (response.success && response.data && response.data.length > 0) {
                        renderExtraOptionTable(response.data);
                    } else {
                        $('#extraOptionTableBody').html('<tr><td colspan="9" style="text-align: center;">추가 옵션이 없습니다</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('추가 옵션 목록 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    $('#extraOptionTableBody').html('<tr><td colspan="9" style="text-align: center;">로드 실패</td></tr>');
                }
            });
        }

        function renderExtraOptionTable(options) {
            let html = '';
            options.forEach(function(option) {
                const inputTypeLabel = option.input_type === 'color_palette'
                    ? '색상 팔레트'
                    : (option.input_type === 'radio' ? '단일 선택' : '체크박스');
                const priceTypeLabel = option.price_type === 'per_order' ? '주문당' : '수량당';
                const requiredLabel = option.is_required == 1 ? '예' : '아니오';
                const statusLabel = option.status == 1 ? '사용' : '중지';

                html += '<tr>';
                html += '<td>' + (option.sort_order || 0) + '</td>';
                html += '<td>' + (option.option_group || '') + '</td>';
                html += '<td>' + (option.option_label || '') + '</td>';
                html += '<td>' + inputTypeLabel + '</td>';
                html += '<td>' + (option.extra_price ? option.extra_price.toLocaleString() : 0) + '</td>';
                html += '<td>' + priceTypeLabel + '</td>';
                html += '<td>' + requiredLabel + '</td>';
                html += '<td>' + statusLabel + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary" style="margin-right:5px;" onclick="showEditExtraOptionModal(' + JSON.stringify(option).replace(/"/g, '&quot;') + ')">수정</button>';
                if (option.input_type === 'color_palette') {
                    html += '<button class="btn" style="margin-right:5px;" onclick="showExtraOptionColorsModal(' + JSON.stringify(option).replace(/"/g, '&quot;') + ')">색상 관리</button>';
                }
                html += '<button class="btn btn-danger" onclick="deleteExtraOption(\'' + option.option_id + '\')">삭제</button>';
                html += '</td>';
                html += '</tr>';
            });
            $('#extraOptionTableBody').html(html);
        }
        
        // 색상 추가 모달 표시
        function showAddColorModal() {
            $('#addColorModal').show();
            // 重置文件名显示
            document.getElementById('add_file_name').textContent = '선택된 파일 없음';
        }
        
        // 색상 추가 모달 숨기기
        function hideAddColorModal() {
            $('#addColorModal').hide();
            $('#addColorForm')[0].reset();
            // 重置文件名显示
            document.getElementById('add_file_name').textContent = '선택된 파일 없음';
        }
        
        // 색상 추가 제출
        function submitAddColor() {
            console.log('색상 추가 제출 시작');
            
            const form = document.getElementById('addColorForm');
            const formData = new FormData(form);
            const svgFile = formData.get('svg_file');
            
            console.log('폼 데이터 확인', {
                svgFile: svgFile,
                svgFileSize: svgFile ? svgFile.size : 0,
                svgFileName: svgFile ? svgFile.name : null,
                formDataEntries: Array.from(formData.entries())
            });
            
            if (svgFile && svgFile.size > 0) {
                console.log('SVG 파일 감지, 업로드 시작', {
                    fileName: svgFile.name,
                    fileSize: svgFile.size,
                    fileType: svgFile.type
                });

                const uploadFormData = new FormData();
                uploadFormData.append('svg_file', svgFile);
                uploadFormData.append('goods_id', goodsId);
                uploadFormData.append('mode', 'uploadSvgAPI');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../custom_color.php', true);

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        console.log('SVG 업로드 응답', {
                            status: xhr.status,
                            responseText: xhr.responseText,
                            readyState: xhr.readyState
                        });

                        if (xhr.status === 200) {
                            try {
                                const uploadResponse = JSON.parse(xhr.responseText);
                                console.log('SVG 업로드 응답 파싱 성공', {
                                    uploadResponse: uploadResponse,
                                    success: uploadResponse.success,
                                    svgUrl: uploadResponse.data ? uploadResponse.data.svg_url : null
                                });

                                if (uploadResponse.success) {
                                    console.log('SVG 업로드 성공, 색상 정보 저장 시작');
                                    saveColorInfo(formData, uploadResponse.data.svg_url);
                                } else {
                                    console.error('SVG 업로드 실패', {
                                        message: uploadResponse.message,
                                        response: uploadResponse
                                    });
                                    alert('SVG 업로드 실패: ' + uploadResponse.message);
                                }
                            } catch (e) {
                                console.error('응답 파싱 실패:', e);
                                alert('SVG 업로드 응답 파싱 실패');
                            }
                        } else {
                            console.error('SVG 업로드 요청 실패', {
                                status: xhr.status,
                                responseText: xhr.responseText
                            });
                            alert('SVG 업로드 요청 실패, 상태 코드: ' + xhr.status);
                        }
                    }
                };

                xhr.send(uploadFormData);
            } else {
                console.log('SVG 파일 미선택, 색상 정보 바로 저장');
                saveColorInfo(formData, '');
            }
        }
        
        // 색상 정보 저장
        function saveColorInfo(formData, svgUrl) {
            console.log('색상 정보 저장 시작', {
                goodsId: goodsId,
                svgUrl: svgUrl,
                formData: {
                    color_name: formData.get('color_name'),
                    color_code: formData.get('color_code'),
                    color_hex: formData.get('color_hex'),
                    sort_order: formData.get('sort_order'),
                    stock_status: formData.get('stock_status') || 1
                }
            });
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'addColorAPI',
                    goods_id: goodsId,
                    color_name: formData.get('color_name'),
                    color_code: formData.get('color_code'),
                    color_hex: formData.get('color_hex'),
                    svg_image_path: svgUrl,
                    sort_order: formData.get('sort_order'),
                    stock_status: formData.get('stock_status') || 1
                },
                dataType: 'json',
                beforeSend: function() {
                    console.log('AJAX 요청 발송', {
                        url: '../custom_color.php',
                        method: 'POST',
                        data: {
                            mode: 'addColorAPI',
                            goods_id: goodsId,
                            color_name: formData.get('color_name'),
                            color_code: formData.get('color_code'),
                            color_hex: formData.get('color_hex'),
                            svg_image_path: svgUrl,
                            sort_order: formData.get('sort_order')
                        }
                    });
                },
                success: function(response) {
                    console.log('AJAX 요청 성공', {
                        response: response,
                        success: response.success,
                        message: response.message
                    });
                    
                    if (response.success) {
                        alert('색상 추가 성공');
                        hideAddColorModal();
                        loadColorList();
                    } else {
                        alert('저장 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    alert('저장 요청 실패');
                }
            });
        }
        
        // 색상 수정 모달 표시
        function showEditColorModal(color) {
            // 폼 데이터 채우기
            $('#edit_color_id').val(color.color_id);
            $('#edit_color_name').val(color.color_name);
            $('#edit_color_code').val(color.color_code || '');
            $('#edit_color_hex').val(color.color_hex || '#000000');
            $('#edit_sort_order').val(color.sort_order || 0);
            
            // 重置文件名显示
            document.getElementById('edit_file_name').textContent = '선택된 파일 없음';
            
            // 현재 SVG 미리보기 표시
            if (color.svg_image_path) {
                $('#current_svg_preview').html('<img src="' + color.svg_image_path + '" style="max-width: 100px; max-height: 100px; border: 1px solid #ddd;">');
            } else {
                $('#current_svg_preview').html('<span style="color:#999;">SVG 미업로드</span>');
            }
            
            // 파일 선택 초기화
            $('#edit_svg_file').val('');
            
            // 모달 표시
            $('#editColorModal').show();
        }
        
        // 색상 수정 모달 숨기기
        function hideEditColorModal() {
            $('#editColorModal').hide();
            $('#editColorForm')[0].reset();
            // 重置文件名显示
            document.getElementById('edit_file_name').textContent = '선택된 파일 없음';
        }
        
        // 색상 수정 제출
        function submitEditColor() {
            const form = document.getElementById('editColorForm');
            const formData = new FormData(form);
            const svgFile = formData.get('svg_file');
            const colorId = formData.get('color_id');
            
            // 새 SVG 파일이 있으면 먼저 업로드
            if (svgFile && svgFile.size > 0) {
                const uploadFormData = new FormData();
                uploadFormData.append('svg_file', svgFile);
                uploadFormData.append('goods_id', goodsId);
                uploadFormData.append('mode', 'uploadSvgAPI');
                
                $.ajax({
                    url: '../custom_color.php',
                    method: 'POST',
                    data: uploadFormData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(uploadResponse) {
                        if (uploadResponse.success) {
                            // SVG 업로드 성공, 색상 정보 업데이트
                updateColorInfo(colorId, formData, uploadResponse.data.svg_url);
                        } else {
                            alert('SVG 업로드 실패: ' + uploadResponse.message);
                        }
                    },
                    error: function() {
                        alert('SVG 업로드 요청 실패');
                    }
                });
            } else {
                // 새 SVG 없음, 색상 정보만 업데이트
                updateColorInfo(colorId, formData, null);
            }
        }
        
        // 색상 정보 업데이트
        function updateColorInfo(colorId, formData, svgUrl) {
            const updateData = {
                mode: 'updateColorAPI',
                goods_id: goodsId,
                color_id: colorId,
                color_name: formData.get('color_name'),
                color_code: formData.get('color_code'),
                color_hex: formData.get('color_hex'),
                sort_order: formData.get('sort_order'),
                stock_status: formData.get('stock_status') || 1
            };
            
            // 새 SVG URL이 있으면 업데이트 데이터에 추가
            if (svgUrl) {
                updateData.svg_image_path = svgUrl;
            }
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: updateData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('색상 업데이트 성공');
                        hideEditColorModal();
                        loadColorList();
                    } else {
                        alert('업데이트 실패: ' + response.message);
                    }
                },
                error: function() {
                    alert('업데이트 요청 실패');
                }
            });
        }
        
        // 색상 삭제
        function deleteColor(colorId) {
            console.log('색상 삭제 시작', {
                colorId: colorId,
                goodsId: goodsId
            });
            
            if (!confirm('이 색상을 삭제하시겠습니까?')) {
                console.log('사용자가 삭제 취소');
                return;
            }
            
            console.log('사용자가 삭제 확인, 삭제 요청 발송');
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: { 
                    mode: 'deleteColorAPI',
                    color_id: colorId,
                    goods_id: goodsId
                },
                dataType: 'json',
                beforeSend: function() {
                    console.log('삭제 요청 발송', {
                        url: '../custom_color.php',
                        method: 'POST',
                        data: {
                            mode: 'deleteColorAPI',
                            color_id: colorId,
                            goods_id: goodsId
                        }
                    });
                },
                success: function(response) {
                    console.log('삭제 요청 성공', {
                        response: response,
                        success: response.success,
                        message: response.message
                    });
                    
                    if (response.success) {
                        alert('색상 삭제 성공');
                        loadColorList();
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('삭제 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    alert('삭제 요청 실패');
                }
            });
        }
        
        // 추가 옵션 모달 표시/숨기기
        function showAddExtraOptionModal() {
            $('#addExtraOptionModal').show();
            $('#addExtraOptionForm')[0].reset();
            // 기본값 설정
            $('#addExtraOptionForm').find('select[name="input_type"]').val('checkbox');
            $('#addExtraOptionForm').find('select[name="price_type"]').val('per_unit');
            $('#addExtraOptionForm').find('input[name="status"]').prop('checked', true);
        }

        function hideAddExtraOptionModal() {
            $('#addExtraOptionModal').hide();
            $('#addExtraOptionForm')[0].reset();
        }

        function showEditExtraOptionModal(option) {
            $('#edit_option_id').val(option.option_id);
            $('#edit_option_group').val(option.option_group || '');
            $('#edit_option_label').val(option.option_label || '');
            $('#edit_option_desc').val(option.option_desc || '');
            $('#edit_input_type').val(option.input_type || 'checkbox');
            $('#edit_extra_price').val(option.extra_price || 0);
            $('#edit_price_type').val(option.price_type || 'per_unit');
            $('#edit_is_required').prop('checked', option.is_required == 1);
            $('#edit_status').prop('checked', option.status == 1);
            $('#edit_sort_order_extra').val(option.sort_order || 0);

            $('#editExtraOptionModal').show();
        }

        function hideEditExtraOptionModal() {
            $('#editExtraOptionModal').hide();
            $('#editExtraOptionForm')[0].reset();
        }

        // 추가 옵션 저장
        function submitAddExtraOption() {
            const form = $('#addExtraOptionForm');
            const data = {
                mode: 'addExtraOptionAPI',
                goods_id: goodsId,
                option_group: form.find('input[name="option_group"]').val(),
                option_label: form.find('input[name="option_label"]').val(),
                option_desc: form.find('input[name="option_desc"]').val(),
                input_type: form.find('select[name="input_type"]').val(),
                extra_price: form.find('input[name="extra_price"]').val() || 0,
                price_type: form.find('select[name="price_type"]').val(),
                is_required: form.find('input[name="is_required"]').is(':checked') ? 1 : 0,
                status: form.find('input[name="status"]').is(':checked') ? 1 : 0,
                sort_order: form.find('input[name="sort_order"]').val() || 0
            };

            if (!data.option_group) {
                alert('옵션 그룹 Key를 입력해주세요');
                return;
            }
            if (!data.option_label) {
                alert('옵션명을 입력해주세요');
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('추가 옵션이 저장되었습니다');
                        hideAddExtraOptionModal();
                        loadExtraOptionsList();
                    } else {
                        alert('저장 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('추가 옵션 저장 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('저장 요청 실패');
                }
            });
        }

        // 추가 옵션 수정 저장
        function submitEditExtraOption() {
            const form = $('#editExtraOptionForm');
            const optionId = form.find('#edit_option_id').val();

            if (!optionId) {
                alert('옵션 ID가 없습니다');
                return;
            }

            const data = {
                mode: 'updateExtraOptionAPI',
                option_id: optionId,
                option_group: form.find('#edit_option_group').val(),
                option_label: form.find('#edit_option_label').val(),
                option_desc: form.find('#edit_option_desc').val(),
                input_type: form.find('#edit_input_type').val(),
                extra_price: form.find('#edit_extra_price').val() || 0,
                price_type: form.find('#edit_price_type').val(),
                is_required: form.find('#edit_is_required').is(':checked') ? 1 : 0,
                status: form.find('#edit_status').is(':checked') ? 1 : 0,
                sort_order: form.find('#edit_sort_order_extra').val() || 0
            };

            if (!data.option_group) {
                alert('옵션 그룹 Key를 입력해주세요');
                return;
            }
            if (!data.option_label) {
                alert('옵션명을 입력해주세요');
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('추가 옵션이 수정되었습니다');
                        hideEditExtraOptionModal();
                        loadExtraOptionsList();
                    } else {
                        alert('수정 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('추가 옵션 수정 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('수정 요청 실패');
                }
            });
        }

        // 추가 옵션 삭제
        function deleteExtraOption(optionId) {
            console.log('추가 옵션 삭제 시도', { optionId: optionId, goodsId: goodsId });

            if (!confirm('이 추가 옵션을 삭제하시겠습니까?')) {
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'deleteExtraOptionAPI',
                    option_id: optionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('추가 옵션이 삭제되었습니다');
                        loadExtraOptionsList();
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('추가 옵션 삭제 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('삭제 요청 실패');
                }
            });
        }

        // ============================================
        // 추가 옵션 색상 팔레트 관리
        // ============================================
        let currentExtraOptionIdForColors = null;

        function showExtraOptionColorsModal(option) {
            currentExtraOptionIdForColors = option.option_id;
            $('#extraOptionColorsSubtitle').text(
                (option.option_label || '') + ' [' + (option.option_group || '') + ']'
            );
            $('#extraColor_option_id').val(option.option_id || '');
            resetExtraOptionColorForm();
            loadExtraOptionColors(option.option_id);
            $('#extraOptionColorsModal').show();
        }

        function hideExtraOptionColorsModal() {
            $('#extraOptionColorsModal').hide();
            currentExtraOptionIdForColors = null;
            $('#extraOptionColorsTableBody').html('<tr><td colspan="5" style="text-align: center;">데이터가 없습니다</td></tr>');
            resetExtraOptionColorForm();
        }

        function resetExtraOptionColorForm() {
            $('#extraColor_mode').val('add');
            $('#extraColor_color_id').val('');
            $('#extraColor_name').val('');
            $('#extraColor_code').val('');
            $('#extraColor_hex').val('#FF0000');
            $('#extraColor_sort_order').val('0');
        }

        function loadExtraOptionColors(optionId) {
            if (!optionId) {
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: {
                    mode: 'getExtraOptionColorsAPI',
                    option_id: optionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        renderExtraOptionColorsTable(response.data);
                    } else {
                        $('#extraOptionColorsTableBody').html('<tr><td colspan="5" style="text-align: center;">색상이 없습니다</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('옵션 색상 목록 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    $('#extraOptionColorsTableBody').html('<tr><td colspan="5" style="text-align: center;">로드 실패</td></tr>');
                }
            });
        }

        function renderExtraOptionColorsTable(colors) {
            let html = '';
            colors.forEach(function(color) {
                html += '<tr>';
                html += '<td>' + (color.sort_order || 0) + '</td>';
                html += '<td>' + (color.color_name || '') + '</td>';
                html += '<td><div class="color-preview" style="background-color:' + (color.color_hex || '#CCCCCC') + '"></div></td>';
                html += '<td>' + (color.color_code || '') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary" style="margin-right:5px;" onclick="fillExtraOptionColorForm(' + JSON.stringify(color).replace(/"/g, '&quot;') + ')">수정</button>';
                // color_id 作为 숫자 直接传入，避免字符串转义错误
                html += '<button class="btn btn-danger" onclick="deleteExtraOptionColor(' + color.color_id + ')">삭제</button>';
                html += '</td>';
                html += '</tr>';
            });
            $('#extraOptionColorsTableBody').html(html);
        }

        function fillExtraOptionColorForm(color) {
            $('#extraColor_mode').val('edit');
            $('#extraColor_color_id').val(color.color_id || '');
            $('#extraColor_option_id').val(color.option_id || currentExtraOptionIdForColors || '');
            $('#extraColor_name').val(color.color_name || '');
            $('#extraColor_code').val(color.color_code || '');
            $('#extraColor_hex').val(color.color_hex || '#FF0000');
            $('#extraColor_sort_order').val(color.sort_order || 0);
        }

        function submitExtraOptionColor() {
            const mode = $('#extraColor_mode').val() || 'add';
            const optionId = $('#extraColor_option_id').val() || currentExtraOptionIdForColors;
            const colorId = $('#extraColor_color_id').val();
            const name = $('#extraColor_name').val();
            const hex = $('#extraColor_hex').val();
            const code = $('#extraColor_code').val();
            const sortOrder = $('#extraColor_sort_order').val() || 0;

            if (!optionId) {
                alert('옵션 ID가 없습니다');
                return;
            }
            if (!name) {
                alert('색상 이름을 입력해주세요');
                return;
            }
            if (!hex) {
                alert('색상 HEX 값을 선택해주세요');
                return;
            }

            let requestData;
            if (mode === 'edit') {
                if (!colorId) {
                    alert('색상 ID가 없습니다');
                    return;
                }
                requestData = {
                    mode: 'updateExtraOptionColorAPI',
                    color_id: colorId,
                    color_name: name,
                    color_code: code,
                    color_hex: hex,
                    sort_order: sortOrder
                };
            } else {
                requestData = {
                    mode: 'addExtraOptionColorAPI',
                    option_id: optionId,
                    color_name: name,
                    color_code: code,
                    color_hex: hex,
                    sort_order: sortOrder
                };
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('색상 정보가 저장되었습니다');
                        resetExtraOptionColorForm();
                        loadExtraOptionColors(optionId);
                    } else {
                        alert('저장 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('옵션 색상 저장 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('저장 요청 실패');
                }
            });
        }

        function deleteExtraOptionColor(colorId) {
            if (!colorId) {
                return;
            }
            if (!confirm('이 색상을 삭제하시겠습니까?')) {
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'deleteExtraOptionColorAPI',
                    color_id: colorId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('색상이 삭제되었습니다');
                        if (currentExtraOptionIdForColors) {
                            loadExtraOptionColors(currentExtraOptionIdForColors);
                        }
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('옵션 색상 삭제 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('삭제 요청 실패');
                }
            });
        }
        
        // 모달 외부 클릭 시 닫기
        $(document).on('click', '.modal', function(e) {
            if (e.target === this) {
                hideAddColorModal();
            }
        });
        
        // ============================================
        // 맞춤 기능 스위치
        // ============================================
        
        // 맞춤 기능 상태 로드
        function loadCustomizationStatus() {
            console.log('맞춤 기능 상태 로드 시작', { goodsId: goodsId });
            
            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: { 
                    mode: 'getCustomizationStatusAPI',
                    goods_id: goodsId 
                },
                dataType: 'json',
                success: function(response) {
                    console.log('맞춤 기능 상태 로드 성공', response);
                    
                    if (response.success) {
                        const isCustomizable = response.data.is_customizable == 1;
                        $('#isCustomizableToggle').prop('checked', isCustomizable);
                        updateCustomizationStatusUI(isCustomizable);
                    } else {
                        console.warn('맞춤 기능 상태 가져오기 실패', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('맞춤 기능 상태 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    // 오류 표시 안 함, 로그만 기록
                }
            });
        }
        
        // 맞춤 기능 상태 UI 업데이트
        function updateCustomizationStatusUI(isEnabled) {
            const statusDiv = $('#customizationStatus');
            if (isEnabled) {
                statusDiv.html('✅ 맞춤 기능 활성화됨 - 사용자가 상품 상세 페이지에서 맞춤 입구 버튼을 볼 수 있습니다').css({
                    'background-color': '#d4edda',
                    'color': '#155724',
                    'border': '1px solid #c3e6cb'
                }).show();
            } else {
                statusDiv.html('⚠️ 맞춤 기능 비활성화됨 - 사용자가 상품 맞춤을 할 수 없습니다').css({
                    'background-color': '#fff3cd',
                    'color': '#856404',
                    'border': '1px solid #ffeeba'
                }).show();
            }
        }
        
        // 맞춤 기능 스위치 이벤트 바인딩
        $(document).on('change', '#isCustomizableToggle', function() {
            const isChecked = $(this).is(':checked');
            console.log('맞춤 기능 스위치 상태 변경', { 
                isChecked: isChecked,
                goodsId: goodsId 
            });
            
            const confirmMsg = isChecked 
                ? '맞춤 기능을 활성화하시겠습니까?\n\n활성화 후 사용자는 상품 상세 페이지에서 "상품 맞춤하기" 버튼을 볼 수 있어 맞춤 작업을 할 수 있습니다.' 
                : '맞춤 기능을 비활성화하시겠습니까?\n\n비활성화 후 사용자는 맞춤 기능을 사용할 수 없습니다.';
            
            if (!confirm(confirmMsg)) {
                // 사용자가 취소, 원래 상태로 복원
                $(this).prop('checked', !isChecked);
                return;
            }
            
            updateCustomizationStatus(isChecked);
        });
        
        // 맞춤 기능 상태 업데이트
        function updateCustomizationStatus(isEnabled) {
            console.log('맞춤 기능 상태 업데이트 시작', {
                goodsId: goodsId,
                isEnabled: isEnabled
            });
            
            // 저장 중 상태 표시
            $('#customizationStatus').html('⏳ 저장 중...').css({
                'background-color': '#d1ecf1',
                'color': '#0c5460',
                'border': '1px solid #bee5eb'
            }).show();
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'updateCustomizationStatusAPI',
                    goods_id: goodsId,
                    is_customizable: isEnabled ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    console.log('맞춤 기능 상태 업데이트 성공', response);
                    
                    if (response.success) {
                        alert(isEnabled ? '✅ 맞춤 기능이 활성화되었습니다!' : '⚠️ 맞춤 기능이 비활성화되었습니다!');
                        updateCustomizationStatusUI(isEnabled);
                    } else {
                        alert('업데이트 실패: ' + response.message);
                        // 원래 상태로 복원
                        $('#isCustomizableToggle').prop('checked', !isEnabled);
                        updateCustomizationStatusUI(!isEnabled);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('맞춤 기능 상태 업데이트 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('업데이트 요청 실패, 다시 시도해주세요');
                    // 원래 상태로 복원
                    $('#isCustomizableToggle').prop('checked', !isEnabled);
                    // 상태 표시 숨기기
                    $('#customizationStatus').hide();
                }
            });
        }
        
        // ============================================
        // 맞춤 사이즈 기능（多尺寸）
        // ============================================
        
        function loadCustomSizes() {
            console.log('맞춤 사이즈 목록 로드 시작', { goodsId: goodsId });
            
            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: { 
                    mode: 'getCustomSizesAPI',
                    goods_id: goodsId 
                },
                dataType: 'json',
                success: function(response) {
                    console.log('맞춤 사이즈 목록 로드 성공', response);
                    
                    if (response.success && response.data && response.data.length > 0) {
                        renderCustomSizeTable(response.data);
                    } else {
                        $('#customSizeTableBody').html('<tr><td colspan="6" style="text-align:center;">등록된 맞춤 사이즈가 없습니다</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('맞춤 사이즈 목록 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    $('#customSizeTableBody').html('<tr><td colspan="6" style="text-align:center;">로드 실패</td></tr>');
                }
            });
        }
        
        function renderCustomSizeTable(list) {
            var html = '';
            list.forEach(function(item, index) {
                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td>' + (item.size_name || '') + '</td>';
                html += '<td>' + (item.size_width || 0) + '</td>';
                html += '<td>' + (item.size_height || 0) + '</td>';
                html += '<td>' + (item.status == 1 ? '사용' : '중지') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary" style="margin-right:5px;" onclick="editCustomSize(' + JSON.stringify(item).replace(/"/g, '&quot;') + ')">수정</button>';
                html += '<button class="btn btn-danger" onclick="deleteCustomSize(' + item.custom_size_id + ')">삭제</button>';
                html += '</td>';
                html += '</tr>';
            });
            $('#customSizeTableBody').html(html);
        }

        function resetCustomSizeForm() {
            $('#custom_size_id').val('');
            $('#size_name').val('');
            $('#size_width').val('');
            $('#size_height').val('');
            $('#size_status').prop('checked', true);
                        }
                        
        function showCustomSizeModal() {
            resetCustomSizeForm();
            $('#customSizeModal').show();
        }

        function hideCustomSizeModal() {
            $('#customSizeModal').hide();
        }

        function editCustomSize(item) {
            $('#custom_size_id').val(item.custom_size_id);
            $('#size_name').val(item.size_name || '');
            $('#size_width').val(item.size_width || '');
            $('#size_height').val(item.size_height || '');
            $('#size_status').prop('checked', item.status == 1);
            $('#customSizeModal').show();
            }
            
        function submitCustomSize() {
            var id   = $('#custom_size_id').val();
            var name = $('#size_name').val();
            var w    = $('#size_width').val();
            var h    = $('#size_height').val();
            var use  = $('#size_status').is(':checked') ? 1 : 0;

            if (!name) {
                alert('사이즈 이름을 입력해주세요');
                return;
            }
            if (!w || !h) {
                alert('너비와 높이를 모두 입력해주세요');
                return;
            }
            
            var mode  = id ? 'updateCustomSizeListAPI' : 'addCustomSizeAPI';
            var data;

            if (!id) {
                data = {
                    mode: mode,
                    goods_id: goodsId,
                    size_name: name,
                    size_width: w,
                    size_height: h,
                    size_unit: 'mm',
                    status: use
                };
            } else {
                data = {
                    mode: mode,
                    sizes: [{
                        custom_size_id: id,
                        size_name: name,
                        size_width: w,
                        size_height: h,
                        status: use
                    }]
                };
            }
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('사이즈가 저장되었습니다');
                        hideCustomSizeModal();
                        loadCustomSizes();
                    } else {
                        alert('저장 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('맞춤 사이즈 저장 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('저장 요청 실패');
                }
            });
        }
        
        function deleteCustomSize(id) {
            if (!id) return;
            if (!confirm('이 맞춤 사이즈를 삭제하시겠습니까?')) {
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'deleteCustomSizeAPI',
                    custom_size_id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('사이즈가 삭제되었습니다');
                        loadCustomSizes();
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('맞춤 사이즈 삭제 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('삭제 요청 실패');
                }
            });
        }

        // ============================================
        // 부가 상품 이미지 관리 (SVG)
        // ============================================

        function loadExtraImages() {
            console.log('부가 상품 이미지 목록 로드 시작', { goodsId: goodsId });

            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: {
                    mode: 'getExtraImagesAPI',
                    goods_id: goodsId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('부가 상품 이미지 목록 로드 성공', response);

                    if (response.success && response.data && response.data.length > 0) {
                        renderExtraImageTable(response.data);
                    } else {
                        $('#extraImageTableBody').html('<tr><td colspan="5" style="text-align:center;">이미지가 없습니다</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('부가 상품 이미지 목록 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    $('#extraImageTableBody').html('<tr><td colspan="5" style="text-align:center;">로드 실패</td></tr>');
            }
            });
        }

        function renderExtraImageTable(list) {
            var html = '';
            list.forEach(function (img, index) {
                var name = img.image_name || '';
                var path = img.image_path || '';

                // 单引号转义，避免拼接到 onclick 字符串时报错
                var escName = name.replace(/'/g, "\\'");
                var escPath = path.replace(/'/g, "\\'");

                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td>' + name + '</td>';
                html += '<td>';
                if (path) {
                    html += '<img src="' + path + '" style="max-width:60px; max-height:60px; border:1px solid #ddd;">';
                } else {
                    html += '<span style="color:#999;">-</span>';
            }
                html += '</td>';
                html += '<td>' + path + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary" style="margin-right:5px;" onclick="showEditExtraImageModal('
                    + img.image_id + ', \'' + escName + '\', \'' + escPath + '\')">수정</button>';
                html += '<button class="btn btn-danger" onclick="deleteExtraImage(' + img.image_id + ')">삭제</button>';
                html += '</td>';
                html += '</tr>';
            });
            $('#extraImageTableBody').html(html);
        }

        function uploadExtraImage() {
            var fileInput = document.getElementById('extraImageFile');
            if (!fileInput || fileInput.files.length === 0) {
                alert('업로드할 SVG 파일을 선택해주세요');
                return;
            }
            
            var file = fileInput.files[0];
            console.log('부가 상품 이미지 업로드 시작', {
                goodsId: goodsId,
                fileName: file.name,
                fileSize: file.size
            });

            var formData = new FormData();
            formData.append('mode', 'uploadExtraImageAPI');
            formData.append('goods_id', goodsId);
            formData.append('extra_image_file', file);
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('부가 상품 이미지 업로드 응답', response);
                    if (response.success) {
                        alert('부가 상품 이미지 업로드 성공');
                        fileInput.value = '';
                        $('#extraImageFileName').text('선택된 파일 없음');
                        loadExtraImages();
                    } else {
                        alert('업로드 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('부가 상품 이미지 업로드 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('업로드 요청 실패');
                }
            });
        }

        function deleteExtraImage(imageId) {
            if (!imageId) return;
            if (!confirm('이 이미지를 삭제하시겠습니까?')) {
                return;
            }
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'deleteExtraImageAPI',
                    image_id: imageId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('부가 상품 이미지 삭제 응답', response);
                    if (response.success) {
                        alert('이미지가 삭제되었습니다');
                        loadExtraImages();
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('부가 상품 이미지 삭제 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('삭제 요청 실패');
                }
            });
        }

        // 부가 상품 이미지 이름 수정 모달 표시
        function showEditExtraImageModal(imageId, imageName, imagePath) {
            $('#extraImage_id').val(imageId || '');
            $('#extraImage_name').val(imageName || '');
            $('#extraImage_path').val(imagePath || '');
            $('#extraImageModal').show();
        }

        function hideExtraImageModal() {
            $('#extraImageModal').hide();
            var form = document.getElementById('extraImageForm');
            if (form) {
                form.reset();
            }
        }

        function submitEditExtraImage() {
            var id   = $('#extraImage_id').val();
            var name = $('#extraImage_name').val();

            if (!id) {
                alert('이미지 ID가 없습니다');
                return;
            }
            if (!name) {
                alert('이미지 이름을 입력해주세요');
                return;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'updateExtraImageAPI',
                    image_id: id,
                    image_name: name
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        alert('이미지 이름이 수정되었습니다');
                        hideExtraImageModal();
                        loadExtraImages();
                    } else {
                        alert('수정 실패: ' + res.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('부가 이미지 수정 요청 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('수정 요청 실패');
                }
            });
        }
        function loadWorkGuide() {
            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: {
                    mode: 'getWorkGuideAPI',
                    goods_id: goodsId
                },
                dataType: 'json',
                success: function(response) {
                    const path = response.success && response.data ? response.data.work_guide_path : null;
                    renderWorkGuidePreview(path);
                },
                error: function() {
                    renderWorkGuidePreview(null);
                }
            });
        }
        
        function renderWorkGuidePreview(path) {
            const container = $('#workGuidePreview');
            if (!path) {
                container.html('<span style="color:#999;">작업 가이드가 없습니다</span>');
                return;
            }
            const lowerPath = path.toLowerCase();
            if (lowerPath.endsWith('.pdf')) {
                container.html('<iframe src="' + path + '" style="width:100%; height:320px; border:none;"></iframe>');
                return;
            }
            if (lowerPath.endsWith('.jpg') || lowerPath.endsWith('.jpeg') || lowerPath.endsWith('.png') || lowerPath.endsWith('.svg')) {
                container.html('<img src="' + path + '" style="max-width:100%; border:1px solid #eee; border-radius:4px;">');
                return;
            }
            container.html('<a href="' + path + '" target="_blank" style="color:#0d6efd;">파일 열기</a>');
        }
        
        function uploadWorkGuide() {
            const fileInput = document.getElementById('workGuideFile');
            if (fileInput.files.length === 0) {
                alert('업로드할 파일을 선택해주세요');
                return;
            }
            const formData = new FormData();
            formData.append('mode', 'uploadWorkGuideAPI');
            formData.append('goods_id', goodsId);
            formData.append('work_guide_file', fileInput.files[0]);
            
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('작업 가이드 업로드 성공');
                        renderWorkGuidePreview(response.data ? response.data.work_guide_path : null);
                        fileInput.value = '';
                        document.getElementById('workGuideFileName').textContent = '선택된 파일 없음';
                    } else {
                        alert('업로드 실패: ' + response.message);
                    }
                },
                error: function() {
                    alert('업로드 요청 실패');
                }
            });
        }
        
        function clearWorkGuide() {
            if (!confirm('작업 가이드를 삭제하시겠습니까?')) {
                return;
            }
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'uploadWorkGuideAPI',
                    goods_id: goodsId,
                    clear_work_guide: 1
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('작업 가이드 삭제 성공');
                        renderWorkGuidePreview(null);
                        const fileInput = document.getElementById('workGuideFile');
                        fileInput.value = '';
                        document.getElementById('workGuideFileName').textContent = '선택된 파일 없음';
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function() {
                    alert('삭제 요청 실패');
                }
            });
        }

        // ============================================
        // 맞춤 템플릿 & 커스텀 영역 관리
        // ============================================
        function loadCustomTemplateAndRegions() {
            console.log('맞춤 템플릿 & 커스텀 영역 로드 시작', { goodsId: goodsId });
            $.ajax({
                url: '../custom_color.php',
                method: 'GET',
                data: {
                    mode: 'getCustomTemplateAPI',
                    goods_id: goodsId
                },
                dataType: 'json',
                success: function (response) {
                    console.log('맞춤 템플릿 로드 응답', response);
                    if (!response.success || !response.data) {
                        $('#clipRegionTableBody').html('<tr><td colspan="8" style="text-align:center;">템플릿 설정이 없습니다</td></tr>');
                        $('#template_viewbox_width').val('');
                        $('#template_viewbox_height').val('');
                        return;
                    }
                    var tpl = response.data.template || {};
                    var regions = response.data.regions || [];
                    $('#template_viewbox_width').val(tpl.viewbox_width || '');
                    $('#template_viewbox_height').val(tpl.viewbox_height || '');
                    renderClipRegionTable(regions);
                },
                error: function (xhr, status, error) {
                    console.error('맞춤 템플릿 로드 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    $('#clipRegionTableBody').html('<tr><td colspan="8" style="text-align:center;">로드 실패</td></tr>');
                }
            });
        }

        function renderClipRegionTable(list) {
            if (!list || list.length === 0) {
                $('#clipRegionTableBody').html('<tr><td colspan="8" style="text-align:center;">등록된 커스텀 영역이 없습니다</td></tr>');
                return;
            }
            var html = '';
            list.forEach(function (item) {
                var type = item.type || '';
                var x = item.x != null ? item.x : '';
                var y = item.y != null ? item.y : '';
                var w = item.w != null ? item.w : '';
                var h = item.h != null ? item.h : '';
                var d = item.d || '';
                var sort = item.sort_order != null ? item.sort_order : (item.sort || 0);
                var esc = JSON.stringify(item).replace(/"/g, '&quot;');
                html += '<tr>';
                html += '<td>' + sort + '</td>';
                html += '<td>' + type + '</td>';
                html += '<td>' + x + '</td>';
                html += '<td>' + y + '</td>';
                html += '<td>' + w + '</td>';
                html += '<td>' + h + '</td>';
                html += '<td style="max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + d.replace(/"/g, '&quot;') + '">' + d + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary" style="margin-right:5px;" onclick="showClipRegionModal(' + esc + ')">수정</button>';
                html += '<button class="btn btn-danger" onclick="deleteClipRegion(' + (item.clip_region_id || item.sno) + ')">삭제</button>';
                html += '</td>';
                html += '</tr>';
            });
            $('#clipRegionTableBody').html(html);
        }

        function resetClipRegionForm() {
            $('#clip_region_id').val('');
            $('#clip_type').val('rect');
            $('#clip_x').val('');
            $('#clip_y').val('');
            $('#clip_w').val('');
            $('#clip_h').val('');
            $('#clip_d').val('');
            $('#clip_sort').val('0');
            updateClipTypeFields();
        }

        function showClipRegionModal(item) {
            if (item) {
                $('#clip_region_id').val(item.clip_region_id || item.sno || '');
                $('#clip_type').val(item.type || 'rect');
                $('#clip_x').val(item.x != null ? item.x : '');
                $('#clip_y').val(item.y != null ? item.y : '');
                $('#clip_w').val(item.w != null ? item.w : '');
                $('#clip_h').val(item.h != null ? item.h : '');
                $('#clip_d').val(item.d || '');
                $('#clip_sort').val(item.sort_order != null ? item.sort_order : (item.sort || 0));
            } else {
                resetClipRegionForm();
            }
            updateClipTypeFields();
            $('#clipRegionModal').show();
        }

        function hideClipRegionModal() {
            $('#clipRegionModal').hide();
        }

        function updateClipTypeFields() {
            var type = $('#clip_type').val();
            if (type === 'path') {
                $('#clip_path_fields').show();
                $('#clip_rect_fields').hide();
            } else {
                $('#clip_rect_fields').show();
                $('#clip_path_fields').hide();
            }
        }

        $('#clip_type').on('change', function () {
            updateClipTypeFields();
        });

        function saveCustomTemplateViewBox() {
            var vw = $('#template_viewbox_width').val();
            var vh = $('#template_viewbox_height').val();
            if (!vw || !vh) {
                alert('viewBox 폭과 높이를 모두 입력해주세요');
                return;
            }
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'saveCustomTemplateViewBoxAPI',
                    goods_id: goodsId,
                    viewbox_width: vw,
                    viewbox_height: vh
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        alert('viewBox 정보가 저장되었습니다');
                        loadCustomTemplateAndRegions();
                    } else {
                        alert('저장 실패: ' + (res.message || '알 수 없는 오류'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('viewBox 저장 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('저장 요청 실패');
                }
            });
        }

        function submitClipRegion() {
            var id = $('#clip_region_id').val();
            var type = $('#clip_type').val();
            var x = $('#clip_x').val();
            var y = $('#clip_y').val();
            var w = $('#clip_w').val();
            var h = $('#clip_h').val();
            var d = $('#clip_d').val();
            var sort = $('#clip_sort').val() || 0;

            if (type === 'rect') {
                if (!x || !y || !w || !h) {
                    alert('rect 타입일 때 X/Y/W/H 를 모두 입력해주세요');
                    return;
                }
            } else if (type === 'path') {
                if (!d) {
                    alert('path 타입일 때 Path d 문자열을 입력해주세요');
                    return;
                }
            }

            var mode = id ? 'updateClipRegionAPI' : 'addClipRegionAPI';
            var payload = {
                mode: mode,
                goods_id: goodsId,
                type: type,
                x: x,
                y: y,
                w: w,
                h: h,
                d: d,
                sort_order: sort
            };
            if (id) {
                payload.clip_region_id = id;
            }

            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: payload,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        alert('커스텀 영역이 저장되었습니다');
                        hideClipRegionModal();
                        loadCustomTemplateAndRegions();
                    } else {
                        alert('저장 실패: ' + (res.message || '알 수 없는 오류'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('커스텀 영역 저장 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('저장 요청 실패');
                }
            });
        }

        function deleteClipRegion(id) {
            if (!id) return;
            if (!confirm('이 커스텀 영역을 삭제하시겠습니까?')) {
                return;
            }
            $.ajax({
                url: '../custom_color.php',
                method: 'POST',
                data: {
                    mode: 'deleteClipRegionAPI',
                    clip_region_id: id
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        alert('커스텀 영역이 삭제되었습니다');
                        loadCustomTemplateAndRegions();
                    } else {
                        alert('삭제 실패: ' + (res.message || '알 수 없는 오류'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('커스텀 영역 삭제 실패', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('삭제 요청 실패');
                }
            });
        }
    </script>
</body>
</html>

