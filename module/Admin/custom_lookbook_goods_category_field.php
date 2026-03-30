<?php
/**
 * 게시글 작성 폼: lookbook 전용 상품분류 선택 (custom_*.php)
 *
 * 변수: $customGoodsCategoryList, $customSelectedGoodsCateCdList, $customSelectedSubTitle (ArticleWriteController 주입)
 */
if (!isset($customGoodsCategoryList) || !is_array($customGoodsCategoryList)) {
    $customGoodsCategoryList = [];
}
$customSelectedGoodsCateCdList = $customSelectedGoodsCateCdList ?? [];
$customSelectedGoodsCateCdList = is_array($customSelectedGoodsCateCdList) ? $customSelectedGoodsCateCdList : [];
$customSelectedSubTitle = $customSelectedSubTitle ?? '';
?>
        <tr>
            <th><?= __('Sub Title') ?></th>
            <td>
                <input type="text" name="subTitle" value="<?= gd_htmlspecialchars($customSelectedSubTitle) ?>" class="form-control" maxlength="255"/>
            </td>
        </tr>
        <tr>
            <th class="require"><?= __('상품 분류') ?></th>
            <td>
                <select name="goodsCateCd[]" class="form-control" style="min-width:280px; height: 220px;" multiple="multiple" required="required">
                    <?php foreach ($customGoodsCategoryList as $row) { ?>
                        <option value="<?= gd_htmlspecialchars($row['cateCd']) ?>"<?php if (in_array(($row['cateCd'] ?? ''), $customSelectedGoodsCateCdList, true)) { ?> selected="selected"<?php } ?>><?= gd_htmlspecialchars($row['label'] ?? '') ?></option>
                    <?php } ?>
                </select>
                <div class="notice-info mgb0"><?= __('Ctrl(Windows) / Command(Mac) 키로 다중 선택할 수 있습니다.') ?></div>
            </td>
        </tr>
