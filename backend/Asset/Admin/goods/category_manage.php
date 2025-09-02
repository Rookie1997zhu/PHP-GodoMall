<?php
// 分类管理测试数据，可后续对接后端
$categoryList = [
    [
        'category_id' => 1,
        'category_name' => '测试分类',
        'category_description' => '这是一个测试分类',
        'parent_id' => 0,
        'parent_name' => null,
        'sort_order' => 1,
        'status' => 1,
        'pattern_count' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'created_at_formatted' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'category_id' => 2,
        'category_name' => '图案分类A',
        'category_description' => '用于存放图案A类型',
        'parent_id' => 0,
        'parent_name' => null,
        'sort_order' => 2,
        'status' => 1,
        'pattern_count' => 5,
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'created_at_formatted' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
    ],
    [
        'category_id' => 3,
        'category_name' => '设计分类B',
        'category_description' => '设计相关的分类',
        'parent_id' => 0,
        'parent_name' => null,
        'sort_order' => 3,
        'status' => 0,
        'pattern_count' => 2,
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'created_at_formatted' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ],
    [
        'category_id' => 4,
        'category_name' => '产品分类C',
        'category_description' => '产品管理分类',
        'parent_id' => 0,
        'parent_name' => null,
        'sort_order' => 4,
        'status' => 1,
        'pattern_count' => 8,
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'created_at_formatted' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
    ],
    [
        'category_id' => 5,
        'category_name' => '营销分类D',
        'category_description' => '营销活动分类',
        'parent_id' => 0,
        'parent_name' => null,
        'sort_order' => 5,
        'status' => 1,
        'pattern_count' => 3,
        'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'created_at_formatted' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
    ]
];

// 分页相关参数
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 3; // 每页显示3条记录
$totalRecords = count($categoryList);
$totalPages = ceil($totalRecords / $perPage);
$startIndex = ($currentPage - 1) * $perPage;
$endIndex = min($startIndex + $perPage, $totalRecords);

// 分页数据切片
$pagedCategoryList = array_slice($categoryList, $startIndex, $perPage);

// 生成分页HTML
function generatePaginationHtml($currentPage, $totalPages, $baseUrl = '') {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="godo-pagination-nav">';
    
    // 上一页
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="godo-page-btn">&laquo; 上一页</a>';
    } else {
        $html .= '<span class="godo-page-btn godo-page-disabled">&laquo; 上一页</span>';
    }
    
    // 页码
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=1" class="godo-page-btn">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="godo-page-ellipsis">...</span>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="godo-page-btn godo-page-current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="godo-page-btn">' . $i . '</a>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span class="godo-page-ellipsis">...</span>';
        }
        $html .= '<a href="' . $baseUrl . '?page=' . $totalPages . '" class="godo-page-btn">' . $totalPages . '</a>';
    }
    
    // 下一页
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="godo-page-btn">下一页 &raquo;</a>';
    } else {
        $html .= '<span class="godo-page-btn godo-page-disabled">下一页 &raquo;</span>';
    }
    
    $html .= '</div>';
    
    // 分页信息
    $html .= '<div class="godo-pagination-info">';
    $html .= '第 ' . $currentPage . ' 页，共 ' . $totalPages . ' 页，每页显示 ' . $GLOBALS['perPage'] . ' 条记录';
    $html .= '</div>';
    
    return $html;
}
?>
<!-- 顶部导航栏 -->
<div class="godo-header">
    <span class="godo-logo">Godomall</span>
    <nav class="godo-topnav">
        <a class="godo-topnav-tab" href="pattern_manage.php" id="topnav-pattern" style="text-decoration:none;">图案管理</a>
        <button class="godo-topnav-tab active" id="topnav-category">分类管理</button>
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
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.godo-status:hover {
    opacity: 0.8;
    transform: scale(1.05);
}
.godo-status-toggle {
    position: relative;
}
.godo-status-toggle:active {
    transform: scale(0.95);
}
.godo-status-on { background: #4caf50; }
.godo-status-off { background: #bdbdbd; }
.godo-status-switching {
    background: #ff9800 !important;
    animation: status-switching 1s infinite;
}
@keyframes status-switching {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.godo-status-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #4caf50;
    color: #fff;
    padding: 12px 20px;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    font-size: 14px;
}
.godo-status-toast-show {
    transform: translateX(0);
}
.godo-sort-mode .godo-table tbody tr {
    cursor: move;
}
.godo-sort-mode .godo-table tbody tr:hover {
    background: #e3f2fd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.godo-sort-handle {
    cursor: move;
    color: #999;
    font-size: 16px;
    margin-right: 8px;
}
.godo-sort-actions {
    display: flex;
    gap: 8px;
    margin-top: 16px;
    justify-content: center;
}
.godo-sort-info {
    background: #e3f2fd;
    border: 1px solid #1976d2;
    border-radius: 4px;
    padding: 12px;
    margin: 16px 0;
    text-align: center;
    color: #1976d2;
    font-size: 14px;
}
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
.godo-pagination-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
}
.godo-page-btn {
    display: inline-block;
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: #fff;
    color: #666;
    text-decoration: none;
    border-radius: 2px;
    font-size: 13px;
    transition: all 0.2s;
    min-width: 32px;
    text-align: center;
}
.godo-page-btn:hover {
    background: #f5f5f5;
    border-color: #ccc;
    color: #333;
}
.godo-page-btn.godo-page-current {
    background: #1976d2;
    color: #fff;
    border-color: #1976d2;
}
.godo-page-btn.godo-page-disabled {
    background: #f5f5f5;
    color: #ccc;
    border-color: #eee;
    cursor: not-allowed;
}
.godo-page-ellipsis {
    padding: 6px 8px;
    color: #999;
    font-size: 13px;
}
.godo-pagination-info {
    font-size: 13px;
    color: #666;
    margin-top: 8px;
}
.godo-form-error {
    color: #d32f2f;
    font-size: 13px;
    margin-top: 2px;
    margin-bottom: 0;
}
/* 弹窗相关样式 */
.godo-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: none;
}
.godo-modal-mask {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}
.godo-modal-dialog {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 400px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
}
.godo-modal-header {
    background: #f8f8f8;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 16px;
    font-weight: bold;
    color: #222;
}
.godo-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
}
.godo-modal-close:hover {
    color: #666;
}
.godo-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}
.godo-modal-footer {
    background: #f8f8f8;
    padding: 16px 20px;
    border-top: 1px solid #e5e5e5;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.godo-form-row {
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
}
.godo-form-row label {
    width: 80px;
    font-size: 14px;
    color: #444;
    margin-right: 12px;
    margin-top: 4px;
    flex-shrink: 0;
}
.godo-form-row .godo-input {
    flex: 1;
    margin-left: 0;
}
.godo-form-row textarea.godo-input {
    height: auto;
    min-height: 60px;
    resize: vertical;
}
.godo-required {
    color: #d32f2f;
    font-weight: bold;
}
.godo-tip {
    font-size: 13px;
    color: #666;
    background: #f5f5f5;
    padding: 8px 12px;
    border-radius: 2px;
    border-left: 3px solid #1976d2;
}
.godo-batch-options {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 16px;
    margin-top: 12px;
}
.godo-batch-options .godo-form-row {
    margin-bottom: 12px;
}
.godo-batch-options .godo-form-row:last-child {
    margin-bottom: 0;
}
.godo-batch-preview {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 12px;
    margin-top: 12px;
    font-size: 13px;
    color: #856404;
}
</style>
<div class="godo-section">
    <!-- 工具栏 -->
    <div class="godo-toolbar">
        <button class="godo-btn godo-btn-primary" id="btn-category-add">新增分类</button>
        <button class="godo-btn godo-btn-batch" id="btn-category-batch">批量操作</button>
        <button class="godo-btn" id="btn-sort-manage">排序管理</button>
    </div>
    <!-- 筛选区 -->
    <form class="godo-filter" id="godoSearchForm" style="margin-top:20px;">
        <label>分类名称 <input type="text" class="godo-input" name="category_name" id="godoSearchInput" placeholder="请输入分类名称"></label>
        <label>状态 
            <select class="godo-input" id="statusFilter">
                <option value="">全部</option>
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
        </label>
        <label>父分类 
            <select class="godo-input" id="parentFilter">
                <option value="">全部</option>
                <option value="0">顶级分类</option>
            </select>
        </label>
        <button type="submit" class="godo-btn godo-btn-search" id="godoSearchBtn">搜索</button>
        <button type="button" class="godo-btn" id="godoClearSearch" style="margin-left:8px;">清空搜索</button>
    </form>
    <!-- 表格骨架 -->
    <div class="godo-table-wrap" style="margin-top:24px;">
        <table class="godo-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox"></th>
                    <th>分类名称</th>
                    <th>描述</th>
                    <th>父分类</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>图案数量</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pagedCategoryList)) : ?>
                <?php foreach ($pagedCategoryList as $cat) : ?>
                <tr>
                    <td><input type="checkbox" name="category_ids[]" value="<?=htmlspecialchars($cat['category_id'])?>"></td>
                    <td><?=htmlspecialchars($cat['category_name'])?></td>
                    <td><?=htmlspecialchars($cat['category_description'])?></td>
                    <td><?=htmlspecialchars($cat['parent_name'] ?: ($cat['parent_id'] == 0 ? '无' : $cat['parent_id']))?></td>
                    <td><?=htmlspecialchars($cat['sort_order'])?></td>
                    <td>
                        <?php if ($cat['status']) : ?>
                            <span class="godo-status godo-status-on godo-status-toggle" data-id="<?=htmlspecialchars($cat['category_id'])?>" data-status="1" title="点击切换状态">启用</span>
                        <?php else : ?>
                            <span class="godo-status godo-status-off godo-status-toggle" data-id="<?=htmlspecialchars($cat['category_id'])?>" data-status="0" title="点击切换状态">禁用</span>
                        <?php endif; ?>
                    </td>
                    <td><?=htmlspecialchars($cat['pattern_count'])?></td>
                    <td><?=htmlspecialchars($cat['created_at_formatted'])?></td>
                    <td>
                        <button class="godo-btn godo-btn-xs godo-btn-edit" title="编辑" data-id="<?=htmlspecialchars($cat['category_id'])?>" data-name="<?=htmlspecialchars($cat['category_name'])?>" data-desc="<?=htmlspecialchars($cat['category_description'])?>" data-parent="<?=htmlspecialchars($cat['parent_id'])?>" data-sort="<?=htmlspecialchars($cat['sort_order'])?>" data-status="<?=htmlspecialchars($cat['status'])?>"><span>编辑</span></button>
                        <button class="godo-btn godo-btn-xs godo-btn-view" title="分配图案"><span>分配图案</span></button>
                        <button class="godo-btn godo-btn-xs godo-btn-del" title="删除"><span>删除</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else : ?>
                <tr><td colspan="9" style="color:#aaa;text-align:center;">暂无数据</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- 分页区域 -->
    <div class="godo-pagination">
        <?php echo generatePaginationHtml($currentPage, $totalPages); ?>
    </div>
</div>
<!-- Godomall风格 新增/编辑分类 弹窗（模态框）结构 -->
<div id="godoCategoryModal" class="godo-modal" style="display:none;">
    <div class="godo-modal-mask"></div>
    <div class="godo-modal-dialog">
        <div class="godo-modal-header">
            <span id="godoCategoryModalTitle">新增分类</span>
            <button class="godo-modal-close" onclick="$('#godoCategoryModal').hide();">×</button>
        </div>
        <form id="godoCategoryForm" class="godo-modal-body">
            <div class="godo-form-row">
                <label>分类名称 <span class="godo-required">*</span></label>
                <input type="text" name="category_name" class="godo-input" required maxlength="100">
            </div>
            <div class="godo-form-row">
                <label>描述</label>
                <textarea name="category_description" class="godo-input" rows="2" maxlength="500"></textarea>
            </div>
            <div class="godo-form-row">
                <label>父分类</label>
                <select name="parent_id" class="godo-input">
                    <option value="0">无</option>
                    <!-- 后续动态填充父分类选项 -->
                </select>
            </div>
            <div class="godo-form-row">
                <label>排序</label>
                <input type="number" name="sort_order" class="godo-input" min="0" max="9999" value="0">
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
            <button class="godo-btn godo-btn-primary" type="submit" form="godoCategoryForm">保存</button>
            <button class="godo-btn" type="button" onclick="$('#godoCategoryModal').hide();">取消</button>
        </div>
    </div>
</div>
<!-- Godomall风格 删除确认 弹窗（模态框）结构 -->
<div id="godoDeleteConfirmModal" class="godo-modal" style="display:none;">
    <div class="godo-modal-mask"></div>
    <div class="godo-modal-dialog" style="min-width:320px;max-width:96vw;">
        <div class="godo-modal-header">
            <span>确认删除</span>
            <button class="godo-modal-close" onclick="$('#godoDeleteConfirmModal').hide();">×</button>
        </div>
        <div class="godo-modal-body" style="padding-bottom:0;">
            <div style="font-size:15px;color:#d32f2f;">确定要删除该分类吗？此操作不可恢复！</div>
        </div>
        <div class="godo-modal-footer">
            <button class="godo-btn godo-btn-primary" type="button" id="btn-delete-confirm">确定</button>
            <button class="godo-btn" type="button" onclick="$('#godoDeleteConfirmModal').hide();">取消</button>
        </div>
    </div>
</div>
<!-- Godomall风格 批量操作 弹窗（模态框）结构 -->
<div id="godoCategoryBatchModal" class="godo-modal" style="display:none;">
    <div class="godo-modal-mask"></div>
    <div class="godo-modal-dialog" style="min-width:340px;max-width:96vw;">
        <div class="godo-modal-header">
            <span>批量操作</span>
            <button class="godo-modal-close" onclick="$('#godoCategoryBatchModal').hide();">×</button>
        </div>
        <form id="godoCategoryBatchForm" class="godo-modal-body">
            <div class="godo-form-row">
                <label style="width:auto;">操作类型：</label>
                <select name="batch_action" class="godo-input" id="batchActionSelect" required>
                    <option value="">请选择操作类型</option>
                    <option value="enable">批量启用</option>
                    <option value="disable">批量禁用</option>
                    <option value="delete">批量删除</option>
                    <option value="sort">批量修改排序</option>
                    <option value="parent">批量修改父分类</option>
                    <option value="status">批量修改状态</option>
                </select>
            </div>
            
            <!-- 批量修改排序 -->
            <div id="batchSortOptions" class="godo-batch-options" style="display:none;">
                <div class="godo-form-row">
                    <label style="width:auto;">排序方式：</label>
                    <select name="sort_method" class="godo-input">
                        <option value="set">设置为指定值</option>
                        <option value="increase">递增排序</option>
                        <option value="decrease">递减排序</option>
                    </select>
                </div>
                <div class="godo-form-row">
                    <label style="width:auto;">排序值：</label>
                    <input type="number" name="sort_value" class="godo-input" min="0" max="9999" value="1">
                </div>
            </div>
            
            <!-- 批量修改父分类 -->
            <div id="batchParentOptions" class="godo-batch-options" style="display:none;">
                <div class="godo-form-row">
                    <label style="width:auto;">父分类：</label>
                    <select name="parent_category" class="godo-input">
                        <option value="">无（顶级分类）</option>
                        <option value="1">测试分类</option>
                        <option value="2">图案分类A</option>
                        <option value="3">设计分类B</option>
                    </select>
                </div>
            </div>
            
            <!-- 批量修改状态 -->
            <div id="batchStatusOptions" class="godo-batch-options" style="display:none;">
                <div class="godo-form-row">
                    <label style="width:auto;">状态：</label>
                    <select name="status_value" class="godo-input">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
            </div>
            
            <div class="godo-tip" style="margin-top:8px;">
                <div>已选择 <span id="selectedCount">0</span> 个分类</div>
                <div>批量操作将对已勾选的分类生效，请谨慎操作。</div>
            </div>
        </form>
        <div class="godo-modal-footer">
            <button class="godo-btn godo-btn-primary" type="submit" form="godoCategoryBatchForm">确定</button>
            <button class="godo-btn" type="button" onclick="$('#godoCategoryBatchModal').hide();">取消</button>
        </div>
    </div>
</div>
<!-- Godomall风格 分配图案 弹窗（模态框）结构 -->
<div id="godoAssignPatternModal" class="godo-modal" style="display:none;">
    <div class="godo-modal-mask"></div>
    <div class="godo-modal-dialog" style="min-width:520px;max-width:96vw;">
        <div class="godo-modal-header">
            <span>分配图案到分类</span>
            <button class="godo-modal-close" onclick="$('#godoAssignPatternModal').hide();">×</button>
        </div>
        <div class="godo-modal-body">
            <div class="godo-form-row">
                <label style="width:auto;">可选图案：</label>
                <!-- 预留图案多选列表，可分页/搜索，后续扩展 -->
                <div class="godo-assign-list" style="flex:1;max-height:260px;overflow:auto;background:#fafbfc;border:1px solid #eee;padding:8px 12px;">
                    <label style="display:block;margin-bottom:6px;">
                        <input type="checkbox" name="assign_pattern[]" value="1"> 图案A
                    </label>
                    <label style="display:block;margin-bottom:6px;">
                        <input type="checkbox" name="assign_pattern[]" value="2"> 图案B
                    </label>
                    <label style="display:block;margin-bottom:6px;">
                        <input type="checkbox" name="assign_pattern[]" value="3"> 图案C
                    </label>
                    <!-- 后续可动态渲染更多图案 -->
                </div>
            </div>
        </div>
        <div class="godo-modal-footer">
            <button class="godo-btn godo-btn-primary" type="button" id="btn-assign-save">保存</button>
            <button class="godo-btn" type="button" onclick="$('#godoAssignPatternModal').hide();">取消</button>
        </div>
    </div>
</div>
  <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  <script>
// 全局配置
var API_BASE_URL = '/admin/custom/custom-pattern-category/';
var currentPage = 1;
var perPage = 3;

// AJAX请求基础函数
function apiRequest(url, method, data, successCallback, errorCallback) {
    var options = {
        url: API_BASE_URL + url,
        type: method,
        dataType: 'json',
        contentType: 'application/json; charset=utf-8',
        success: function(response) {
            if (response.success) {
                if (successCallback) successCallback(response.data);
            } else {
                if (errorCallback) errorCallback(response.message);
                else showError('操作失败：' + response.message);
            }
        },
        error: function(xhr, status, error) {
            var errorMsg = '网络请求失败';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMsg = response.message || errorMsg;
            } catch (e) {
                errorMsg = '服务器错误：' + xhr.status;
            }
            if (errorCallback) errorCallback(errorMsg);
            else showError(errorMsg);
        }
    };
    
    if (data && method !== 'GET') {
        options.data = JSON.stringify(data);
    } else if (data && method === 'GET') {
        options.data = data;
    }
    
    $.ajax(options);
}

// 显示成功消息
function showSuccess(message) {
    var $toast = $('<div class="godo-status-toast">' + message + '</div>');
    $('body').append($toast);
    setTimeout(function() {
        $toast.addClass('godo-status-toast-show');
    }, 10);
    setTimeout(function() {
        $toast.removeClass('godo-status-toast-show');
        setTimeout(function() {
            $toast.remove();
        }, 300);
    }, 2000);
}

// 显示错误消息
function showError(message) {
    alert('错误：' + message);
}

// 显示加载状态
function showLoading() {
    $('.godo-table tbody').html('<tr><td colspan="9" style="text-align:center;padding:40px;color:#999;"><div>加载中...</div></td></tr>');
}

// 加载分类列表
function loadCategoryList(page, searchParams) {
    showLoading();
    
    // 构建查询参数
    var params = {
        page: page || 1,
        limit: perPage
    };
    
    // 合并搜索参数
    if (searchParams) {
        params = $.extend(params, searchParams);
    }
    
    // 添加状态筛选参数
    var statusFilter = $('#statusFilter').val();
    if (statusFilter !== '') {
        params.status = statusFilter;
    }
    
    // 添加父分类筛选参数
    var parentFilter = $('#parentFilter').val();
    if (parentFilter !== '') {
        params.parent_id = parentFilter;
    }
    
    apiRequest('get-category-list-api', 'GET', params, function(data) {
        renderCategoryTable(data.categories);
        updatePagination(data.pagination);
        currentPage = data.pagination.current_page;
    }, function(error) {
        $('.godo-table tbody').html('<tr><td colspan="9" style="color:#d32f2f;text-align:center;">加载失败：' + error + '</td></tr>');
    });
}

// 渲染分类表格
function renderCategoryTable(categories) {
    var $tbody = $('.godo-table tbody');
    $tbody.empty();
    
    if (categories && categories.length > 0) {
        categories.forEach(function(cat) {
            var statusHtml = cat.status == 1 ? 
                '<span class="godo-status godo-status-on godo-status-toggle" data-id="' + cat.category_id + '" data-status="1" title="点击切换状态">启用</span>' :
                '<span class="godo-status godo-status-off godo-status-toggle" data-id="' + cat.category_id + '" data-status="0" title="点击切换状态">禁用</span>';
            
            var parentName = cat.parent_name || (cat.parent_id == 0 ? '无' : cat.parent_id);
            
            var row = '<tr>' +
                '<td><input type="checkbox" name="category_ids[]" value="' + cat.category_id + '"></td>' +
                '<td>' + escapeHtml(cat.category_name) + '</td>' +
                '<td>' + escapeHtml(cat.category_description) + '</td>' +
                '<td>' + escapeHtml(parentName) + '</td>' +
                '<td>' + cat.sort_order + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '<td>' + cat.pattern_count + '</td>' +
                '<td>' + escapeHtml(cat.created_at_formatted) + '</td>' +
                '<td>' +
                    '<button class="godo-btn godo-btn-xs godo-btn-edit" title="编辑" data-id="' + cat.category_id + '" data-name="' + escapeHtml(cat.category_name) + '" data-desc="' + escapeHtml(cat.category_description) + '" data-parent="' + cat.parent_id + '" data-sort="' + cat.sort_order + '" data-status="' + cat.status + '"><span>编辑</span></button> ' +
                    '<button class="godo-btn godo-btn-xs godo-btn-view" title="分配图案"><span>分配图案</span></button> ' +
                    '<button class="godo-btn godo-btn-xs godo-btn-del" title="删除"><span>删除</span></button>' +
                '</td>' +
            '</tr>';
            $tbody.append(row);
        });
    } else {
        $tbody.html('<tr><td colspan="9" style="color:#aaa;text-align:center;">暂无数据</td></tr>');
    }
}

// HTML转义函数
function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

$(function(){
    // 页面初始化 - 加载分类列表
    loadCategoryList(1);
    
    // 打开新增分类弹窗
    $('#btn-category-add').on('click', function() {
        $('#godoCategoryModalTitle').text('新增分类');
        $('#godoCategoryForm').find('button[type="submit"]').text('保存');
        $('#godoCategoryForm')[0].reset();
        $('.godo-form-error').remove();
        
        // 移除分类ID，表示新增模式
        $('#godoCategoryForm [name="category_id"]').remove();
        
        $('#godoCategoryModal').show();
    });

    // 打开编辑分类弹窗
    $('.godo-btn-edit[title="编辑"]').on('click', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var name = $btn.data('name');
        var desc = $btn.data('desc');
        var parent = $btn.data('parent');
        var sort = $btn.data('sort');
        var status = $btn.data('status');
        
        // 设置弹窗标题和按钮文字
        $('#godoCategoryModalTitle').text('编辑分类');
        $('#godoCategoryForm').find('button[type="submit"]').text('更新');
        
        // 预填充表单数据
        $('#godoCategoryForm [name="category_name"]').val(name);
        $('#godoCategoryForm [name="category_description"]').val(desc);
        $('#godoCategoryForm [name="parent_id"]').val(parent);
        $('#godoCategoryForm [name="sort_order"]').val(sort);
        $('#godoCategoryForm [name="status"]').val(status);
        
        // 添加分类ID到表单，用于区分新增/编辑
        if (!$('#godoCategoryForm [name="category_id"]').length) {
            $('#godoCategoryForm').append('<input type="hidden" name="category_id" value="">');
        }
        $('#godoCategoryForm [name="category_id"]').val(id);
        
        // 清除之前的错误提示
        $('.godo-form-error').remove();
        
        // 显示弹窗
        $('#godoCategoryModal').show();
    });

    // 新增/编辑分类表单前端校验
    $('#godoCategoryForm').on('submit', function(e) {
        e.preventDefault();
        $('.godo-form-error').remove();
        var valid = true;
        
        // 校验分类名称
        var name = $(this).find('[name="category_name"]').val().trim();
        if (!name) {
            valid = false;
            $(this).find('[name="category_name"]').after('<div class="godo-form-error">请输入分类名称</div>');
        } else if (name.length > 100) {
            valid = false;
            $(this).find('[name="category_name"]').after('<div class="godo-form-error">名称不能超过100字符</div>');
        }
        
        // 校验描述长度
        var desc = $(this).find('[name="category_description"]').val();
        if (desc && desc.length > 500) {
            valid = false;
            $(this).find('[name="category_description"]').after('<div class="godo-form-error">描述不能超过500字符</div>');
        }
        
        // 校验排序为数字且范围合法
        var sort = $(this).find('[name="sort_order"]').val();
        if (sort && (!/^\d+$/.test(sort) || sort < 0 || sort > 9999)) {
            valid = false;
            $(this).find('[name="sort_order"]').after('<div class="godo-form-error">排序请输入0~9999的数字</div>');
        }
        
        if (!valid) return false;
        
        // 准备提交数据
        var categoryId = $(this).find('[name="category_id"]').val();
        var formData = {
            category_name: name,
            category_description: desc,
            parent_id: parseInt($(this).find('[name="parent_id"]').val()) || 0,
            sort_order: parseInt(sort) || 0,
            status: parseInt($(this).find('[name="status"]').val()) || 1
        };
        
        // 显示提交状态
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.text('提交中...').prop('disabled', true);
        
        // 根据是否有ID判断是新增还是编辑
        if (categoryId) {
            // 编辑分类
            formData.category_id = parseInt(categoryId);
            apiRequest('update-category-api', 'POST', formData, function(data) {
                showSuccess('分类更新成功');
                $('#godoCategoryModal').hide();
                loadCategoryList(currentPage); // 重新加载当前页
            }, function(error) {
                showError('分类更新失败：' + error);
            });
        } else {
            // 新增分类
            apiRequest('add-category-api', 'POST', formData, function(data) {
                showSuccess('分类添加成功');
                $('#godoCategoryModal').hide();
                loadCategoryList(1); // 跳转到第一页
            }, function(error) {
                showError('分类添加失败：' + error);
            });
        }
        
        // 恢复按钮状态
        setTimeout(function() {
            $submitBtn.text(originalText).prop('disabled', false);
        }, 1000);
        
        return false;
    });
    // 分类管理表格"删除"按钮二次确认弹窗
    var $deleteTargetTr = null;
    var deleteCategoryId = null;
    
    $(document).on('click', '.godo-btn-del[title="删除"]', function() {
        $deleteTargetTr = $(this).closest('tr');
        deleteCategoryId = $(this).closest('tr').find('input[name="category_ids[]"]').val();
        $('#godoDeleteConfirmModal').show();
    });
    
    $('#btn-delete-confirm').on('click', function() {
        if (!deleteCategoryId) return;
        
        // 显示删除状态
        var $confirmBtn = $(this);
        var originalText = $confirmBtn.text();
        $confirmBtn.text('删除中...').prop('disabled', true);
        
        // 调用删除API
        apiRequest('delete-category-api', 'POST', {category_id: parseInt(deleteCategoryId)}, function(data) {
            showSuccess('分类删除成功');
            $('#godoDeleteConfirmModal').hide();
            loadCategoryList(currentPage); // 重新加载当前页
        }, function(error) {
            showError('分类删除失败：' + error);
        });
        
        // 恢复按钮状态
        setTimeout(function() {
            $confirmBtn.text(originalText).prop('disabled', false);
        }, 1000);
        
        $deleteTargetTr = null;
        deleteCategoryId = null;
    });

    // 批量操作功能增强 - 第一部分：批量操作管理和选项切换
    // 打开分类批量操作弹窗
    $('#btn-category-batch').on('click', function() {
        $('#godoCategoryBatchForm')[0].reset();
        $('.godo-form-error').remove();
        $('.godo-batch-options').hide();
        
        // 检查是否有勾选分类
        var selectedCount = $('input[name="category_ids[]"]:checked').length;
        if (selectedCount === 0) {
            alert('请先勾选要操作的分类！');
            return false;
        }
        
        // 更新选中数量
        $('#selectedCount').text(selectedCount);
        
        $('#godoCategoryBatchModal').show();
    });
    
    // 批量操作类型切换
    $('#batchActionSelect').on('change', function() {
        var action = $(this).val();
        
        // 隐藏所有选项
        $('.godo-batch-options').hide();
        
        // 显示对应选项
        switch(action) {
            case 'sort':
                $('#batchSortOptions').show();
                break;
            case 'parent':
                $('#batchParentOptions').show();
                break;
            case 'status':
                $('#batchStatusOptions').show();
                break;
        }
    });

    // 批量操作功能增强 - 第二部分：表单验证和操作预览
    // 分类批量操作表单校验与反馈
    $('#godoCategoryBatchForm').on('submit', function(e) {
        e.preventDefault();
        $('.godo-form-error').remove();
        
        var action = $(this).find('[name="batch_action"]').val();
        if (!action) {
            $(this).find('[name="batch_action"]').after('<div class="godo-form-error">请选择批量操作类型</div>');
            return false;
        }
        
        // 根据操作类型进行特定校验
        var isValid = true;
        switch(action) {
            case 'sort':
                var sortValue = $(this).find('[name="sort_value"]').val();
                if (!sortValue || sortValue < 0 || sortValue > 9999) {
                    $(this).find('[name="sort_value"]').after('<div class="godo-form-error">请输入0~9999的排序值</div>');
                    isValid = false;
                }
                break;
            case 'delete':
                if (!confirm('确定要删除选中的分类吗？此操作不可恢复！')) {
                    return false;
                }
                break;
        }
        
        if (!isValid) return false;
        
        // 显示操作预览
        showBatchOperationPreview(action);
        
        // 执行批量操作
        executeBatchOperation(action);
        
        $('#godoCategoryBatchModal').hide();
        return false;
    });
    
    // 显示批量操作预览
    function showBatchOperationPreview(action) {
        var selectedCount = $('input[name="category_ids[]"]:checked').length;
        var previewText = '';
        
        switch(action) {
            case 'enable':
                previewText = '将启用 ' + selectedCount + ' 个分类';
                break;
            case 'disable':
                previewText = '将禁用 ' + selectedCount + ' 个分类';
                break;
            case 'delete':
                previewText = '将删除 ' + selectedCount + ' 个分类';
                break;
            case 'sort':
                var sortMethod = $('#godoCategoryBatchForm [name="sort_method"]').val();
                var sortValue = $('#godoCategoryBatchForm [name="sort_value"]').val();
                previewText = '将修改 ' + selectedCount + ' 个分类的排序为：' + sortValue;
                break;
            case 'parent':
                var parentCategory = $('#godoCategoryBatchForm [name="parent_category"]').val();
                var parentText = parentCategory ? '指定父分类' : '顶级分类';
                previewText = '将修改 ' + selectedCount + ' 个分类的父分类为：' + parentText;
                break;
            case 'status':
                var statusValue = $('#godoCategoryBatchForm [name="status_value"]').val();
                var statusText = statusValue == '1' ? '启用' : '禁用';
                previewText = '将修改 ' + selectedCount + ' 个分类的状态为：' + statusText;
                break;
        }
        
        alert('批量操作预览：' + previewText);
    }
    
    // 批量操作功能增强 - 第三部分：执行批量操作
    // 执行批量操作
    function executeBatchOperation(action) {
        var selectedIds = [];
        $('input[name="category_ids[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        // 模拟AJAX请求
        console.log('执行批量操作：', action, '选中的分类ID：', selectedIds);
        
        // 根据操作类型执行相应逻辑
        switch(action) {
            case 'enable':
                batchUpdateStatus(selectedIds, 1);
                break;
            case 'disable':
                batchUpdateStatus(selectedIds, 0);
                break;
            case 'delete':
                batchDeleteCategories(selectedIds);
                break;
            case 'sort':
                batchUpdateSort(selectedIds);
                break;
            case 'parent':
                batchUpdateParent(selectedIds);
                break;
            case 'status':
                var statusValue = $('#godoCategoryBatchForm [name="status_value"]').val();
                batchUpdateStatus(selectedIds, parseInt(statusValue));
                break;
        }
    }
    
    // 批量更新状态
    function batchUpdateStatus(ids, status) {
        ids.forEach(function(id) {
            var $row = $('input[name="category_ids[]"][value="' + id + '"]').closest('tr');
            var $statusSpan = $row.find('.godo-status-toggle');
            
            $statusSpan.data('status', status);
            $statusSpan.removeClass('godo-status-on godo-status-off');
            if (status == 1) {
                $statusSpan.addClass('godo-status-on').text('启用');
            } else {
                $statusSpan.addClass('godo-status-off').text('禁用');
            }
        });
        
        alert('批量状态更新完成！');
    }
    
    // 批量删除分类
    function batchDeleteCategories(ids) {
        ids.forEach(function(id) {
            $('input[name="category_ids[]"][value="' + id + '"]').closest('tr').remove();
        });
        
        alert('批量删除完成！');
    }
    
    // 批量更新排序
    function batchUpdateSort(ids) {
        var sortMethod = $('#godoCategoryBatchForm [name="sort_method"]').val();
        var sortValue = parseInt($('#godoCategoryBatchForm [name="sort_value"]').val());
        
        ids.forEach(function(id, index) {
            var $row = $('input[name="category_ids[]"][value="' + id + '"]').closest('tr');
            var newSort;
            
            switch(sortMethod) {
                case 'set':
                    newSort = sortValue;
                    break;
                case 'increase':
                    newSort = sortValue + index;
                    break;
                case 'decrease':
                    newSort = sortValue - index;
                    break;
                default:
                    newSort = sortValue;
            }
            
            $row.find('td:eq(4)').text(newSort);
        });
        
        alert('批量排序更新完成！');
    }
    
    // 批量更新父分类
    function batchUpdateParent(ids) {
        var parentCategory = $('#godoCategoryBatchForm [name="parent_category"]').val();
        var parentText = parentCategory ? '指定父分类' : '无';
        
        ids.forEach(function(id) {
            var $row = $('input[name="category_ids[]"][value="' + id + '"]').closest('tr');
            $row.find('td:eq(3)').text(parentText);
        });
        
        alert('批量父分类更新完成！');
    }

    // 打开分配图案弹窗
    $('.godo-btn-view[title="分配图案"]').on('click', function() {
        $('#godoAssignPatternModal').show();
    });
    // 保存分配图案（后续可扩展实际逻辑）
    $('#btn-assign-save').on('click', function() {
        alert('分配图案操作已提交！');
        $('#godoAssignPatternModal').hide();
    });
    // 表头checkbox全选/反选
    $('.godo-table th input[type="checkbox"]').on('change', function() {
        var checked = $(this).is(':checked');
        $(this).closest('table').find('tbody input[type="checkbox"]').prop('checked', checked);
    });

    // 状态快速切换功能 - 第一部分：点击状态标签触发
    $(document).on('click', '.godo-status-toggle', function() {
        var $span = $(this);
        var categoryId = $span.data('id');
        var currentStatus = $span.data('status');
        
        // 修复状态切换逻辑：确保启用能切换为禁用，禁用能切换为启用
        var newStatus;
        var statusText;
        
        if (currentStatus == 1 || currentStatus == '1') {
            newStatus = 0;
            statusText = '禁用';
        } else {
            newStatus = 1;
            statusText = '启用';
        }
        
        // 显示确认提示
        if (confirm('确定要将该分类状态切换为"' + statusText + '"吗？')) {
            // 显示切换中状态
            $span.text('切换中...').addClass('godo-status-switching');
            
            // 调用更新API
            var updateData = {
                category_id: parseInt(categoryId),
                status: newStatus
            };
            
            apiRequest('update-category-api', 'POST', updateData, function(data) {
                // 更新状态数据
                $span.data('status', newStatus);
                
                // 更新样式和文字
                $span.removeClass('godo-status-on godo-status-off godo-status-switching');
                if (newStatus == 1) {
                    $span.addClass('godo-status-on').text('启用');
                } else {
                    $span.addClass('godo-status-off').text('禁用');
                }
                
                // 显示成功提示
                showStatusChangeSuccess(statusText);
                
                // 更新本地数据
                updateTableRowStatus(categoryId, newStatus);
                
            }, function(error) {
                // 恢复原状态
                $span.removeClass('godo-status-switching');
                showError('状态切换失败：' + error);
            });
        }
    });
    
    // 状态快速切换功能 - 第二部分：成功提示和状态更新
    // 显示状态切换成功提示
    function showStatusChangeSuccess(statusText) {
        var $toast = $('<div class="godo-status-toast">状态已切换为"' + statusText + '"</div>');
        $('body').append($toast);
        
        // 显示动画
        setTimeout(function() {
            $toast.addClass('godo-status-toast-show');
        }, 10);
        
        // 自动隐藏
        setTimeout(function() {
            $toast.removeClass('godo-status-toast-show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 2000);
    }
    
    // 更新表格行状态数据
    function updateTableRowStatus(categoryId, newStatus) {
        // 这里可以更新本地数据，用于搜索和分页功能
        // 实际项目中应该同步到后端数据库
        console.log('分类ID:', categoryId, '状态已更新为:', newStatus);
    }

    // 分类排序功能 - 第一部分：排序模式管理
    var isSortMode = false;
    var originalSortOrder = [];
    
    // 排序管理按钮点击事件
    $('#btn-sort-manage').on('click', function() {
        // 显示开发中提示弹窗
        alert('该功能正在开发中');
        return;
        
        // 原有排序功能代码（暂时注释）
        /*
        if (isSortMode) {
            exitSortMode();
        } else {
            enterSortMode();
        }
        */
    });
    
    // 进入排序模式
    function enterSortMode() {
        isSortMode = true;
        $('#btn-sort-manage').text('退出排序').addClass('godo-btn-primary');
        
        // 保存原始排序
        saveOriginalSortOrder();
        
        // 添加排序提示
        addSortInfo();
        
        // 启用拖拽排序
        enableDragSort();
        
        // 添加排序操作按钮
        addSortActions();
    }
    
    // 退出排序模式
    function exitSortMode() {
        isSortMode = false;
        $('#btn-sort-manage').text('排序管理').removeClass('godo-btn-primary');
        
        // 移除排序提示
        $('.godo-sort-info').remove();
        
        // 禁用拖拽排序
        disableDragSort();
        
        // 移除排序操作按钮
        $('.godo-sort-actions').remove();
        
        // 恢复原始排序
        restoreOriginalSortOrder();
    }
    
    // 分类排序功能 - 第二部分：排序数据管理和提示信息
    // 保存原始排序
    function saveOriginalSortOrder() {
        originalSortOrder = [];
        $('.godo-table tbody tr').each(function(index) {
            var $row = $(this);
            var categoryId = $row.find('input[name="category_ids[]"]').val();
            var sort = $row.find('td:eq(4)').text(); // 排序列
            originalSortOrder.push({
                id: categoryId,
                sort: sort,
                element: $row
            });
        });
    }
    
    // 添加排序提示信息
    function addSortInfo() {
        var $info = $('<div class="godo-sort-info">排序模式：拖拽行可调整顺序，点击"保存排序"应用更改</div>');
        $('.godo-table-wrap').before($info);
    }
    
    // 添加排序操作按钮
    function addSortActions() {
        var $actions = $('<div class="godo-sort-actions">' +
            '<button class="godo-btn godo-btn-primary" id="btn-save-sort">保存排序</button>' +
            '<button class="godo-btn" id="btn-reset-sort">重置排序</button>' +
            '</div>');
        $('.godo-table-wrap').after($actions);
        
        // 保存排序按钮事件
        $('#btn-save-sort').on('click', function() {
            saveSortOrder();
        });
        
        // 重置排序按钮事件
        $('#btn-reset-sort').on('click', function() {
            resetSortOrder();
        });
    }

    // 分类排序功能 - 第三部分：拖拽排序和排序操作
    // 启用拖拽排序
    function enableDragSort() {
        $('.godo-table tbody').sortable({
            handle: 'td:eq(4)', // 排序列作为拖拽手柄
            axis: 'y', // 只允许垂直拖拽
            opacity: 0.8,
            tolerance: 'pointer',
            update: function(event, ui) {
                updateSortNumbers();
            }
        });
        
        // 添加拖拽手柄样式
        $('.godo-table tbody tr').addClass('godo-sort-mode');
    }
    
    // 禁用拖拽排序
    function disableDragSort() {
        $('.godo-table tbody').sortable('destroy');
        $('.godo-table tbody tr').removeClass('godo-sort-mode');
    }
    
    // 更新排序数字
    function updateSortNumbers() {
        $('.godo-table tbody tr').each(function(index) {
            $(this).find('td:eq(4)').text(index + 1);
        });
    }
    
    // 保存排序
    function saveSortOrder() {
        var newSortOrder = [];
        $('.godo-table tbody tr').each(function(index) {
            var $row = $(this);
            var categoryId = $row.find('input[name="category_ids[]"]').val();
            newSortOrder.push({
                id: categoryId,
                newSort: index + 1
            });
        });
        
        // 显示保存中状态
        $('#btn-save-sort').text('保存中...').prop('disabled', true);
        
        // 模拟AJAX保存
        setTimeout(function() {
            alert('排序已保存！');
            $('#btn-save-sort').text('保存排序').prop('disabled', false);
            
            // 退出排序模式
            exitSortMode();
        }, 1000);
    }
    
    // 重置排序
    function resetSortOrder() {
        if (confirm('确定要重置为原始排序吗？')) {
            restoreOriginalSortOrder();
        }
    }
    
    // 恢复原始排序
    function restoreOriginalSortOrder() {
        var $tbody = $('.godo-table tbody');
        $tbody.empty();
        
        originalSortOrder.forEach(function(item) {
            $tbody.append(item.element);
        });
        
        updateSortNumbers();
    }

    // 分页功能 - 第一部分：分页链接事件处理
    $(document).on('click', '.godo-pagination-nav a', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var pageMatch = href.match(/page=(\d+)/);
        if (pageMatch) {
            var page = parseInt(pageMatch[1]);
            loadPageData(page);
        }
    });
    
    // 加载指定页面的数据
    function loadPageData(page) {
        // 显示加载状态
        $('.godo-table tbody').html('<tr><td colspan="9" style="text-align:center;padding:40px;color:#999;"><div>加载中...</div></td></tr>');
        
        // 模拟AJAX请求延迟
        setTimeout(function() {
            // 这里应该是真实的AJAX请求，现在用模拟数据
            var startIndex = (page - 1) * 3; // 每页3条
            var endIndex = startIndex + 3;
            var pageData = originalCategoryList.slice(startIndex, endIndex);
            
            renderCategoryTable(pageData);
            updatePagination(page, Math.ceil(originalCategoryList.length / 3));
            
            // 更新URL参数（不刷新页面）
            var newUrl = window.location.pathname + '?page=' + page;
            window.history.pushState({page: page}, '', newUrl);
        }, 300);
    }

    // 分页功能 - 第二部分：分页更新和HTML生成
    function updatePagination(currentPage, totalPages) {
        var paginationHtml = generatePaginationHtml(currentPage, totalPages);
        $('.godo-pagination').html(paginationHtml);
    }

    // 生成分页HTML（JavaScript版本，用于动态更新）
    function generatePaginationHtml(currentPage, totalPages) {
        if (totalPages <= 1) return '';
        
        var html = '<div class="godo-pagination-nav">';
        
        // 上一页
        if (currentPage > 1) {
            html += '<a href="?page=' + (currentPage - 1) + '" class="godo-page-btn">&laquo; 上一页</a>';
        } else {
            html += '<span class="godo-page-btn godo-page-disabled">&laquo; 上一页</span>';
        }
        
        // 页码
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += '<a href="?page=1" class="godo-page-btn">1</a>';
            if (startPage > 2) {
                html += '<span class="godo-page-ellipsis">...</span>';
            }
        }
        
        for (var i = startPage; i <= endPage; i++) {
            if (i == currentPage) {
                html += '<span class="godo-page-btn godo-page-current">' + i + '</span>';
            } else {
                html += '<a href="?page=' + i + '" class="godo-page-btn">' + i + '</a>';
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += '<span class="godo-page-ellipsis">...</span>';
            }
            html += '<a href="?page=' + totalPages + '" class="godo-page-btn">' + totalPages + '</a>';
        }
        
        // 下一页
        if (currentPage < totalPages) {
            html += '<a href="?page=' + (currentPage + 1) + '" class="godo-page-btn">下一页 &raquo;</a>';
        } else {
            html += '<span class="godo-page-btn godo-page-disabled">下一页 &raquo;</span>';
        }
        
        html += '</div>';
        
        // 分页信息
        html += '<div class="godo-pagination-info">';
        html += '第 ' + currentPage + ' 页，共 ' + totalPages + ' 页，每页显示 3 条记录';
        html += '</div>';
        
        return html;
    }

    // 搜索功能
    $('#godoSearchForm').on('submit', function(e) {
        e.preventDefault();
        var searchTerm = $('#godoSearchInput').val().trim();
        if (!searchTerm) {
            alert('请输入分类名称进行搜索！');
            return false;
        }
        // 使用AJAX请求搜索
        loadCategoryList(1, {keyword: searchTerm});
        return false;
    });

    $('#godoClearSearch').on('click', function() {
        // 清空所有筛选条件
        $('#godoSearchInput').val('');
        $('#statusFilter').val('');
        $('#parentFilter').val('');
        // 重新加载第一页，不带搜索条件
        loadCategoryList(1);
    });

    function updateCategoryTable(searchTerm) {
        var $tableBody = $('.godo-table tbody');
        var $rows = $tableBody.find('tr');
        var filteredRows = [];

        $rows.each(function() {
            var $row = $(this);
            var name = $row.find('td:eq(1)').text().toLowerCase(); // 分类名称在第2列
            var desc = $row.find('td:eq(2)').text().toLowerCase(); // 描述在第3列

            if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                filteredRows.push($row);
            }
        });

        $tableBody.empty();
        if (filteredRows.length > 0) {
            filteredRows.forEach(function(row) {
                $tableBody.append(row);
            });
        } else {
            $tableBody.append('<tr><td colspan="9" style="color:#aaa;text-align:center;">未找到相关分类</td></tr>');
        }
    }
});
</script>
