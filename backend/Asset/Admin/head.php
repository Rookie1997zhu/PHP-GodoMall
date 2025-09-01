<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <title><?php echo $_mallName_?> :: 쇼핑몰 관리자 - 고도몰</title>
    <meta name="robots" content="noindex">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <meta name="viewport" content="minimal-ui, width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta http-equiv="cleartype" content="on">
    <?php if($gSecure && $gReferrer) { ?>
        <meta name="referrer" content="origin">
    <?php } ?>

    <!-- Latest compiled and minified CSS -->
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/bootstrap.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/bootstrap-datetimepicker.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/bootstrap-datetimepicker-standalone.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/bootstrap-dialog.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery-ui/jquery-ui.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/style.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/non-responsive.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/flags.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/jquery.countdownTimer.css')?>" rel="stylesheet"/>
    <?php
    $headerStyle = gd_isset($headerStyle);
    if (is_array($headerStyle)) {
        foreach ($headerStyle as $v) { ?>
            <link type = "text/css" href = "<?=gd_set_browser_cache($v); ?>" rel = "stylesheet" />
            <?php
        }
    }
    if (isset($headCss) === true) { ?>
        <style type="text/css">
            <?=$headCss;?>
        </style>
        <?php
    } ?>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/gd5-style.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_SKIN . 'css/admin-custom.css')?>" rel="stylesheet"/>
    
    <!-- 商品定制图案管理 CSS -->
    <style type="text/css">
        /* 图案管理容器 */
        .custom-pattern-container {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        /* 图案筛选区域 */
        .pattern-filter-section {
            margin-bottom: 25px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .pattern-filter-section .form-group {
            margin-bottom: 0;
        }
        
        /* 图案库区域 */
        .pattern-library-section {
            margin-bottom: 25px;
        }
        
        .pattern-library-section h5 {
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        /* 图案网格 */
        .pattern-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        /* 图案项目 */
        .pattern-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .pattern-item:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.1);
        }
        
        .pattern-item.selected {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        /* 图案预览 */
        .pattern-preview {
            margin-bottom: 10px;
        }
        
        .pattern-preview img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 5px;
        }
        
        /* 图案信息 */
        .pattern-info {
            margin-bottom: 10px;
        }
        
        .pattern-name {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .pattern-category {
            font-size: 12px;
            color: #6c757d;
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        
        /* 图案操作按钮 */
        .pattern-actions {
            margin-top: 10px;
        }
        
        .pattern-actions .btn {
            font-size: 12px;
            padding: 4px 12px;
        }
        
        /* 已选图案区域 */
        .selected-patterns-section {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
        }
        
        .selected-patterns-section h5 {
            color: #495057;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        /* 已选图案列表 */
        .selected-patterns-list {
            margin-bottom: 20px;
        }
        
        .selected-pattern-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        
        .pattern-preview-small {
            margin-right: 15px;
        }
        
        .pattern-preview-small img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 3px;
        }
        
        .selected-pattern-item .pattern-info {
            flex: 1;
            margin-bottom: 0;
        }
        
        .selected-pattern-item .pattern-actions {
            margin-top: 0;
        }
        
        .selected-pattern-item .pattern-actions .btn {
            margin-left: 5px;
            font-size: 11px;
            padding: 2px 6px;
        }
        
        /* 标签页样式 */
        .custom-pattern-tabs {
            margin-bottom: 20px;
        }
        
        .custom-pattern-tabs .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        
        .custom-pattern-tabs .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
            margin-right: 5px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .custom-pattern-tabs .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #495057;
            background-color: #f8f9fa;
        }
        
        .custom-pattern-tabs .nav-tabs .nav-link.active {
            color: #007bff;
            border-bottom-color: #007bff;
            background-color: transparent;
            font-weight: 600;
        }
        
        .custom-pattern-tabs .tab-content {
            padding: 20px 0;
        }
        
        .custom-pattern-tabs .tab-pane {
            display: none;
        }
        
        .custom-pattern-tabs .tab-pane.show {
            display: block;
        }
        
        .custom-pattern-tabs .tab-pane.active {
            display: block;
        }
        
        /* 分类管理样式 */
        .pattern-category-manage {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .pattern-category-manage .page-header {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .pattern-category-manage .page-header h2 {
            color: #495057;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .pattern-category-manage .page-header .description {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }
        
        /* 工具栏样式 */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .toolbar-left .btn {
            margin-right: 10px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
        }
        
        .search-box input {
            margin-right: 10px;
            min-width: 200px;
        }
        
        /* 表格样式 */
        .category-list-container {
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            overflow: hidden;
        }
        
        .category-list-container .table {
            margin-bottom: 0;
        }
        
        .category-list-container .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .category-list-container .table td {
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        /* 拖拽排序样式 */
        .drag-handle {
            cursor: move;
            text-align: center;
            color: #6c757d;
        }
        
        .drag-handle .icon-drag {
            font-size: 16px;
            margin-right: 5px;
        }
        
        .sort-order {
            font-size: 12px;
            color: #6c757d;
        }
        
        /* 分页样式 */
        .pagination-container {
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }
        
        /* 拖拽排序提示 */
        .drag-sort-tip {
            margin-top: 15px;
            padding: 10px;
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            text-align: center;
            color: #0056b3;
        }
        
        .drag-sort-tip p {
            margin: 0;
            font-size: 14px;
        }
        
        .drag-sort-tip .icon-info {
            margin-right: 5px;
        }
        
        /* 操作按钮区域 */
        .selected-patterns-actions {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .selected-patterns-actions .btn {
            margin: 0 5px;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .pattern-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }
            
            .pattern-item {
                padding: 10px;
            }
            
            .pattern-preview img {
                width: 50px;
                height: 50px;
            }
            
            .selected-pattern-item {
                flex-direction: column;
                text-align: center;
            }
            
            .pattern-preview-small {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/underscore/underscore-min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery-ui/jquery-ui.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/bootstrap/bootstrap.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/moment/moment.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/numeral/numeral.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/bootstrap/bootstrap-datetimepicker.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/bootstrap/bootstrap-dialog.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/bootstrap/bootstrap-filestyle.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/copyclipboard/ZeroClipboard.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/copyclipboard/clipboard.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/copyclipboard/clipboard-2.0.0.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery-browser/dist/jquery.browser.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery.serialize-object.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery.countdownTimer.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/validation/jquery.validate.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/validation/localization/messages_ko.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/validation/additional-methods.min.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/datasaver/jquery.DataSaver.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery.number_only.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery-cookie/jquery.cookie.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/common2.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/common.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/schedule.js')?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/admin_panel_api.js')?>"></script>
    <?php
    $headerScript = gd_isset($headerScript);
    if (is_array($headerScript)) {
        foreach ($headerScript as $url) { ?>
            <script type="text/javascript" src="<?=($url)?>"></script>
            <?php
        }
    } ?>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_SKIN . 'script/admin-custom.js')?>"></script>

    <script type="text/javascript">
        <!--
        $(document).ready(function () {
            <?php if (isset($godoSecurityAgreement)) { ?>
            godo.layer.security_agreement.show(<?= $godoSecurityAgreement; ?>);
            <?php } elseif (isset($godoLoginSecurity)) { ?>
            var panel_options = {};
            panel_options = <?= $godoLoginSecurity; ?>;
            panel_options.position = {};
            panel_options.target = $(".login-layout");

            godo.layer.login_security.show(panel_options);
            <?php } ?>

            <?php if (empty($manualData['menuKey']) === false) {?>

            // 메뉴얼 링크
            $(".gd-help-manual").each(function() {
                var linkText = $(this).text().trim().replace(/\s/g, ''); // 타이틀의 문자열의 공백을 제거함
                var manualUrl = '<?php echo sprintf($manualData['manual_url'], $manualData['menuCode'], $manualData['menuKey'], $manualData['menuFile']);?>#' + linkText;
                $(this).append(' <a href="' + manualUrl + '" target="_blank" class="link-help">페이지 도움말</a>');
            });
            <?php }?>

            <?php if (empty($tooltipData) === false) {?>

            // 툴팁 데이터
            var tooltipData = <?php echo $tooltipData;?>;
            var sectionEle = null;

            // 개발자 정보 등록 툴팁
            function appendTooltip(selector, content, width) {
                $(selector).append('<button type="button" class="btn btn-xs js-tooltip" title="' + content + '" data-placement="right" data-width="' + width + '"><span title="" class="icon-tooltip"></span></button>');
            }

            for (const item of tooltipData) {
                const { title, attribute, content, cntWidth } = item;

                switch (title) {
                    case '쇼핑몰개발담당자정보등록':
                        if (attribute === '본인인증') {
                            appendTooltip('#frmDevelopmentSave table tr:nth-child(4) th label', content, cntWidth);
                        } else if (attribute === '[필수]개인정보수집및이용동의') {
                            appendTooltip('.input_group_development #servicePrivacy label', content, cntWidth);
                        }
                        break;
                    case '쇼핑몰개발담당자정보수정':
                        if (attribute === '[필수]개인정보수집및이용동의') {
                            appendTooltip('#frmDevelopmentUpdate .dev_agree_cont #servicePrivacy label', content, cntWidth);
                        }
                        break;
                }
            }

            $('#content .table.table-cols th').each(function(idx){
                if ($(this).closest('table').siblings('.table-title').length) {
                    sectionEle = $(this).closest('table').prevAll('.table-title:first');
                } else if ($(this).closest('table').parent('div').siblings('.table-title').length) {
                    sectionEle = $(this).closest('table').parent('div').prevAll('.table-title:first');
                } else {
                    sectionEle = $(this).closest('table').parent('div').parent('div').prevAll('.table-title:first');
                }
                if (typeof sectionEle[0] !== "undefined") {
                    var sectionTitle = $(sectionEle[0]).html().replace(/\(?<\/?[^*]+>/gi, '').trim().replace(/ /gi, '').replace(/\n/gi, '');
                    var titleName = $(this).text().trim().replace(/ /gi, '').replace(/\n/gi, '');
                    for (var i in tooltipData) {
                        if (tooltipData[i].title == sectionTitle) {
                            if (tooltipData[i].attribute == titleName) {
                                $(this).append('<button type="button" class="btn btn-xs js-tooltip" title="' + tooltipData[i].content + '" data-placement="right" data-width="' + tooltipData[i].cntWidth + '"><span title="" class="icon-tooltip"></span></button>');
                            }
                        }
                    }
                }
            });

            if ($('.js-tooltip').length > 0) {
                // 툴팁 초기화
                $(document).on({
                    'click': function() {
                        if ($(this).attr('aria-describedby')) {
                            $(this).tooltip('destroy');
                        } else {
                            var option = {
                                trigger: 'click',
                                container: '#content',
                                html: true,
                                template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div><button class="tooltip-close">close</button></div>',
                            };
                            $(this).on('shown.bs.tooltip', function () {
                                $(".tooltip.in").css({
                                    width: $(this).data('width'),
                                    maxWidth: "none",
                                });
                            });
                            $(this).tooltip(option).tooltip('show');
                        }
                    }
                }, '.js-tooltip');
                $(document).on('click', '.tooltip.in .tooltip-close', function () {
                    $('.js-tooltip[aria-describedby=' + $(this).parent().attr('id') + ']').trigger('click');
                });
            }

            // 레이어 생성/제거시에 툴팁 제거
            $(document).on('shown.bs.modal hidden.bs.modal', '.modal', function () {
                if ($('.tooltip.in').length) {
                    $('.tooltip.in').tooltip('destroy');
                }
            });
            <?php }?>
        });
        //-->
    </script>

</head>
