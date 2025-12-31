<?php

/**
 * 商品定制组件（独立版本）
 * 
 * 本组件完全独立，不继承任何父类，使用 PDO 直接查询数据库
 * 专注于商品定制页面的数据获取和展示
 * 
 * 【核心功能】
 * - 获取商品定制视图数据（getCustomGoodsView）
 * - 渲染颜色选项HTML（renderColorOptionsHtml）
 * 
 * 【数据库表】
 * - es_goods：商品基础信息
 * - custom_goods_extension：定制扩展信息
 * - custom_goods_colors：颜色列表
 * 
 * 【设计理念】
 * - 使用 CustomPDO 统一数据库访问
 * - 避免父类依赖导致的500错误
 * - 完整的异常处理和日志记录
 * 
 * @package Custom\Component
 * @author Custom Development Team
 * @version 2.0 (独立版本)
 */

namespace Custom\Component;

use Custom\Component\Database\CustomPDO;
use Custom\Component\CustomExtraOption;
use Custom\Component\CustomExtraImage;
use Framework\Debug\Exception\AlertBackException;
use Exception;

/**
 * 商品定制组件
 */
class CustomGoods
{
    /**
     * PDO连接实例
     * @var CustomPDO
     */
    private $pdo;

    /**
     * 构造函数
     * 
     * 初始化 PDO 连接，不依赖任何父类
     */
    public function __construct()
    {
        try {
            $this->pdo = new CustomPDO();
            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], CustomGoods 组件初始化成功', []);
        } catch (\Throwable $e) {
            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [ERROR] PDO 初始化失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new AlertBackException('数据库连接初始化失败');
        }
    }

    /**
     * 获取商品定制视图数据
     *
     * 基础信息通过官方 \Component\Goods\Goods 组件读取 es_goods；
     * 仍通过 PDO 查询 custom_goods_extension 与 custom_goods_colors。
     * 
     * @param int $goodsNo 商品编号
     * @return array|false 定制商品视图数据或false
     */
    public function getCustomGoodsView($goodsNo)
    {
        try {
            if (empty($goodsNo)) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [ERROR] goodsNo 为空', []);
            return false;
            }

            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 使用官方 Goods 组件读取基础信息', [
                'goodsNo' => $goodsNo
            ]);

            // ✅ 1. 通过官方 Goods 组件读取基础信息
            $goodsComponent = \App::load('\\Component\\Goods\\Goods');
            $goodsData = $goodsComponent->getGoodsInfo($goodsNo);

            if (!$goodsData) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [ERROR] 官方组件未找到商品', [
                'goodsNo' => $goodsNo
            ]);
            return false;
            }

            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 官方组件返回成功', [
                'goodsNm' => $goodsData['goodsNm'] ?? ''
            ]);

            // ✅ 2. 查询 custom_goods_extension
            $extensionSql = "SELECT 
                                is_customizable,
                                custom_width,
                                custom_height,
                                default_color,
                                default_size
                            FROM custom_goods_extension
                            WHERE goods_id = ?";
            
            $extensionData = $this->pdo->fetch($extensionSql, [$goodsNo]);
            
            if ($extensionData) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], custom_goods_extension 查询成功', [
                    'is_customizable' => $extensionData['is_customizable'],
                    'custom_width' => $extensionData['custom_width'],
                    'custom_height' => $extensionData['custom_height']
                ]);
            } else {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], custom_goods_extension 无数据，使用默认值', []);
            }

            // ✅ 3. 查询 custom_goods_colors
            $colorsSql = "SELECT 
                            color_id,
                            color_name,
                            color_code,
                            color_hex,
                            svg_image_path,
                            stock_status,
                            sort_order
                          FROM custom_goods_colors
                          WHERE goods_id = ? AND status = 1
                          ORDER BY sort_order ASC, color_id ASC";
            
            $colorsData = $this->pdo->fetchAll($colorsSql, [$goodsNo]);

            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], custom_goods_colors 查询成功', [
                'color_count' => count($colorsData ?: [])
            ]);
            
            // ✅ 3.2 查询 custom_goods_sizes（多尺寸）- 出错时不影响整页
            $sizesData = [];
            $sizesDisplay = [];
            try {
                $sizesSql = "SELECT custom_size_id, goods_id, size_name, size_width, size_height, size_unit, status, created_at, updated_at
                             FROM custom_goods_sizes
                             WHERE goods_id = ? AND status = 1
                             ORDER BY custom_size_id ASC";
                $sizesData = $this->pdo->fetchAll($sizesSql, [$goodsNo]) ?: [];

                foreach ($sizesData as $row) {
                    $w = rtrim(rtrim((string)$row['size_width'], '0'), '.');
                    $h = rtrim(rtrim((string)$row['size_height'], '0'), '.');
                    $unit = $row['size_unit'] ?: 'mm';
                    $sizesDisplay[] = sprintf('%s : %s x %s %s', $row['size_name'], $w, $h, $unit);
                }

                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], custom_goods_sizes 查询成功', [
                    'size_count' => count($sizesData),
                ]);
            } catch (\Throwable $e) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [WARN] 获取自定义尺寸失败，继续渲染页面', [
                    'goodsNo' => $goodsNo,
                    'error'   => $e->getMessage(),
                ]);
                $sizesData = [];
                $sizesDisplay = [];
            }

            // ✅ 3.3 查询模板 viewBox + 커스텀 영역（如有）- 出错时不影响整页
            $templateViewBoxWidth  = 0;
            $templateViewBoxHeight = 0;
            $clipRegions           = [];
            try {
                $tplSql = "SELECT id, viewbox_width, viewbox_height
                             FROM custom_template
                            WHERE goods_no = ? AND use_yn = 'y'
                            ORDER BY id DESC
                            LIMIT 1";
                $tpl    = $this->pdo->fetch($tplSql, [$goodsNo]);
                if ($tpl) {
                    $templateViewBoxWidth  = (float)($tpl['viewbox_width'] ?? 0);
                    $templateViewBoxHeight = (float)($tpl['viewbox_height'] ?? 0);

                    $regionSql = "SELECT type, x, y, w, h, d, sort AS sort_order
                                    FROM custom_template_clip_region
                                   WHERE template_id = ?
                                   ORDER BY sort ASC, sno ASC";
                    $clipRegions = $this->pdo->fetchAll($regionSql, [(int)$tpl['id']]) ?: [];
                }
            } catch (\Throwable $e) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [WARN] 获取模板/定制区域失败，继续渲染页面', [
                    'goodsNo' => $goodsNo,
                    'error'   => $e->getMessage(),
                ]);
                $templateViewBoxWidth  = 0;
                $templateViewBoxHeight = 0;
                $clipRegions           = [];
            }

            // ✅ 3.1 查询 custom_goods_extra_options 及其颜色（如有）
            $extraOptions = [];
            try {
                $extraOptionComponent = new CustomExtraOption();
                $extraResult = $extraOptionComponent->getGoodsExtraOptions($goodsNo);

                if (!empty($extraResult['success']) && !empty($extraResult['data'])) {
                    $extraOptions = $extraResult['data'];

                    // 为 color_palette 类型加载颜色列表
                    foreach ($extraOptions as &$option) {
                        if (($option['input_type'] ?? '') === 'color_palette') {
                            $colorResult = $extraOptionComponent->getOptionColors($option['option_id']);
                            if (!empty($colorResult['success']) && !empty($colorResult['data'])) {
                                $option['colors'] = $colorResult['data'];
                            } else {
                                $option['colors'] = [];
                            }
                        }
                    }
                    unset($option);
                }

                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], custom_goods_extra_options 查询成功', [
                    'extra_option_count' => count($extraOptions),
                ]);
            } catch (\Throwable $e) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [WARN] 获取附加选项失败，继续渲染页面', [
                    'goodsNo' => $goodsNo,
                    'error' => $e->getMessage(),
                ]);
                $extraOptions = [];
            }

            // ✅ 3.2 查询附加商品图片 custom_goods_extra_images（如有）- 出错不影响整页
            $extraImages = [];
            try {
                $extraImageComponent = new CustomExtraImage();
                $imageResult = $extraImageComponent->getGoodsExtraImages($goodsNo);

                if (!empty($imageResult['success']) && !empty($imageResult['data'])) {
                    $extraImages = $imageResult['data'];
                }

                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], custom_goods_extra_images 查询成功', [
                    'image_count' => count($extraImages),
                ]);
            } catch (\Throwable $e) {
                \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [WARN] 获取附加商品图片失败，继续渲染页面', [
                    'goodsNo' => $goodsNo,
                    'error'   => $e->getMessage(),
                ]);
                $extraImages = [];
            }

            // ✅ 4. 获取官方选项数据（用于定制商品也能选择官方选项）
            $officialOptionData = $this->getOfficialOptionData($goodsNo);

            // ✅ 5. 合并返回数据
            // ✅ 优先使用第一个颜色的SVG图片，否则使用es_goods的图片
            $defaultColorId = null;
            $goodsImageUrl = $this->buildGoodsImageUrl($goodsData, $colorsData, $defaultColorId);

            $customGoodsView = [
                'goodsNo' => $goodsData['goodsNo'],
                'goodsNm' => $goodsData['goodsNm'],
                'goodsPrice' => $goodsData['goodsPrice'],
                'goodsImage' => $goodsImageUrl,
                'goodsDescription' => $goodsData['goodsDescription'] ?? '',
                'shortDescription' => $goodsData['shortDescription'] ?? '',
                'is_customizable' => (int)($extensionData['is_customizable'] ?? 0),
                // 单一宽高不再直接用于前台展示，仅供兼容；多尺寸用 custom_goods_sizes
                'custom_width' => floatval($extensionData['custom_width'] ?? 0),
                'custom_height' => floatval($extensionData['custom_height'] ?? 0),
                'default_color' => $extensionData['default_color'] ?? '',
                'default_size' => $extensionData['default_size'] ?? '',
                // 多尺寸显示：优先使用 custom_goods_sizes；如无记录则回退到旧的单一尺寸
                'custom_size_display' => !empty($sizesDisplay)
                    ? implode(' / ', $sizesDisplay)
                    : $this->formatCustomSize(
                    $extensionData['custom_width'] ?? null, 
                    $extensionData['custom_height'] ?? null
                ),
                'sizes' => $sizesData,
                'colors' => $colorsData ?: [],
                'color_count' => count($colorsData ?: []),
                'colors_json' => json_encode($colorsData ?: [], JSON_UNESCAPING_UNICODE),
                'default_color_id' => $defaultColorId,

                // 模板 viewBox + 裁剪区域配置（供前端 pageConfig 使用）
                'template_viewbox_width'  => $templateViewBoxWidth,
                'template_viewbox_height' => $templateViewBoxHeight,
                'clip_regions_json'       => json_encode($clipRegions ?: [], JSON_UNESCAPED_UNICODE),

                'extra_options' => $extraOptions,
                'extra_options_json' => json_encode($extraOptions ?: [], JSON_UNESCAPING_UNICODE),
                'extra_images' => $extraImages,
                'extra_images_json' => json_encode($extraImages ?: [], JSON_UNESCAPING_UNICODE),
                'goods_price_display' => number_format($goodsData['goodsPrice']) . '원',

                // ✅ 官方选项数据（用于定制商品选择官方选项并结算）
                'optionFl'            => $officialOptionData['optionFl'] ?? 'n',
                'optionDisplayFl'     => $officialOptionData['optionDisplayFl'] ?? 's',
                'optionName'          => $officialOptionData['optionName'] ?? [],
                'optionEachCntFl'     => $officialOptionData['optionEachCntFl'] ?? 'one',
                'option'              => $officialOptionData['option'] ?? [],
                'optionDivision'      => $officialOptionData['optionDivision'] ?? [],
                'optionDivisionStock' => $officialOptionData['optionDivisionStock'] ?? [],
                'optionTextFl'        => $officialOptionData['optionTextFl'] ?? 'n',
                'optionText'          => $officialOptionData['optionText'] ?? [],
                'optionIcon'          => $officialOptionData['optionIcon'] ?? [],
                'optionImagePreviewFl'=> $officialOptionData['optionImagePreviewFl'] ?? 'n',
                'orderPossible'       => $officialOptionData['orderPossible'] ?? 'y',
                'stockFl'             => $officialOptionData['stockFl'] ?? 'n',
            ];

            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 数据组合完成', [
                'goodsNo' => $goodsNo,
                'is_customizable' => $customGoodsView['is_customizable'],
                'color_count' => $customGoodsView['color_count']
            ]);

            return $customGoodsView;

        } catch (\Throwable $e) {
            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [ERROR] 获取商品定制数据异常', [
                'goodsNo' => $goodsNo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 构建商品图片URL
     * ✅ 优先使用颜色管理的SVG图片
     * 
     * @param array $goodsData 商品数据
     * @param array $colorsData 颜色数据数组
     * @return string 图片URL
     */
    private function buildGoodsImageUrl($goodsData, $colorsData = [], &$defaultColorId = null)
    {
        if (!empty($colorsData)) {
            foreach ($colorsData as $colorRow) {
                $isAvailable = !isset($colorRow['stock_status']) || (int)$colorRow['stock_status'] === 1;
                if ($isAvailable && !empty($colorRow['svg_image_path'])) {
                    $defaultColorId = $colorRow['color_id'] ?? null;
                    $svgPath = $colorRow['svg_image_path'];
            
                    \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 使用可用颜色SVG图片', [
                        'svg_path' => $svgPath,
                        'color_name' => $colorRow['color_name'] ?? ''
                    ]);
            
                    return $svgPath;
                }
            }
        }
        
        if (empty($goodsData['imagePath']) || empty($goodsData['goodsImage'])) {
            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 使用默认无图片占位符', []);
            return '/data/skin/front/mplshop/images/common/no_image.gif';
        }
        
        $imageUrl = '/data/goods/' . $goodsData['imagePath'] . '/' . $goodsData['goodsImage'];
        
        \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 使用es_goods图片', [
            'image_url' => $imageUrl
        ]);
        
        return $imageUrl;
    }

    /**
     * 格式化自定义尺寸显示
     * 
     * @param float|null $width 宽度
     * @param float|null $height 高度
     * @return string 格式化后的尺寸字符串（如：10.5 x 15.0 cm）
     */
    private function formatCustomSize($width, $height)
    {
        if (empty($width) || empty($height)) {
            return '';
        }
        
        // 格式化为：宽 x 高 cm
        return number_format($width, 1) . ' x ' . number_format($height, 1) . ' cm';
    }

    /**
     * 获取官方选项数据
     * 
     * 调用官方 Goods 组件的 getGoodsView 方法获取选项信息
     * 
     * @param int $goodsNo 商品编号
     * @return array 选项数据
     */
    private function getOfficialOptionData($goodsNo)
    {
        try {
            $goodsComponent = \App::load('\\Component\\Goods\\Goods');
            $goodsView = $goodsComponent->getGoodsView($goodsNo);

            if (empty($goodsView)) {
                return [
                    'optionFl' => 'n',
                    'optionDisplayFl' => 's',
                    'optionName' => '',
                    'optionEachCntFl' => 'one',
                    'option' => [],
                    'orderPossible' => 'y',
                    'stockFl' => 'n',
                ];
            }

            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], 获取官方选项数据成功', [
                'goodsNo' => $goodsNo,
                'optionFl' => $goodsView['optionFl'] ?? 'n',
                'optionCount' => count($goodsView['option'] ?? []),
            ]);

            return [
                'optionFl'            => $goodsView['optionFl'] ?? 'n',
                'optionDisplayFl'     => $goodsView['optionDisplayFl'] ?? 's',
                'optionName'          => $goodsView['optionName'] ?? [],
                'optionEachCntFl'     => $goodsView['optionEachCntFl'] ?? 'one',
                'option'              => $goodsView['option'] ?? [],
                'optionDivision'      => $goodsView['optionDivision'] ?? [],
                'optionDivisionStock' => $goodsView['optionDivisionStock'] ?? [],
                'optionTextFl'        => $goodsView['optionTextFl'] ?? 'n',
                'optionText'          => $goodsView['optionText'] ?? [],
                'optionIcon'          => $goodsView['optionIcon'] ?? [],
                'optionImagePreviewFl'=> $goodsView['optionImagePreviewFl'] ?? 'n',
                'orderPossible'       => $goodsView['orderPossible'] ?? 'y',
                'stockFl'             => $goodsView['stockFl'] ?? 'n',
            ];
        } catch (\Throwable $e) {
            \Logger::channel('userLog')->debug(__METHOD__ . '[' . __LINE__ . '], [WARN] 获取官方选项数据失败', [
                'goodsNo' => $goodsNo,
                'error' => $e->getMessage(),
            ]);

            return [
                'optionFl'            => 'n',
                'optionDisplayFl'     => 's',
                'optionName'          => [],
                'optionEachCntFl'     => 'one',
                'option'              => [],
                'optionDivision'      => [],
                'optionDivisionStock' => [],
                'optionTextFl'        => 'n',
                'optionText'          => [],
                'optionIcon'          => [],
                'optionImagePreviewFl'=> 'n',
                'orderPossible'       => 'y',
                'stockFl'             => 'n',
            ];
        }
    }

    /**
     * 渲染颜色选项HTML（供控制器使用）
     * 
     * 根据 custom_goods_colors 表的数据生成颜色选项的HTML
     * 
     * @param array $colors 颜色数组
     * @return string HTML字符串
     */
    public function renderColorOptionsHtml($colors)
    {
        if (empty($colors)) {
            return '<div class="no-colors">사용 가능한 색상이 없습니다</div>';
        }
        
        $html = '';
        foreach ($colors as $index => $color) {
            $isActive = $index === 0 ? 'active' : '';
            $colorHex = $color['color_hex'] ?? '#CCCCCC';
            $colorName = htmlspecialchars($color['color_name']);
            $colorCode = htmlspecialchars($color['color_code']);
            $svgPath = htmlspecialchars($color['svg_image_path'] ?? '');
            
            $html .= sprintf(
                '<div class="color-item %s" data-color="%s" data-svg-path="%s" style="background-color: %s;" title="%s"></div>',
                $isActive,
                $colorCode,
                $svgPath,
                $colorHex,
                $colorName
            );
        }
        
        return $html;
    }
} 
