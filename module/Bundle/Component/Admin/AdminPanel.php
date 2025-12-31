<?php

namespace Bundle\Component\Admin;

/**
 * 고도몰 어드민 페이지 패널 데이터 class
 *
 * @package Bundle\Component\Admin
 */
class AdminPanel
{
    // panel data
    public $panel = [];

    /**
     * @param string $panelKey
     * @param string $panelCode
     * @param mixed $panelData
     * @return void
     */
    public function setPanel(string $panelKey, string $panelCode, $panelData)
    {
        $this->panel[$panelKey][] = [
            'panelCode' => $panelCode,
            'panelData' => $panelData
        ];

        $lastIndex = count($this->panel[$panelKey]) - 1;

        // $panelKey 값에 따라 'gdSharePath'를 조건부로 추가
        if (in_array($panelKey, ['popupCos', 'banner', 'board'])) {
            $this->panel[$panelKey][$lastIndex]['gdSharePath'] = PATH_ADMIN_GD_SHARE;
        }
    }

    /**
     * @return array
     */
    public function getPanel(): array
    {
        return $this->panel;
    }
}
