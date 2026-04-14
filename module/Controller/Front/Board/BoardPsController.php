<?php

/**
 * Board board_ps.php 최소 오버라이드
 *
 * - mode=write/modify/reply: 공식 BoardAct::saveData() + 동일 js 리다이렉트 유지
 * - bdId=request1 이고 mode=write 저장 직후 견적 알림 메일 발송 (실패해도 저장/이동은 유지)
 *
 * 관리자 설정 체크리스트 (게시판 request1):
 * - 상품 선택(글쓰기 시 상품 연동) 사용
 * - 첨부파일(bdUploadFl) — 폼에 첨부 행 표시
 * - 자동등록방지(bdSpamBoardFl 비트) — captcha 행
 * - 휴대폰 항목(bdMobileFl) — 연락처(writerMobile) 저장
 * - 이메일 항목(bdEmailFl) — writerEmail + 도메인 선택
 * - 에디터 사용(bdEditorFl) — SmartEditor 및 JS 병합(UPDATE_CONTENTS_FIELD)과 일치
 * - 비밀글 설정이 템플릿 분기(bdSecretFl)와 맞는지 확인
 * - 말머리/링크/평가 등 부가 항목은 해당 플래그에 맞춰 켜기
 *
 * 공식 Bundle BoardPsController 의 write/modify/reply 분기가 변경되면 이 파일의
 * handleWriteModifyReply() 를 수동으로 맞춰야 한다.
 */

namespace Controller\Front\Board;

use Component\Board\BoardAct;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertOnlyException;
use Request;

class BoardPsController extends \Bundle\Controller\Front\Board\BoardPsController
{
    public function index()
    {
        $req = Request::post()->toArray();
        $mode = gd_isset($req['mode']);
        if ($mode === 'modify' || $mode === 'write' || $mode === 'reply') {
            $this->handleWriteModifyReply($req);

            return;
        }

        return parent::index();
    }

    /**
     * @param array $req
     * @return void
     */
    private function handleWriteModifyReply(array $req)
    {
        $req['isMobile'] = false;
        try {
            $boardAct = new BoardAct($req);
            if (method_exists($boardAct, 'setHttpStorage') === true) {
                $boardAct->setHttpStorage(false);
            }
            $addScrpt = '';
            $msgs[] = $boardAct->saveData();

            if (($req['bdId'] ?? '') === 'request1' && ($req['mode'] ?? '') === 'write') {
                try {
                    $attachmentMeta = $this->parseFrontAttachments($req);
                    $mailCtl = \App::load('\\Controller\\Front\\Custom\\CustomGoodsMailController');
                    if ($mailCtl && method_exists($mailCtl, 'sendEstimateInquiryNotification')) {
                        $mailCtl->sendEstimateInquiryNotification($req, $attachmentMeta);
                    }
                } catch (\Throwable $e) {
                    error_log('[BoardPsController] sendEstimateInquiryNotification: ' . $e->getMessage());
                }
            }

            if ($msgs) {
                foreach ($msgs as $msg) {
                    if (!$msg) {
                        continue;
                    }
                    $addScrpt .= 'alert("' . $msg . '");';
                }
            }
            if (gd_isset($req['gboard']) == 'y') {
                if ($req['windowType'] == 'popup') {
                    if (\Request::isSecure()) {
                        $this->js($addScrpt . 'location.href="../goods/goods_ps.php?mode=openerReload&goodsNo=' . $req['goodsNo'] . '";');
                    } else {
                        $this->js($addScrpt . 'alert("' . __('저장되었습니다.') . '");location.href="../goods/goods_view.php?goodsNo=' . $req['goodsNo'] . '";opener.location.reload();self.close();');
                    }
                } else {
                    $this->js($addScrpt . 'location.href="../goods/goods_ps.php?mode=layerReload";');
                }
            } else {
                $this->js($addScrpt . 'location.href="../board/list.php?' . $req['returnUrl'] . '";');
            }
            exit;
        } catch (\Exception $e) {
            if (gd_isset($req['gboard']) == 'y' && $req['windowType'] != 'popup') {
                throw new AlertOnlyException($e->getMessage());
            }
            throw new AlertBackException($e->getMessage());
        }
    }

    /**
     * POST 에서 프론트가 base64 로 인코딩해 보낸 첨부 JSON 파싱.
     * JS 의 FileReader 로 읽은 [{name, type, size, dataUrl}] 배열을 복원한다.
     *
     * @param array $req
     * @return array ['frontAttachments' => [...]]
     */
    private function parseFrontAttachments(array $req)
    {
        $json = trim((string)($req['request1_attachments_json'] ?? ''));
        if ($json === '' || $json === '[]') {
            return ['frontAttachments' => []];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            error_log('[BoardPsController] parseFrontAttachments: invalid JSON, len=' . strlen($json));
            return ['frontAttachments' => []];
        }
        return ['frontAttachments' => $decoded];
    }
}
