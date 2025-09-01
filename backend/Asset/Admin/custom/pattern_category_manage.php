<?php
/**
 * 图案分类管理模板
 * 
 * @package Custom\Template\Admin
 * @author Custom Developer
 * @since 2024-01-01
 */

// 获取当前用户权限
$adminInfo = Session::get('manager');
$isSuperAdmin = isset($adminInfo['adminType']) && $adminInfo['adminType'] == 'super';

// 检查具体权限
$canAddCategory = $isSuperAdmin || gd_policy('custom.pattern_category.add');
$canEditCategory = $isSuperAdmin || gd_policy('custom.pattern_category.edit');
$canDeleteCategory = $isSuperAdmin || gd_policy('custom.pattern_category.delete');

// 获取分类统计数据
$categoryStats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

// 获取当前筛选参数
$currentStatus = $_GET['status'] ?? '';
$currentKeyword = $_GET['keyword'] ?? '';
$currentParentId = $_GET['parent_id'] ?? '';
?>

<!-- 图案分类管理页面 -->
<div class="pattern-category-manage">
    <!-- 页面标题 -->
    <div class="page-header">
        <h2><?=gd_text('图案分类管理')?></h2>
        <p class="description"><?=gd_text('管理图案分类，支持分类的增删改查、排序和层级管理')?></p>
    </div>

    <!-- 操作工具栏 -->
    <div class="toolbar">
        <div class="toolbar-left">
            <?php if ($canAddCategory): ?>
            <button type="button" class="btn btn-primary" id="btnAddCategory">
                <i class="icon-plus"></i> <?=gd_text('添加分类')?>
            </button>
            <?php endif; ?>
            
            <button type="button" class="btn btn-success" id="btnRefreshCategories">
                <i class="icon-refresh"></i> <?=gd_text('刷新')?>
            </button>
        </div>
        
        <div class="toolbar-right">
            <div class="search-box">
                <input type="text" id="categorySearch" 
                       placeholder="<?=gd_text('搜索分类名称...')?>" 
                       class="form-control" 
                       value="<?=gd_escape($currentKeyword)?>">
                <button type="button" id="btnSearchCategory" class="btn btn-outline-secondary">
                    <i class="icon-search"></i>
                </button>
            </div>
            
            <!-- 状态筛选 -->
            <div class="filter-box ml-3">
                <select id="statusFilter" class="form-control form-control-sm">
                    <option value=""><?=gd_text('全部状态')?></option>
                    <option value="1" <?=$currentStatus === '1' ? 'selected' : ''?>><?=gd_text('启用')?></option>
                    <option value="0" <?=$currentStatus === '0' ? 'selected' : ''?>><?=gd_text('禁用')?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-info mb-3">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?=$categoryStats['total']?></div>
                    <div class="stat-label"><?=gd_text('总分类数')?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?=$categoryStats['active']?></div>
                    <div class="stat-label"><?=gd_text('启用分类')?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?=$categoryStats['inactive']?></div>
                    <div class="stat-label"><?=gd_text('禁用分类')?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">0</div>
                    <div class="stat-label"><?=gd_text('今日新增')?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 分类列表 -->
    <div class="category-list-container">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="categoryTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th width="50"><?=gd_text('排序')?></th>
                        <th width="60"><?=gd_text('状态')?></th>
                        <th><?=gd_text('分类名称')?></th>
                        <th><?=gd_text('描述')?></th>
                        <th><?=gd_text('父分类')?></th>
                        <th><?=gd_text('图案数量')?></th>
                        <th><?=gd_text('创建时间')?></th>
                        <th width="120"><?=gd_text('操作')?></th>
                    </tr>
                </thead>
                <tbody id="categoryTableBody">
                    <!-- 分类数据将通过JavaScript动态加载 -->
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <div class="pagination-container" id="categoryPagination">
            <!-- 分页将通过JavaScript动态生成 -->
        </div>
    </div>

    <!-- 分类编辑模态框 -->
    <div class="modal fade" id="categoryEditModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle"><?=gd_text('添加分类')?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?=gd_text('关闭')?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="categoryEditForm">
                        <input type="hidden" id="editCategoryId" name="category_id">
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"><?=gd_text('分类名称')?> <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="editCategoryName" name="category_name" 
                                       placeholder="<?=gd_text('请输入分类名称')?>" required maxlength="100">
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"><?=gd_text('分类描述')?></label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="editCategoryDescription" name="category_description" 
                                          placeholder="<?=gd_text('请输入分类描述')?>" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"><?=gd_text('父分类')?></label>
                            <div class="col-sm-9">
                                <select class="form-control" id="editParentId" name="parent_id">
                                    <option value="0"><?=gd_text('顶级分类')?></option>
                                    <!-- 父分类选项将通过JavaScript动态加载 -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"><?=gd_text('排序顺序')?></label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="editSortOrder" name="sort_order" 
                                       placeholder="<?=gd_text('数字越小排序越靠前')?>" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"><?=gd_text('状态')?></label>
                            <div class="col-sm-9">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="editStatusActive" name="status" value="1" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="editStatusActive"><?=gd_text('启用')?></label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="editStatusInactive" name="status" value="0" class="custom-control-input">
                                    <label class="custom-control-label" for="editStatusInactive"><?=gd_text('禁用')?></label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gd_text('取消')?></button>
                    <button type="button" class="btn btn-primary" id="btnSaveCategory"><?=gd_text('保存')?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?=gd_text('确认删除')?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?=gd_text('关闭')?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?=gd_text('确定要删除分类')?> "<span id="deleteCategoryName"></span>" <?=gd_text('吗？')?></p>
                    <p class="text-warning"><?=gd_text('删除后无法恢复，请谨慎操作！')?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gd_text('取消')?></button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete"><?=gd_text('确认删除')?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- 拖拽排序提示 -->
    <div class="drag-sort-tip">
        <p><i class="icon-info"></i> <?=gd_text('提示：拖拽行可以调整分类排序')?></p>
    </div>
    
    <!-- 批量操作工具栏 -->
    <div class="batch-toolbar" id="batchToolbar" style="display: none;">
        <div class="batch-info">
            <span id="batchCount">0</span> <?=gd_text('个分类已选择')?>
        </div>
        <div class="batch-actions">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnBatchEnable">
                <i class="icon-check"></i> <?=gd_text('批量启用')?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBatchDisable">
                <i class="icon-ban"></i> <?=gd_text('批量禁用')?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnBatchDelete">
                <i class="icon-delete"></i> <?=gd_text('批量删除')?>
            </button>
        </div>
    </div>
</div>

<!-- 分类管理JavaScript -->
<script>
$(document).ready(function() {
    // 初始化分类管理
    initCategoryManagement();
    
    // 绑定事件
    bindCategoryEvents();
    
    // 加载分类列表
    loadCategoryList();
});

/**
 * 初始化分类管理
 */
function initCategoryManagement() {
    // 初始化拖拽排序
    initDragSort();
    
    // 初始化搜索防抖
    initSearchDebounce();
    
    // 初始化批量操作
    initBatchOperations();
    
    // 初始化状态筛选
    initStatusFilter();
    
    // 检查用户权限
    checkUserPermissions();
}

/**
 * 绑定分类管理事件
 */
function bindCategoryEvents() {
    // 添加分类按钮
    $('#btnAddCategory').on('click', function() {
        showCategoryEditModal();
    });
    
    // 刷新按钮
    $('#btnRefreshCategories').on('click', function() {
        loadCategoryList();
    });
    
    // 搜索按钮
    $('#btnSearchCategory').on('click', function() {
        searchCategories();
    });
    
    // 搜索输入框回车
    $('#categorySearch').on('keypress', function(e) {
        if (e.which === 13) {
            searchCategories();
        }
    });
    
    // 保存分类按钮
    $('#btnSaveCategory').on('click', function() {
        saveCategory();
    });
    
    // 确认删除按钮
    $('#btnConfirmDelete').on('click', function() {
        confirmDeleteCategory();
    });
    
    // 模态框关闭事件
    $('#categoryEditModal').on('hidden.bs.modal', function() {
        resetCategoryForm();
    });
}

/**
 * 加载分类列表
 */
function loadCategoryList(params = {}) {
    const searchParams = new URLSearchParams({
        ...params,
        keyword: $('#categorySearch').val() || ''
    });
    
    $.ajax({
        url: '/admin/custom/pattern_category/getCategoryListAPI',
        method: 'GET',
        data: searchParams.toString(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderCategoryTable(response.data.categories);
                updateCategoryPagination(response.data.total);
            } else {
                showErrorMessage(response.message || '加载分类列表失败');
            }
        },
        error: function() {
            showErrorMessage('网络错误，请稍后重试');
        }
    });
}

/**
 * 渲染分类表格
 */
function renderCategoryTable(categories) {
    const tbody = $('#categoryTableBody');
    tbody.empty();
    
    if (!categories || categories.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center text-muted">暂无分类数据</td></tr>');
        return;
    }
    
    categories.forEach(function(category, index) {
        const row = createCategoryTableRow(category, index);
        tbody.append(row);
    });
    
    // 重新初始化拖拽排序
    initDragSort();
}

/**
 * 创建分类表格行
 */
function createCategoryTableRow(category, index) {
    const statusClass = category.status ? 'badge-success' : 'badge-secondary';
    const statusText = category.status ? '<?=gd_text('启用')?>' : '<?=gd_text('禁用')?>';
    
    return `
        <tr data-category-id="${category.category_id}" data-sort-order="${category.sort_order}">
            <td>
                <input type="checkbox" class="category-checkbox form-check-input" 
                       value="${category.category_id}" onchange="updateBatchToolbar()">
            </td>
            <td class="drag-handle">
                <i class="icon-drag"></i>
                <span class="sort-order">${category.sort_order}</span>
            </td>
            <td>
                <span class="badge ${statusClass}">${statusText}</span>
            </td>
            <td>
                <strong>${escapeHtml(category.category_name)}</strong>
                ${category.parent_id > 0 ? `<small class="text-muted">(<?=gd_text('子分类')?>)</small>` : ''}
            </td>
            <td>${escapeHtml(category.category_description || '-')}</td>
            <td>${escapeHtml(category.parent_name || '-')}</td>
            <td>
                <span class="badge badge-info">${category.pattern_count}</span>
            </td>
            <td>${category.created_time_formatted}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <?php if ($canEditCategory): ?>
                    <button type="button" class="btn btn-outline-primary btn-edit-category" 
                            data-category-id="${category.category_id}" title="<?=gd_text('编辑')?>">
                        <i class="icon-edit"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($canDeleteCategory): ?>
                    <button type="button" class="btn btn-outline-danger btn-delete-category" 
                            data-category-id="${category.category_id}" title="<?=gd_text('删除')?>">
                        <i class="icon-delete"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    `;
}

/**
 * 显示分类编辑模态框
 */
function showCategoryEditModal(categoryId = null) {
    const modal = $('#categoryEditModal');
    const title = modal.find('.modal-title');
    const form = $('#categoryEditForm');
    
    if (categoryId) {
        // 编辑模式
        title.text('编辑分类');
        loadCategoryInfo(categoryId);
    } else {
        // 添加模式
        title.text('添加分类');
        resetCategoryForm();
    }
    
    // 加载父分类选项
    loadParentCategoryOptions();
    
    modal.modal('show');
}

/**
 * 加载分类信息
 */
function loadCategoryInfo(categoryId) {
    $.ajax({
        url: '/admin/custom/pattern_category/getCategoryInfoAPI',
        method: 'GET',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                fillCategoryForm(response.data.category);
            } else {
                showErrorMessage(response.message || '加载分类信息失败');
            }
        },
        error: function() {
            showErrorMessage('网络错误，请稍后重试');
        }
    });
}

/**
 * 填充分类表单
 */
function fillCategoryForm(category) {
    $('#editCategoryId').val(category.category_id);
    $('#editCategoryName').val(category.category_name);
    $('#editCategoryDescription').val(category.category_description);
    $('#editParentId').val(category.parent_id);
    $('#editSortOrder').val(category.sort_order);
    
    if (category.status) {
        $('#editStatusActive').prop('checked', true);
    } else {
        $('#editStatusInactive').prop('checked', true);
    }
}

/**
 * 重置分类表单
 */
function resetCategoryForm() {
    $('#categoryEditForm')[0].reset();
    $('#editCategoryId').val('');
    $('#editSortOrder').val('0');
    $('#editStatusActive').prop('checked', true);
}

/**
 * 加载父分类选项
 */
function loadParentCategoryOptions() {
    $.ajax({
        url: '/admin/custom/pattern_category/getCategoryListAPI',
        method: 'GET',
        data: { status: 1 },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderParentCategoryOptions(response.data.categories);
            }
        }
    });
}

/**
 * 渲染父分类选项
 */
function renderParentCategoryOptions(categories) {
    const select = $('#editParentId');
    select.find('option:not(:first)').remove();
    
    categories.forEach(function(category) {
        if (category.category_id != $('#editCategoryId').val()) {
            select.append(`<option value="${category.category_id}">${escapeHtml(category.category_name)}</option>`);
        }
    });
}

/**
 * 保存分类
 */
function saveCategory() {
    const form = $('#categoryEditForm')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const categoryId = $('#editCategoryId').val();
    const url = categoryId ? 
        '/admin/custom/pattern_category/updateCategoryAPI' : 
        '/admin/custom/pattern_category/addCategoryAPI';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessMessage(response.message || '保存成功');
                $('#categoryEditModal').modal('hide');
                loadCategoryList();
            } else {
                showErrorMessage(response.message || '保存失败');
            }
        },
        error: function() {
            showErrorMessage('网络错误，请稍后重试');
        }
    });
}

/**
 * 删除分类
 */
function deleteCategory(categoryId) {
    $.ajax({
        url: '/admin/custom/pattern_category/deleteCategoryAPI',
        method: 'POST',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessMessage(response.message || '删除成功');
                loadCategoryList();
            } else {
                showErrorMessage(response.message || '删除失败');
            }
        },
        error: function() {
            showErrorMessage('网络错误，请稍后重试');
        }
    });
}

/**
 * 显示删除确认模态框
 */
function showDeleteConfirmModal(categoryId, categoryName) {
    $('#deleteCategoryName').text(categoryName);
    $('#deleteConfirmModal').modal('show');
    
    // 绑定确认删除事件
    $('#btnConfirmDelete').off('click').on('click', function() {
        deleteCategory(categoryId);
        $('#deleteConfirmModal').modal('hide');
    });
}

/**
 * 搜索分类
 */
function searchCategories() {
    loadCategoryList();
}

/**
 * 初始化拖拽排序
 */
function initDragSort() {
    $('#categoryTableBody').sortable({
        handle: '.drag-handle',
        axis: 'y',
        tolerance: 'pointer',
        update: function(event, ui) {
            updateCategorySort();
        }
    });
}

/**
 * 更新分类排序
 */
function updateCategorySort() {
    const sortData = {};
    $('#categoryTableBody tr').each(function(index) {
        const categoryId = $(this).data('category-id');
        if (categoryId) {
            sortData[categoryId] = index + 1;
        }
    });
    
    $.ajax({
        url: '/admin/custom/pattern_category/updateCategorySortAPI',
        method: 'POST',
        data: { sort_data: sortData },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessMessage('排序更新成功');
                loadCategoryList();
            } else {
                showErrorMessage(response.message || '排序更新失败');
            }
        },
        error: function() {
            showErrorMessage('网络错误，请稍后重试');
        }
    });
}

/**
 * 初始化搜索防抖
 */
function initSearchDebounce() {
    let searchTimer;
    $('#categorySearch').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            searchCategories();
        }, 500);
    });
}

/**
 * 更新分页
 */
function updateCategoryPagination(total) {
    // 这里可以实现分页功能，暂时简化处理
    if (total > 10) {
        $('#categoryPagination').html(`
            <div class="text-center">
                <small class="text-muted">共 ${total} 条记录</small>
            </div>
        `);
    } else {
        $('#categoryPagination').empty();
    }
}

/**
 * 事件委托绑定
 */
$(document).on('click', '.btn-edit-category', function() {
    const categoryId = $(this).data('category-id');
    showCategoryEditModal(categoryId);
});

$(document).on('click', '.btn-delete-category', function() {
    const categoryId = $(this).data('category-id');
    const categoryName = $(this).closest('tr').find('td:nth-child(3) strong').text();
    showDeleteConfirmModal(categoryId, categoryName);
});

/**
 * 工具函数
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccessMessage(message) {
    // 使用Godomall的消息提示系统
    if (typeof showMessage === 'function') {
        showMessage(message, 'success');
    } else {
        alert(message);
    }
}

function showErrorMessage(message) {
    // 使用Godomall的消息提示系统
    if (typeof showMessage === 'function') {
        showMessage(message, 'error');
    } else {
        alert(message);
    }
}

/**
 * 检查用户权限
 */
function checkUserPermissions() {
    // 检查添加权限
    const canAdd = <?php echo $canAddCategory ? 'true' : 'false'; ?>;
    if (!canAdd) {
        $('#btnAddCategory').hide();
    }
    
    // 检查编辑权限
    const canEdit = <?php echo $canEditCategory ? 'true' : 'false'; ?>;
    if (!canEdit) {
        $('.btn-edit-category').hide();
    }
    
    // 检查删除权限
    const canDelete = <?php echo $canDeleteCategory ? 'true' : 'false'; ?>;
    if (!canDelete) {
        $('.btn-delete-category').hide();
    }
}

/**
 * 初始化批量操作
 */
function initBatchOperations() {
    // 全选/取消全选
    $('#selectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.category-checkbox').prop('checked', isChecked);
        updateBatchToolbar();
    });
    
    // 批量操作按钮事件
    $('#btnBatchEnable').on('click', function() {
        batchUpdateStatus(1);
    });
    
    $('#btnBatchDisable').on('click', function() {
        batchUpdateStatus(0);
    });
    
    $('#btnBatchDelete').on('click', function() {
        batchDelete();
    });
}

/**
 * 初始化状态筛选
 */
function initStatusFilter() {
    $('#statusFilter').on('change', function() {
        const status = $(this).val();
        loadCategoryList({ status: status });
    });
}

/**
 * 更新批量操作工具栏
 */
function updateBatchToolbar() {
    const checkedCount = $('.category-checkbox:checked').length;
    
    if (checkedCount > 0) {
        $('#batchCount').text(checkedCount);
        $('#batchToolbar').show();
    } else {
        $('#batchToolbar').hide();
    }
}

/**
 * 批量更新状态
 */
function batchUpdateStatus(status) {
    const selectedIds = getSelectedCategoryIds();
    
    if (selectedIds.length === 0) {
        showErrorMessage('<?=gd_text('请选择要操作的分类')?>');
        return;
    }
    
    const statusText = status ? '<?=gd_text('启用')?>' : '<?=gd_text('禁用')?>';
    
    if (confirm(`<?=gd_text('确定要批量')?>${statusText} ${selectedIds.length} <?=gd_text('个分类吗？')?>`)) {
        $.ajax({
            url: '/admin/custom/pattern_category/batchUpdateStatusAPI',
            method: 'POST',
            data: { 
                category_ids: selectedIds,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message || `<?=gd_text('批量')?>${statusText}<?=gd_text('成功')?>`);
                    loadCategoryList();
                    updateBatchToolbar();
                } else {
                    showErrorMessage(response.message || `<?=gd_text('批量')?>${statusText}<?=gd_text('失败')?>`);
                }
            },
            error: function() {
                showErrorMessage('<?=gd_text('网络错误，请稍后重试')?>');
            }
        });
    }
}

/**
 * 批量删除
 */
function batchDelete() {
    const selectedIds = getSelectedCategoryIds();
    
    if (selectedIds.length === 0) {
        showErrorMessage('<?=gd_text('请选择要删除的分类')?>');
        return;
    }
    
    if (confirm(`<?=gd_text('确定要删除')?> ${selectedIds.length} <?=gd_text('个分类吗？删除后无法恢复！')?>`)) {
        $.ajax({
            url: '/admin/custom/pattern_category/batchDeleteAPI',
            method: 'POST',
            data: { category_ids: selectedIds },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message || '<?=gd_text('批量删除成功')?>');
                    loadCategoryList();
                    updateBatchToolbar();
                } else {
                    showErrorMessage(response.message || '<?=gd_text('批量删除失败')?>');
                }
            },
            error: function() {
                showErrorMessage('<?=gd_text('网络错误，请稍后重试')?>');
            }
        });
    }
}

/**
 * 获取选中的分类ID
 */
function getSelectedCategoryIds() {
    const ids = [];
    $('.category-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}
</script>
