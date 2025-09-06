<?php
// 初始化测试数据
if (!isset($categoryList) || empty($categoryList)) {
    $categoryList = [
        [
            'category_id' => 1,
            'category_name' => '测试分类',
            'category_description' => '这是一个测试分类',
            'parent_id' => 0,
            'sort_order' => 1,
            'status' => 1,
            'pattern_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// 初始化图案测试数据 - 与后端API返回格式保持一致
if (!isset($patternList) || empty($patternList)) {
    $patternList = [
        [
            'pattern_id' => 1,
            'pattern_name' => '测试图案1',
            'pattern_thumbnail' => '/assets/images/pattern1_thumb.jpg',
            'pattern_category' => '测试分类',
            'pattern_group' => '默认分组',
            'uploader' => 'admin',
            'file_size' => '2.5 MB',
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'pattern_id' => 2,
            'pattern_name' => '测试图案2',
            'pattern_thumbnail' => '/assets/images/pattern2_thumb.jpg',
            'pattern_category' => '设计分类',
            'pattern_group' => '创意组',
            'uploader' => 'designer',
            'file_size' => '1.8 MB',
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
}

echo '<!-- $categoryList count: ' . count($categoryList) . ' -->';
echo '<!-- $patternList count: ' . count($patternList) . ' -->';
?>
<!-- 顶部导航栏，仅图案管理 -->
<div class="godo-header">
    <span class="godo-logo">Godomall</span>
    <nav class="godo-topnav">
        <button class="godo-topnav-tab active" id="topnav-pattern">图案管理</button>
        <a class="godo-topnav-tab" href="category_manage.php" style="text-decoration:none;">分类管理</a>
    </nav>
</div>
<style>
.godo-header {
    background: #393939;
    color: #fff;
    height: 48px;
    line-height: 48px;
    padding: 0 24px;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 2px solid #e5e5e5;
    display: flex;
    align-items: center;
}
.godo-logo {
    font-family: 'Segoe UI', 'Arial', 'Malgun Gothic', '맑은 고딕', sans-serif;
    font-size: 22px;
    font-weight: 900;
    letter-spacing: 1px;
    color: #ff3c36;
    margin-right: 32px;
    text-shadow: 1px 1px 0 #fff2, 0 1px 2px #2224;
}
.godo-topnav {
    display: flex;
    gap: 0;
}
.godo-topnav-tab {
    background: none;
    border: none;
    outline: none;
    color: #fff;
    font-size: 16px;
    font-weight: 500;
    padding: 0 28px;
    height: 48px;
    line-height: 48px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: background 0.2s, color 0.2s, border-bottom 0.2s;
}
.godo-topnav-tab.active {
    background: #ff3c36;
    color: #fff;
    border-bottom: 3px solid #ff3c36;
}
.godo-section {
    max-width: 1200px;
    margin: 32px auto 0 auto;
    background: #fff;
    border: 1px solid #e5e5e5;
    padding: 32px 40px 40px 40px;
    min-height: 600px;
}
.godo-toolbar {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}
.godo-btn {
    background: #fff;
    border: 1px solid #bbb;
    color: #222;
    padding: 6px 18px;
    font-size: 14px;
    border-radius: 2px;
    cursor: pointer;
    transition: background 0.2s;
}
.godo-btn-primary {
    background: #d32f2f;
    color: #fff;
    border-color: #d32f2f;
}
.godo-btn-batch {
    background: #f5f5f5;
    color: #666;
    border-color: #ccc;
}
.godo-btn-search {
    background: #393939;
    color: #fff;
    border-color: #393939;
    margin-left: 12px;
}
.godo-btn:hover {
    background: #f0f0f0;
}
.godo-btn-primary:hover {
    background: #b71c1c;
    border-color: #b71c1c;
}
.godo-filter {
    display: flex;
    gap: 24px;
    align-items: center;
    flex-wrap: wrap;
}
.godo-filter label {
    font-size: 14px;
    color: #444;
}
.godo-input {
    height: 28px;
    border: 1px solid #ccc;
    border-radius: 2px;
    padding: 0 8px;
    margin-left: 6px;
    font-size: 14px;
    background: #fff;
}
.godo-table-wrap {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 2px;
    overflow-x: auto;
}
.godo-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    background: #fff;
}
.godo-table th, .godo-table td {
    border-bottom: 1px solid #e5e5e5;
    padding: 10px 8px;
    text-align: center;
}
.godo-table th {
    background: #f8f8f8;
    color: #222;
    font-weight: bold;
}
.godo-table tbody tr:hover {
    background: #f3f6fa;
}
.godo-status {
    display: inline-block;
    min-width: 36px;
    padding: 2px 8px;
    border-radius: 2px;
    font-size: 13px;
    color: #fff;
}
.godo-status-on { background: #4caf50; }
.godo-status-off { background: #bdbdbd; }
.godo-btn-xs {
    padding: 2px 8px;
    font-size: 13px;
    margin-right: 2px;
}
.godo-btn-edit { background: #1976d2; color: #fff; border-color: #1976d2; }
.godo-btn-edit:hover { background: #0d47a1; border-color: #0d47a1; }
.godo-btn-view { background: #fff; color: #1976d2; border-color: #1976d2; }
.godo-btn-view:hover { background: #e3f2fd; }
.godo-btn-del { background: #fff; color: #d32f2f; border-color: #d32f2f; }
.godo-btn-del:hover { background: #ffebee; }
.godo-pagination {
    margin: 24px 0 0 0;
    text-align: center;
}
.godo-modal { position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; }
.godo-modal-mask { position: absolute; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(34,34,34,0.25); }
.godo-modal-dialog {
    position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%);
    background: #fff; border-radius: 6px; min-width: 420px; max-width: 96vw;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
}
.godo-modal-header {
    background: #393939; color: #fff; font-size: 16px; font-weight: bold;
    padding: 14px 24px; border-radius: 6px 6px 0 0; display: flex; justify-content: space-between; align-items: center;
}
.godo-modal-close {
    background: none; border: none; color: #fff; font-size: 22px; cursor: pointer;
}
.godo-modal-body { padding: 24px 24px 0 24px; }
.godo-form-row { margin-bottom: 18px; display: flex; align-items: center; }
.godo-form-row label { width: 90px; color: #444; font-size: 14px; }
.godo-form-row .godo-input { flex: 1; }
.godo-form-row textarea.godo-input { resize: vertical; }
.godo-required { color: #d32f2f; margin-left: 2px; }
.godo-tip { color: #888; font-size: 12px; margin-top: 2px; }
.godo-modal-footer {
    padding: 18px 24px 18px 24px; text-align: right; border-top: 1px solid #eee; border-radius: 0 0 6px 6px;
}
.godo-form-error {
    color: #d32f2f;
    font-size: 13px;
    margin-top: 2px;
    margin-bottom: 0;
}

/* 图片预览弹窗样式 */
.godo-image-info-row {
    display: flex;
    margin-bottom: 8px;
    font-size: 14px;
    line-height: 1.5;
}

.godo-image-info-row label {
    width: 80px;
    color: #666;
    font-weight: 500;
    flex-shrink: 0;
}

.godo-image-info-row span {
    color: #333;
    flex: 1;
}

#godoImagePreviewContainer {
    position: relative;
    display: inline-block;
    max-width: 100%;
}

#godoImagePreviewImg {
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

#godoImagePreviewImg:hover {
    transform: scale(1.02);
}

#godoImagePreviewInfo {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 15px;
    margin-top: 20px;
}

/* 图片加载状态 */
.godo-image-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: #666;
    font-size: 14px;
}

.godo-image-loading::before {
    content: '';
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
<!-- 引入jQuery库，确保$可用 -->
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<div id="section-pattern" class="godo-section">
    <!-- Godomall风格工具栏 -->
    <div class="godo-toolbar">
        <button class="godo-btn godo-btn-primary">新增图案</button>
        <button class="godo-btn">导入</button>
        <button class="godo-btn">导出</button>
        <button class="godo-btn godo-btn-batch">批量操作</button>
    </div>
    <!-- Godomall风格筛选区 -->
    <form class="godo-filter" style="margin-top:20px;">
        <label>图案名称 <input type="text" class="godo-input" name="pattern_name" placeholder="请输入图案名称"></label>
        <label>分类
            <select class="godo-input" name="category" id="categoryFilter">
                <option value="">全部</option>
                <?php if (!empty($categoryList)) : ?>
                <?php foreach ($categoryList as $category) : ?>
                <option value="<?=htmlspecialchars($category['category_id'])?>"><?=htmlspecialchars($category['category_name'])?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </label>
        <label>状态
            <select class="godo-input" name="status">
                <option value="">全部</option>
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
        </label>
        <button type="submit" class="godo-btn godo-btn-search">搜索</button>
    </form>
    <!-- Godomall风格表格区域（完善数据渲染与操作按钮） -->
    <div class="godo-table-wrap" style="margin-top:24px;">
        <table class="godo-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox"></th>
                    <th>图案预览</th>
                    <th>图案名称</th>
                    <th>分类</th>
                    <th>分组</th>
                    <th>上传者</th>
                    <th>文件大小</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($patternList)) : ?>
                <?php foreach ($patternList as $pattern) : ?>
                <tr>
                    <td><input type="checkbox" name="pattern_ids[]" value="<?=htmlspecialchars($pattern['pattern_id'])?>"></td>
                    <td><img src="<?=htmlspecialchars($pattern['pattern_thumbnail'])?>" alt="预览" style="width:40px;height:40px;border:1px solid #eee;"></td>
                    <td><?=htmlspecialchars($pattern['pattern_name'])?></td>
                    <td><?=htmlspecialchars($pattern['pattern_category'])?></td>
                    <td><?=htmlspecialchars($pattern['pattern_group'])?></td>
                    <td><?=htmlspecialchars($pattern['uploader'])?></td>
                    <td><?=htmlspecialchars($pattern['file_size'])?></td>
                    <td>
                        <?php if ($pattern['status']) : ?>
                            <span class="godo-status godo-status-on">启用</span>
                        <?php else : ?>
                            <span class="godo-status godo-status-off">禁用</span>
                        <?php endif; ?>
                    </td>
                    <td><?=htmlspecialchars($pattern['created_at'])?></td>
                    <td>
                        <button class="godo-btn godo-btn-xs godo-btn-edit" title="编辑" data-id="<?=htmlspecialchars($pattern['pattern_id'])?>"><span>编辑</span></button>
                        <button class="godo-btn godo-btn-xs godo-btn-view" title="预览" data-id="<?=htmlspecialchars($pattern['pattern_id'])?>"><span>预览</span></button>
                        <button class="godo-btn godo-btn-xs godo-btn-del" title="删除" data-id="<?=htmlspecialchars($pattern['pattern_id'])?>"><span>删除</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else : ?>
                <tr><td colspan="10" style="color:#aaa;text-align:center;">暂无数据</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Godomall风格分页区域 -->
    <div class="godo-pagination">
        <!-- 后续可用PHP分页控件输出 -->
        <?php if (!empty($paginationHtml)) echo $paginationHtml; ?>
    </div>
    <!-- Godomall风格 新增/编辑图案 弹窗（模态框）结构 -->
    <div id="godoPatternModal" class="godo-modal" style="display:none;">
        <div class="godo-modal-mask"></div>
        <div class="godo-modal-dialog">
            <div class="godo-modal-header">
                <span id="godoPatternModalTitle">新增图案</span>
                <button class="godo-modal-close" onclick="$('#godoPatternModal').hide();">×</button>
            </div>
            <form id="godoPatternForm" class="godo-modal-body">
                <div class="godo-form-row">
                    <label>图案名称 <span class="godo-required">*</span></label>
                    <input type="text" name="name" class="godo-input" required maxlength="100">
                </div>
                <div class="godo-form-row">
                    <label>分类 <span class="godo-required">*</span></label>
                    <select name="category" class="godo-input" required>
                        <option value="">请选择</option>
                        <?php if (!empty($categoryList)) : ?>
                        <?php foreach ($categoryList as $category) : ?>
                        <option value="<?=htmlspecialchars($category['category_id'])?>"><?=htmlspecialchars($category['category_name'])?></option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="godo-form-row">
                    <label>分组</label>
                    <input type="text" name="group" class="godo-input" maxlength="20">
                </div>
                <div class="godo-form-row">
                    <label>上传图片 <span class="godo-required">*</span></label>
                    <input type="file" name="image" accept="image/*" class="godo-input" required>
                    <div class="godo-tip">支持JPG/PNG/GIF/SVG，最大5MB</div>
                </div>
                <div class="godo-form-row">
                    <label>描述</label>
                    <textarea name="desc" class="godo-input" rows="2" maxlength="500"></textarea>
                </div>
                <div class="godo-form-row">
                    <label>状态</label>
                    <select name="status" class="godo-input">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
            </form>
            <div class="godo-modal-footer">
                <button class="godo-btn godo-btn-primary" type="submit" form="godoPatternForm">保存</button>
                <button class="godo-btn" type="button" onclick="$('#godoPatternModal').hide();">取消</button>
            </div>
        </div>
    </div>
    <!-- Godomall风格 批量操作 弹窗（模态框）结构 -->
    <div id="godoBatchModal" class="godo-modal" style="display:none;">
        <div class="godo-modal-mask"></div>
        <div class="godo-modal-dialog" style="min-width:340px;max-width:96vw;">
            <div class="godo-modal-header">
                <span>批量操作</span>
                <button class="godo-modal-close" onclick="$('#godoBatchModal').hide();">×</button>
            </div>
            <form id="godoBatchForm" class="godo-modal-body">
                <div class="godo-form-row">
                    <label style="width:auto;">请选择批量操作：</label>
                    <select name="batch_action" class="godo-input" required>
                        <option value="">请选择</option>
                        <option value="enable">批量启用</option>
                        <option value="disable">批量禁用</option>
                        <option value="delete">批量删除</option>
                    </select>
                </div>
                <div class="godo-tip" style="margin-top:8px;">批量操作将对已勾选的图案生效，请谨慎操作。</div>
            </form>
            <div class="godo-modal-footer">
                <button class="godo-btn godo-btn-primary" type="submit" form="godoBatchForm">确定</button>
                <button class="godo-btn" type="button" onclick="$('#godoBatchModal').hide();">取消</button>
            </div>
        </div>
    </div>
    <!-- Godomall风格 导入/导出 弹窗（模态框）结构 -->
    <div id="godoImportExportModal" class="godo-modal" style="display:none;">
        <div class="godo-modal-mask"></div>
        <div class="godo-modal-dialog" style="min-width:380px;max-width:96vw;">
            <div class="godo-modal-header">
                <span>导入/导出图案数据</span>
                <button class="godo-modal-close" onclick="$('#godoImportExportModal').hide();">×</button>
            </div>
            <div class="godo-modal-body">
                <div class="godo-form-row">
                    <label style="width:auto;">导入文件：</label>
                    <input type="file" name="import_file" accept=".csv,.xls,.xlsx" class="godo-input">
                </div>
                <div class="godo-tip" style="margin-top:8px;">
                    支持CSV、Excel格式，文件大小不超过5MB。请下载模板后按要求填写数据。
                </div>
                <div style="margin:16px 0 0 0;">
                    <button class="godo-btn godo-btn-primary" type="button">导入</button>
                    <button class="godo-btn" type="button">下载模板</button>
                </div>
                <hr style="margin:24px 0; border:none; border-top:1px solid #eee;">
                <div>
                    <button class="godo-btn godo-btn-primary" type="button">导出全部数据</button>
                </div>
            </div>
            <div class="godo-modal-footer">
                <button class="godo-btn" type="button" onclick="$('#godoImportExportModal').hide();">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 图片预览弹窗 -->
<div id="godoImagePreviewModal" class="godo-modal" style="display:none;">
    <div class="godo-modal-mask"></div>
    <div class="godo-modal-dialog" style="min-width:600px;max-width:90vw;max-height:90vh;">
        <div class="godo-modal-header">
            <span id="godoImagePreviewTitle">图片预览</span>
            <button class="godo-modal-close" onclick="$('#godoImagePreviewModal').hide();">×</button>
        </div>
        <div class="godo-modal-body" style="text-align:center;padding:20px;">
            <div id="godoImagePreviewContainer">
                <img id="godoImagePreviewImg" src="" alt="预览图片" style="max-width:100%;max-height:400px;border:1px solid #eee;">
            </div>
            <div id="godoImagePreviewInfo" style="margin-top:15px;text-align:left;">
                <div class="godo-image-info-row">
                    <label>图片名称：</label>
                    <span id="godoImageName"></span>
                </div>
                <div class="godo-image-info-row">
                    <label>文件大小：</label>
                    <span id="godoImageSize"></span>
                </div>
                <div class="godo-image-info-row">
                    <label>图片尺寸：</label>
                    <span id="godoImageDimensions"></span>
                </div>
                <div class="godo-image-info-row">
                    <label>上传时间：</label>
                    <span id="godoImageUploadTime"></span>
                </div>
            </div>
        </div>
        <div class="godo-modal-footer">
            <button class="godo-btn godo-btn-primary" id="godoImageDownloadBtn">下载图片</button>
            <button class="godo-btn" onclick="$('#godoImagePreviewModal').hide();">关闭</button>
        </div>
    </div>
</div>

<script>
// ========================================
// 基础AJAX工具函数
// ========================================

// AJAX请求基础配置
const API_BASE_URL = '/Custom/Controller/Admin/Custom/CustomPatternController.php?action=';
const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content') || '';

// 通用AJAX请求函数
function apiRequest(url, method = 'GET', data = null) {
    return $.ajax({
        url: API_BASE_URL + url,
        method: method,
        data: data,
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json'
        }
    }).fail(function(xhr, status, error) {
        console.error('API请求失败:', error);
        showMessage('请求失败: ' + (xhr.responseJSON?.message || error), 'error');
    });
}

// 显示消息提示
function showMessage(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // 在页面顶部显示消息
    $('.godo-section').prepend(alertHtml);
    
    // 3秒后自动消失
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 3000);
}

// 加载图案列表数据
function loadPatternList(page = 1, filters = {}) {
    const params = {
        page: page,
        page_size: 20,
        ...filters
    };
    
    // 显示加载状态
    $('.godo-table tbody').html('<tr><td colspan="10" style="text-align:center;padding:40px;color:#999;"><div>加载中...</div></td></tr>');
    
    return apiRequest('getPatternListAPI', 'GET', params)
        .done(function(response) {
            if (response.success) {
                renderPatternTable(response.data.patterns);
                updatePagination(response.data.pagination);
            } else {
                showMessage(response.message || '加载数据失败', 'error');
            }
        });
}

// 渲染图案表格
function renderPatternTable(patterns) {
    if (!patterns || patterns.length === 0) {
        $('.godo-table tbody').html('<tr><td colspan="10" style="color:#aaa;text-align:center;">暂无数据</td></tr>');
        return;
    }
    
    let html = '';
    patterns.forEach(function(pattern) {
        const statusClass = pattern.status ? 'godo-status-on' : 'godo-status-off';
        const statusText = pattern.status ? '启用' : '禁用';
        
        html += `
            <tr>
                <td><input type="checkbox" name="pattern_ids[]" value="${pattern.pattern_id}"></td>
                <td><img src="${pattern.pattern_thumbnail}" alt="预览" style="width:40px;height:40px;border:1px solid #eee;"></td>
                <td>${pattern.pattern_name}</td>
                <td>${pattern.pattern_category}</td>
                <td>${pattern.pattern_group}</td>
                <td>${pattern.uploader}</td>
                <td>${pattern.file_size}</td>
                <td><span class="godo-status ${statusClass}">${statusText}</span></td>
                <td>${pattern.created_at}</td>
                <td>
                    <button class="godo-btn godo-btn-xs godo-btn-edit" title="编辑" data-id="${pattern.pattern_id}"><span>编辑</span></button>
                    <button class="godo-btn godo-btn-xs godo-btn-view" title="预览" data-id="${pattern.pattern_id}"><span>预览</span></button>
                    <button class="godo-btn godo-btn-xs godo-btn-del" title="删除" data-id="${pattern.pattern_id}"><span>删除</span></button>
                </td>
            </tr>
        `;
    });
    
    $('.godo-table tbody').html(html);
    
    // 绑定编辑按钮事件
    bindEditButtonEvents();
}

// 绑定编辑按钮事件
function bindEditButtonEvents() {
    $('.godo-btn-edit[title="编辑"]').off('click').on('click', function() {
        const patternId = $(this).data('id');
        loadPatternForEdit(patternId);
    });
    
    $('.godo-btn-view[title="预览"]').off('click').on('click', function() {
        const patternId = $(this).data('id');
        const patternName = $(this).closest('tr').find('td:eq(2)').text();
        const patternThumb = $(this).closest('tr').find('td:eq(1) img').attr('src');
        showImagePreview(patternId, patternName, patternThumb);
    });
    
    $('.godo-btn-del[title="删除"]').off('click').on('click', function() {
        const patternId = $(this).data('id');
        const patternName = $(this).closest('tr').find('td:eq(2)').text();
        confirmDeletePattern(patternId, patternName);
    });
    
    // 缩略图点击预览
    $('.godo-table tbody img[alt="预览"]').off('click').on('click', function() {
        const $row = $(this).closest('tr');
        const patternId = $row.find('input[name="pattern_ids[]"]').val();
        const patternName = $row.find('td:eq(2)').text();
        const patternThumb = $(this).attr('src');
        showImagePreview(patternId, patternName, patternThumb);
    });
}

// 加载图案数据用于编辑
function loadPatternForEdit(patternId) {
    // 显示加载状态
    $('#godoPatternModalTitle').text('编辑图案');
    $('#godoPatternForm').find('button[type="submit"]').text('更新');
    $('#godoPatternModal').show();
    
    // 获取图案详情
    apiRequest('getPatternDetailAPI', 'GET', {pattern_id: patternId})
        .done(function(response) {
            if (response.success) {
                fillPatternForm(response.data);
            } else {
                showMessage(response.message || '加载图案信息失败', 'error');
                $('#godoPatternModal').hide();
            }
        })
        .fail(function() {
            $('#godoPatternModal').hide();
        });
}

// 填充图案表单数据
function fillPatternForm(pattern) {
    const $form = $('#godoPatternForm');
    
    // 清空表单
    $form[0].reset();
    $('.godo-form-error').remove();
    
    // 填充数据
    $form.find('[name="pattern_id"]').remove(); // 移除旧的ID
    $form.append(`<input type="hidden" name="pattern_id" value="${pattern.pattern_id}">`);
    
    $form.find('[name="name"]').val(pattern.pattern_name);
    $form.find('[name="category"]').val(pattern.pattern_category);
    $form.find('[name="group"]').val(pattern.pattern_group);
    $form.find('[name="desc"]').val(pattern.pattern_description);
    $form.find('[name="status"]').val(pattern.status);
    
    // 图片上传字段设为可选（编辑时）
    $form.find('[name="image"]').removeAttr('required');
}

// 显示图片预览
function showImagePreview(patternId, patternName, patternThumb) {
    // 显示预览弹窗
    $('#godoImagePreviewModal').show();
    $('#godoImagePreviewTitle').text(`图片预览 - ${patternName}`);
    
    // 显示加载状态
    $('#godoImagePreviewContainer').html('<div class="godo-image-loading">加载中...</div>');
    $('#godoImagePreviewInfo').hide();
    
    // 获取图案详细信息
    apiRequest('getPatternDetailAPI', 'GET', {pattern_id: patternId})
        .done(function(response) {
            if (response.success) {
                loadImagePreview(response.data);
            } else {
                showMessage(response.message || '加载图片信息失败', 'error');
                $('#godoImagePreviewModal').hide();
            }
        })
        .fail(function() {
            $('#godoImagePreviewModal').hide();
        });
}

// 加载图片预览内容
function loadImagePreview(patternData) {
    const $container = $('#godoImagePreviewContainer');
    const $img = $('#godoImagePreviewImg');
    const $info = $('#godoImagePreviewInfo');
    
    // 设置图片源
    $img.attr('src', patternData.pattern_url || patternData.thumb);
    
    // 图片加载成功
    $img.on('load', function() {
        $container.html($img);
        $info.show();
        
        // 填充图片信息
        $('#godoImageName').text(patternData.pattern_name || '未知');
        $('#godoImageSize').text(patternData.file_size || '未知');
        $('#godoImageUploadTime').text(patternData.created_at || '未知');
        
        // 获取图片尺寸
        const img = this;
        $('#godoImageDimensions').text(`${img.naturalWidth} × ${img.naturalHeight} 像素`);
        
        // 绑定下载按钮
        bindDownloadButton(patternData.pattern_url, patternData.name);
    });
    
    // 图片加载失败
    $img.on('error', function() {
        $container.html('<div style="color:#999;padding:40px;">图片加载失败</div>');
        $info.hide();
    });
}

// 绑定下载按钮
function bindDownloadButton(imageUrl, imageName) {
    $('#godoImageDownloadBtn').off('click').on('click', function() {
        if (imageUrl) {
            // 创建下载链接
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = imageName || 'pattern_image';
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showMessage('下载已开始', 'success');
        } else {
            showMessage('下载链接无效', 'error');
        }
    });
}

// ========================================
// 事件处理函数
// ========================================

// 更新分页组件
function updatePagination(pagination) {
    if (!pagination || pagination.total_pages <= 1) {
        $('.godo-pagination').html('');
        return;
    }
    
    let html = '<div class="godo-pagination-nav">';
    
    // 上一页
    if (pagination.current_page > 1) {
        html += `<a href="#" class="godo-page-btn" data-page="${pagination.current_page - 1}">&laquo; 上一页</a>`;
    } else {
        html += '<span class="godo-page-btn godo-page-disabled">&laquo; 上一页</span>';
    }
    
    // 页码
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        html += '<a href="#" class="godo-page-btn" data-page="1">1</a>';
        if (startPage > 2) {
            html += '<span class="godo-page-ellipsis">...</span>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i == pagination.current_page) {
            html += `<span class="godo-page-btn godo-page-current">${i}</span>`;
        } else {
            html += `<a href="#" class="godo-page-btn" data-page="${i}">${i}</a>`;
        }
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += '<span class="godo-page-ellipsis">...</span>';
        }
        html += `<a href="#" class="godo-page-btn" data-page="${pagination.total_pages}">${pagination.total_pages}</a>`;
    }
    
    // 下一页
    if (pagination.current_page < pagination.total_pages) {
        html += `<a href="#" class="godo-page-btn" data-page="${pagination.current_page + 1}">下一页 &raquo;</a>`;
    } else {
        html += '<span class="godo-page-btn godo-page-disabled">下一页 &raquo;</span>';
    }
    
    html += '</div>';
    html += `<div class="godo-pagination-info">第 ${pagination.current_page} 页，共 ${pagination.total_pages} 页，每页显示 ${pagination.page_size} 条记录</div>`;
    
    $('.godo-pagination').html(html);
}

// 分页点击事件
$(document).on('click', '.godo-pagination-nav a', function(e) {
    e.preventDefault();
    const page = parseInt($(this).data('page'));
    if (page > 0) {
        loadPatternList(page, getCurrentFilters());
    }
});

// 获取当前筛选条件
function getCurrentFilters() {
    return {
        keyword: $('input[name="pattern_name"]').val().trim(),
        category: $('#categoryFilter').val(),
        status: $('select[name="status"]').val()
    };
}

// 搜索表单提交
$('.godo-filter').on('submit', function(e) {
    e.preventDefault();
    loadPatternList(1, getCurrentFilters());
});

// 清空搜索
$('#godoClearSearch').on('click', function() {
    $('input[name="pattern_name"]').val('');
    $('#categoryFilter').val('');
    $('select[name="status"]').val('');
    loadPatternList(1, {});
});

// 页面加载时初始化数据
$(document).ready(function() {
    loadPatternList(1, {});
});

// 确认删除图案
function confirmDeletePattern(patternId, patternName) {
    if (confirm(`确定要删除图案"${patternName}"吗？此操作不可恢复！`)) {
        deletePattern(patternId);
    }
}

// 删除图案
function deletePattern(patternId) {
    const data = {
        pattern_id: patternId
    };
    
    apiRequest('deletePatternAPI', 'POST', data)
        .done(function(response) {
            if (response.success) {
                showMessage(response.message || '删除成功', 'success');
                loadPatternList(1, getCurrentFilters()); // 重新加载列表
            } else {
                showMessage(response.message || '删除失败', 'error');
            }
        });
}

// 打开新增图案弹窗
$('.godo-btn-primary:contains("新增图案")').on('click', function() {
    $('#godoPatternModalTitle').text('新增图案');
    $('#godoPatternForm').find('button[type="submit"]').text('保存');
    $('#godoPatternForm')[0].reset();
    $('.godo-form-error').remove();
    
    // 移除pattern_id字段（新增模式）
    $('#godoPatternForm [name="pattern_id"]').remove();
    
    // 图片上传字段设为必填（新增时）
    $('#godoPatternForm [name="image"]').attr('required', 'required');
    
    $('#godoPatternModal').show();
});
// 表单前端校验和提交
$('#godoPatternForm').on('submit', function(e) {
    e.preventDefault();
    $('.godo-form-error').remove();
    
    // 表单验证
    if (!validatePatternForm()) {
        return false;
    }
    
    // 获取表单数据
    const formData = new FormData(this);
    const patternId = formData.get('pattern_id');
    
    // 显示提交状态
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.text('提交中...').prop('disabled', true);
    
    // 提交数据
    const action = patternId ? 'updatePatternAPI' : 'addPatternAPI';
    
    $.ajax({
        url: API_BASE_URL + action,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        }
    }).done(function(response) {
        if (response.success) {
            showMessage(response.message || '操作成功', 'success');
            $('#godoPatternModal').hide();
            loadPatternList(1, getCurrentFilters()); // 重新加载列表
        } else {
            showMessage(response.message || '操作失败', 'error');
        }
    }).fail(function(xhr) {
        const errorMsg = xhr.responseJSON?.message || '网络错误，请重试';
        showMessage(errorMsg, 'error');
    }).always(function() {
        submitBtn.text(originalText).prop('disabled', false);
    });
    
    return false;
});

// 表单验证函数
function validatePatternForm() {
    let valid = true;
    const $form = $('#godoPatternForm');
    
    // 校验图案名称
    const name = $form.find('[name="name"]').val().trim();
    if (!name) {
        valid = false;
        $form.find('[name="name"]').after('<div class="godo-form-error">请输入图案名称</div>');
    } else if (name.length > 100) {
        valid = false;
        $form.find('[name="name"]').after('<div class="godo-form-error">名称不能超过100字符</div>');
    }
    
    // 校验分类
    const category = $form.find('[name="category"]').val();
    if (!category) {
        valid = false;
        $form.find('[name="category"]').after('<div class="godo-form-error">请选择分类</div>');
    }
    
    // 校验图片（新增时必填，编辑时可选）
    const patternId = $form.find('[name="pattern_id"]').val();
    const fileInput = $form.find('[name="image"]')[0];
    
    if (!patternId && (!fileInput.files || fileInput.files.length === 0)) {
        valid = false;
        $(fileInput).after('<div class="godo-form-error">请上传图片</div>');
    } else if (fileInput.files && fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        if (['jpg','jpeg','png','gif','svg'].indexOf(ext) === -1) {
            valid = false;
            $(fileInput).after('<div class="godo-form-error">图片格式仅支持JPG/PNG/GIF/SVG</div>');
        }
        if (file.size > 5*1024*1024) {
            valid = false;
            $(fileInput).after('<div class="godo-form-error">图片大小不能超过5MB</div>');
        }
    }
    
    // 校验描述长度
    const desc = $form.find('[name="desc"]').val();
    if (desc && desc.length > 500) {
        valid = false;
        $form.find('[name="desc"]').after('<div class="godo-form-error">描述不能超过500字符</div>');
    }
    
    return valid;
}
// 打开批量操作弹窗
$('.godo-btn-batch').on('click', function() {
    $('#godoBatchForm')[0].reset();
    $('.godo-form-error').remove();
    
    // 检查是否有勾选图案
    const checkedPatterns = $('input[name="pattern_ids[]"]:checked');
    if (checkedPatterns.length === 0) {
        showMessage('请先勾选要操作的图案！', 'error');
        return false;
    }
    
    // 显示选中的图案数量
    $('#godoBatchModal .godo-tip').text(`已选择 ${checkedPatterns.length} 个图案，批量操作将对已勾选的图案生效，请谨慎操作。`);
    
    $('#godoBatchModal').show();
});

// 批量操作表单提交
$('#godoBatchForm').on('submit', function(e) {
    e.preventDefault();
    $('.godo-form-error').remove();
    
    const action = $(this).find('[name="batch_action"]').val();
    if (!action) {
        $(this).find('[name="batch_action"]').after('<div class="godo-form-error">请选择批量操作类型</div>');
        return false;
    }
    
    const checkedPatterns = $('input[name="pattern_ids[]"]:checked');
    const patternIds = checkedPatterns.map(function() {
        return $(this).val();
    }).get();
    
    if (patternIds.length === 0) {
        showMessage('请先勾选要操作的图案！', 'error');
        return false;
    }
    
    // 执行批量操作
    executeBatchOperation(action, patternIds);
    
    $('#godoBatchModal').hide();
    return false;
});

// 执行批量操作
function executeBatchOperation(action, patternIds) {
    let actionName, data, message;
    
    switch (action) {
        case 'enable':
            actionName = 'batchUpdatePatternStatusAPI';
            data = { pattern_ids: patternIds, status: 1 };
            message = '批量启用';
            break;
        case 'disable':
            actionName = 'batchUpdatePatternStatusAPI';
            data = { pattern_ids: patternIds, status: 0 };
            message = '批量禁用';
            break;
        case 'delete':
            if (!confirm(`确定要删除选中的 ${patternIds.length} 个图案吗？此操作不可恢复！`)) {
                return;
            }
            actionName = 'batchDeletePatternAPI';
            data = { pattern_ids: patternIds };
            message = '批量删除';
            break;
        default:
            showMessage('未知的操作类型', 'error');
            return;
    }
    
    // 显示操作状态
    showMessage(`${message}中...`, 'info');
    
    apiRequest(actionName, 'POST', data)
        .done(function(response) {
            if (response.success) {
                showMessage(response.message || `${message}成功`, 'success');
                loadPatternList(1, getCurrentFilters()); // 重新加载列表
            } else {
                showMessage(response.message || `${message}失败`, 'error');
            }
        });
}

// 表格全选/反选功能
$(document).on('change', '.godo-table th input[type="checkbox"]', function() {
    const isChecked = $(this).is(':checked');
    $(this).closest('table').find('tbody input[type="checkbox"]').prop('checked', isChecked);
});

// 状态快速切换功能
$(document).on('click', '.godo-status', function() {
    const $status = $(this);
    const $row = $status.closest('tr');
    const patternId = $row.find('input[name="pattern_ids[]"]').val();
    const currentStatus = $status.hasClass('godo-status-on');
    const newStatus = !currentStatus;
    const statusText = newStatus ? '启用' : '禁用';
    
    if (confirm(`确定要将该图案状态切换为"${statusText}"吗？`)) {
        // 显示切换中状态
        $status.text('切换中...').addClass('godo-status-switching');
        
        // 发送状态更新请求
        const data = {
            pattern_ids: [patternId],
            status: newStatus ? 1 : 0
        };
        
        apiRequest('batchUpdatePatternStatusAPI', 'POST', data)
            .done(function(response) {
                if (response.success) {
                    // 更新状态显示
                    $status.removeClass('godo-status-on godo-status-off godo-status-switching');
                    if (newStatus) {
                        $status.addClass('godo-status-on').text('启用');
                    } else {
                        $status.addClass('godo-status-off').text('禁用');
                    }
                    showMessage(`状态已切换为"${statusText}"`, 'success');
                } else {
                    showMessage(response.message || '状态切换失败', 'error');
                    $status.removeClass('godo-status-switching');
                }
            })
            .fail(function() {
                $status.removeClass('godo-status-switching');
            });
    }
});
// 打开导入/导出弹窗
$('.godo-btn:contains("导入"), .godo-btn:contains("导出")').on('click', function() {
    $('#godoImportExportModal').show();
    $('.godo-form-error').remove();
    $('#godoImportExportModal input[name="import_file"]').val('');
});
// 导入按钮校验
$('#godoImportExportModal .godo-btn-primary:contains("导入")').on('click', function() {
    $('.godo-form-error').remove();
    var fileInput = $('#godoImportExportModal input[name="import_file"]')[0];
    if (!fileInput.files || fileInput.files.length === 0) {
        $(fileInput).after('<div class="godo-form-error">请选择要导入的文件</div>');
        return false;
    }
    var file = fileInput.files[0];
    var ext = file.name.split('.').pop().toLowerCase();
    if ($.inArray(ext, ['csv','xls','xlsx']) === -1) {
        $(fileInput).after('<div class="godo-form-error">文件格式仅支持CSV、Excel</div>');
        return false;
    }
    if (file.size > 5*1024*1024) {
        $(fileInput).after('<div class="godo-form-error">文件大小不能超过5MB</div>');
        return false;
    }
    alert('导入校验通过，可提交数据！');
    $('#godoImportExportModal').hide();
});
// 导出按钮反馈
$('#godoImportExportModal .godo-btn-primary:contains("导出全部数据")').on('click', function() {
    alert('已触发导出全部数据操作！');
    $('#godoImportExportModal').hide();
});
// 下载模板按钮反馈
$('#godoImportExportModal .godo-btn:contains("下载模板")').on('click', function() {
    alert('已触发模板下载操作！');
});
// 后续可扩展编辑逻辑
</script>
