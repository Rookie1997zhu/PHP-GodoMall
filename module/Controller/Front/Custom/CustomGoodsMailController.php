<?php
/**
 * 외부몰 주문 메일 발송 전용 컨트롤러
 *
 * URL: /custom/custom_goods_mail.php?mode=sendExternalOrder
 * Method: POST (application/json)
 *
 * 기능:
 * - 외부몰 주문 팝업에서 입력한 정보 + 커스텀 요약 테이블 + 첨부 이미지(JPG/PNG/SVG)를
 *   메일로 전송한다.
 *
 * 주의:
 * - 기존 GoodsCustomController.php 를 수정하지 않기 위해 별도 컨트롤러로 분리.
 */

namespace Controller\Front\Custom;

use Exception;
use Request;

class CustomGoodsMailController extends \Controller\Front\Controller
{
    /**
     * rate limit 저장 경로 (data/tmp/rate_limit)
     */
    private function getRateLimitDir()
    {
        $root = null;
        if (defined('_ROOT_PATH_')) {
            $root = _ROOT_PATH_;
        } else {
            // fallback: /module/Controller/Front/Custom -> (4 levels up) project root
            $root = dirname(dirname(dirname(dirname(__DIR__))));
        }

        $candidates = [
            rtrim((string)$root, '/\\') . '/data/tmp/rate_limit',
        ];

        // sys temp fallback (권한 문제 대비)
        try {
            if (function_exists('sys_get_temp_dir')) {
                $candidates[] = rtrim((string)sys_get_temp_dir(), '/\\') . '/godomall_rate_limit';
            }
        } catch (\Throwable $e) {}

        foreach ($candidates as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        // 최종 fallback: 첫번째 경로 반환 (이 경우 fopen 실패 시 차단 처리됨)
        return $candidates[0];
    }

    /**
     * goodsNo + email 기준: cooldown(냉각) 방식 제한
     * - 60초 구간 내 maxPerMinute 초과 시 60초 냉각(block) 진입
     * - 냉각 중에는 항상 차단(429) + retryAfter(남은 초) 반환
     *
     * @return array [allowed(bool), count(int), retryAfter(int)]
     */
    private function rateLimitGoodsEmailPerMinute($goodsNo, $email, $maxPerMinute = 3)
    {
        $goodsNo = (int)$goodsNo;
        $email = strtolower(trim((string)$email));
        $maxPerMinute = (int)$maxPerMinute;
        if ($maxPerMinute <= 0) $maxPerMinute = 1;

        // 필수 키가 없으면 제한하지 않음
        if ($goodsNo <= 0 || $email === '') {
            return [true, 0, 0];
        }

        $cooldownSeconds = 60;
        $now = time();

        $safeEmail = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $email);
        $dir = $this->getRateLimitDir();
        // cooldown 방식: 분 버킷 파일이 아니라 1개 상태 파일에 누적
        $file = rtrim($dir, '/\\') . '/cgmail_goods_email_' . $goodsNo . '_' . $safeEmail . '.json';

        $fp = @fopen($file, 'c+');
        if (!$fp) {
            // ✅ 파일을 못 열면 차단(그래야 "항상 제한이 동작"함)
            return [false, 999, $cooldownSeconds];
        }

        @flock($fp, LOCK_EX);

        // 상태 로드
        $state = [
            'start' => $now,        // window start
            'count' => 0,           // request count in window
            'block_until' => 0,     // unix ts
        ];
        $raw = stream_get_contents($fp);
        if (is_string($raw) && trim($raw) !== '') {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) {
                $state = array_merge($state, $tmp);
            }
        }

        $blockUntil = (int)($state['block_until'] ?? 0);
        if ($blockUntil > 0 && $now < $blockUntil) {
            $retryAfter = $blockUntil - $now;
            if ($retryAfter <= 0) $retryAfter = 1;

            // 상태 저장(그대로)
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($state, JSON_UNESCAPED_UNICODE));
            fflush($fp);
            @flock($fp, LOCK_UN);
            fclose($fp);

            return [false, (int)($state['count'] ?? 0), (int)$retryAfter];
        }

        // window 만료 시 리셋
        $start = (int)($state['start'] ?? $now);
        if (($now - $start) >= $cooldownSeconds) {
            $state['start'] = $now;
            $state['count'] = 0;
            $state['block_until'] = 0;
        }

        $state['count'] = (int)($state['count'] ?? 0) + 1;

        if ((int)$state['count'] > $maxPerMinute) {
            // ✅ 초과 시 냉각 진입(60초)
            $state['block_until'] = $now + $cooldownSeconds;
            $retryAfter = $cooldownSeconds; // 첫 차단 시 60초로 시작
        } else {
            $retryAfter = 0;
        }

        // 상태 저장
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($state, JSON_UNESCAPED_UNICODE));
        fflush($fp);
        @flock($fp, LOCK_UN);
        fclose($fp);

        if (!empty($state['block_until']) && $now < (int)$state['block_until']) {
            return [false, (int)$state['count'], (int)$retryAfter];
        }

        return [true, (int)$state['count'], 0];
    }

    /**
     * RFC2047 (UTF-8) header encoding
     */
    private function encodeHeaderUtf8($str)
    {
        $str = (string)$str;
        if ($str === '' || preg_match('/^[\x20-\x7E]*$/', $str)) {
            return $str;
        }
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }

    /**
     * error_log 로 남기는 간단한 구조화 로그
     */
    private function logMailEvent($requestId, $stage, array $data = [])
    {
        try {
            $payload = [
                'request_id' => $requestId,
                'stage' => $stage,
                'ts' => date('c'),
                'data' => $data,
            ];
            error_log('[CustomGoodsMail] ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * debug=1 이면 trace 를 JSON 응답으로 회신
     */
    private function isTraceDebugEnabled()
    {
        // ✅ trace 회신 비활성화 (정보 노출 방지)
        // - 서버 error_log 기록은 유지되며, 프론트(JSON)로는 trace를 내려주지 않는다.
        return false;
    }

    /**
     * 서버 변수 조회 (Request->server() -> $_SERVER -> getenv 순서)
     */
    private function getServerVar($key, $default = null)
    {
        try {
            $req = Request::getInstance();
            if ($req) {
                $v = $req->server()->get($key);
                if ($v !== null && $v !== '') {
                    return $v;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }

        return $default;
    }

    /**
     * DevTools 에서 확인 가능한 디버그 정보 구성
     */
    private function buildDebugContext(array $extra = [])
    {
        $reqMethod = null;
        $srvMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        try {
            $req = Request::getInstance();
            if ($req) {
                $reqMethod = $req->server()->get('REQUEST_METHOD');
            }
        } catch (\Throwable $e) {}

        $effectiveMethod = $this->getServerVar('REQUEST_METHOD', null);
        $effectiveContentType = $this->getServerVar('CONTENT_TYPE', null) ?: $this->getServerVar('HTTP_CONTENT_TYPE', null);
        $effectiveContentLength = $this->getServerVar('CONTENT_LENGTH', null);
        $effectiveQueryString = $this->getServerVar('QUERY_STRING', null);
        $effectiveRequestUri = $this->getServerVar('REQUEST_URI', null);
        $effectiveOrigin = $this->getServerVar('HTTP_ORIGIN', null);

        $ctx = [
            // 기존 키(호환): observed_method 는 "효과적인" 메소드로 채운다.
            'observed_method' => $effectiveMethod,
            'content_type' => $effectiveContentType,
            'content_length' => $effectiveContentLength !== null ? (int)$effectiveContentLength : null,
            'query_string' => $effectiveQueryString,
            'request_uri' => $effectiveRequestUri,
            // 추가: 두 소스 비교용
            'observed_method_request' => $reqMethod,
            'observed_method_server' => $srvMethod,
            'content_type_request' => null,
            'content_type_server' => $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? null),
            'origin_request' => null,
            'origin_server' => $_SERVER['HTTP_ORIGIN'] ?? null,
            // getenv 값 확인용(효과적인 값이 어디서 왔는지 진단)
            'env_request_method' => getenv('REQUEST_METHOD') !== false ? getenv('REQUEST_METHOD') : null,
            'ini_variables_order' => function_exists('ini_get') ? ini_get('variables_order') : null,
            'observed_origin' => $effectiveOrigin,
        ];
        foreach ($extra as $k => $v) {
            $ctx[$k] = $v;
        }
        return $ctx;
    }

    /**
     * 엔트리
     */
    public function index()
    {
        $mode = Request::get()->get('mode') ?: Request::post()->get('mode');
        if ($mode === 'sendExternalOrder') {
            $this->sendExternalOrder();
            return;
        }

        $this->json([
            'success' => false,
            'message' => 'Invalid mode',
        ]);
    }

    /**
     * 외부몰 주문 메일 발송 (첨부 포함)
     */
    public function sendExternalOrder()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            // 요청별 추적 ID (메일 미수신 시 서버 로그 추적용)
            $requestId = null;
            try {
                $requestId = 'cgm_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
            } catch (\Throwable $e) {
                $requestId = 'cgm_' . date('YmdHis') . '_fallback';
            }

            $debugTrace = $this->isTraceDebugEnabled();
            $trace = [];
            $traceAdd = function ($stage, array $data = []) use (&$trace, $requestId) {
                $row = [
                    'request_id' => $requestId,
                    'stage' => $stage,
                    'ts' => date('c'),
                    'data' => $data,
                ];
                $trace[] = $row;
                // 서버 error_log 에도 같이 남김
                error_log('[CustomGoodsMailTrace] ' . json_encode($row, JSON_UNESCAPED_UNICODE));
            };

            // CORS preflight 대응 (환경에 따라 OPTIONS가 먼저 들어옴)
            $method = (string)$this->getServerVar('REQUEST_METHOD', '');
            $origin = (string)$this->getServerVar('HTTP_ORIGIN', '*');
            if (strtoupper((string)$method) === 'OPTIONS') {
                header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
                header('Access-Control-Allow-Credentials: true');
                echo json_encode([
                    'success' => true,
                    'message' => 'OK',
                    'debug' => $this->buildDebugContext(['note' => 'OPTIONS preflight']),
                    'request_id' => $requestId,
                    'trace' => $debugTrace ? $trace : null,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // 일부 환경에서 Request::get()->getMethod() 가 없거나 예외를 던져 500(HTML)로 떨어질 수 있어,
            // PHP 표준 서버 변수를 사용한다.
            if (strtoupper((string)$method) !== 'POST') {
                // DevTools 에서 바로 확인할 수 있도록 debug 포함
                $traceAdd('reject_non_post', [
                    'observed_method_effective' => $method,
                ]);
                $resp = [
                    'success' => false,
                    'message' => 'POST 요청만 지원합니다.',
                    'debug' => $this->buildDebugContext([
                        'observed_method_effective' => $method,
                    ]),
                    'request_id' => $requestId,
                ];
                if ($debugTrace) {
                    $resp['trace'] = $trace;
                }
                $this->json($resp);
                return;
            }

            $rawInput = file_get_contents('php://input');
            if (empty($rawInput)) {
                $traceAdd('reject_empty_body', []);
                $resp = [
                    'success' => false,
                    'message' => '요청 데이터가 없습니다.',
                    'debug' => $this->buildDebugContext(['has_body' => false]),
                    'request_id' => $requestId,
                ];
                if ($debugTrace) {
                    $resp['trace'] = $trace;
                }
                $this->json($resp);
                return;
            }

            $data = json_decode($rawInput, true);
            if (!is_array($data)) {
                $traceAdd('reject_invalid_json', [
                    'body_head' => mb_substr((string)$rawInput, 0, 200, 'UTF-8'),
                ]);
                $resp = [
                    'success' => false,
                    'message' => '잘못된 JSON 형식입니다.',
                    'debug' => $this->buildDebugContext([
                        'has_body' => true,
                        'body_head' => mb_substr((string)$rawInput, 0, 200, 'UTF-8'),
                    ]),
                    'request_id' => $requestId,
                ];
                if ($debugTrace) {
                    $resp['trace'] = $trace;
                }
                $this->json($resp);
                return;
            }

            $toEmail = 'hello@hellomisterlee.com';

            $goodsNo = isset($data['goods_no']) ? (int)$data['goods_no'] : 0;
            $goodsName = trim((string)($data['goods_name'] ?? ''));
            $platform = strtolower(trim((string)($data['platform'] ?? '')));
            if ($platform !== 'naver' && $platform !== 'cafe24') {
                $platform = '';
            }
            $platformAccountId = trim((string)($data['platform_account_id'] ?? ''));
            $requestNote = trim((string)($data['request_note'] ?? ''));
            $receiver = trim((string)($data['receiver'] ?? ''));
            // 프론트의 "연락 정보" (이메일일 수도, 아이디/메신저일 수도 있음)
            $email = trim((string)($data['email'] ?? ''));
            $contact = trim((string)($data['contact'] ?? ''));
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
            $summaryPrice = trim((string)($data['summary_price'] ?? ''));
            $customInfoHtml = (string)($data['custom_info_html'] ?? '');
            $images = (isset($data['images']) && is_array($data['images'])) ? $data['images'] : [];

            // 요청 수신 로그
            $this->logMailEvent($requestId, 'request_received', [
                'method' => $method,
                'content_type' => $this->getServerVar('CONTENT_TYPE', null) ?: $this->getServerVar('HTTP_CONTENT_TYPE', null),
                'content_length' => $this->getServerVar('CONTENT_LENGTH', null),
                'to' => $toEmail,
                'platform' => $platform,
                'platform_account_id' => $platformAccountId,
                'request_note_len' => ($requestNote !== '' ? mb_strlen($requestNote, 'UTF-8') : 0),
                'goods_no' => $goodsNo,
                'goods_name' => $goodsName,
                'receiver' => $receiver,
                'contact_info' => $email,
                'images_count_input' => is_array($images) ? count($images) : 0,
            ]);
            $traceAdd('request_received', [
                'method' => $method,
                'to' => $toEmail,
                'platform' => $platform,
                'platform_account_id' => $platformAccountId,
                'request_note_len' => ($requestNote !== '' ? mb_strlen($requestNote, 'UTF-8') : 0),
                'goods_no' => $goodsNo,
                'goods_name' => $goodsName,
                'receiver' => $receiver,
                'contact_info' => $email,
                'images_count_input' => is_array($images) ? count($images) : 0,
            ]);

            if ($goodsNo <= 0) {
                throw new Exception('상품번호가 올바르지 않습니다.');
            }
            if ($receiver === '') {
                throw new Exception('수령인을 입력해주세요.');
            }
            if ($email === '') {
                throw new Exception('연락 정보를 입력해주세요.');
            }
            // 고객 이메일로도 발송해야 하므로 이메일 형식 필수
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('이메일 형식으로 입력해주세요.');
            }
            if ($contact === '') {
                throw new Exception('연락처를 입력해주세요.');
            }
            if ($quantity <= 0) {
                $quantity = 1;
            }

            // ✅ 빈번한 요청 방지: 동일 goodsNo + 이메일 기준 1분당 3회 제한
            list($rateAllowed, $rateCount, $retryAfter) = $this->rateLimitGoodsEmailPerMinute($goodsNo, $email, 3);
            if (!$rateAllowed) {
                $this->logMailEvent($requestId, 'rate_limited', [
                    'goods_no' => $goodsNo,
                    'email' => $email,
                    'count' => $rateCount,
                    'limit' => 3,
                    'retry_after' => $retryAfter,
                ]);
                $traceAdd('rate_limited', [
                    'goods_no' => $goodsNo,
                    'email' => $email,
                    'count' => $rateCount,
                    'limit' => 3,
                    'retry_after' => $retryAfter,
                ]);

                http_response_code(429);
                header('Retry-After: ' . (int)$retryAfter);

                $resp = [
                    'success' => false,
                    'message' => '요청이 너무 많습니다.',
                    'retry_after' => (int)$retryAfter,
                    'request_id' => $requestId,
                ];
                if ($debugTrace) {
                    $resp['trace'] = $trace;
                }
                $this->json($resp);
                return;
            }

            // 기본 정보(발신자)
            $basicInfo = gd_policy('basic.info');
            $mallNm = $basicInfo['mallNm'] ?? 'GodoMall';

            // ✅ 고정 From (요구사항: basic.info.email 사용 안 함)
            $fromEmail = 'hello@hellomisterlee.com';

            // 메일 제목
            $platformLabel = $platform ? strtoupper($platform) : 'EXTERNAL';
            $subject = sprintf('[%s 주문요청] %s / %s', $platformLabel, ($goodsName !== '' ? $goodsName : ('goodsNo=' . $goodsNo)), $receiver);

            // 커스텀 테이블 HTML 최소 허용 태그만 남김 (XSS 방지)
            $customInfoHtml = $this->sanitizeCustomInfoHtml($customInfoHtml);

            $bodyHtml = $this->buildBodyHtml([
                'mallNm' => $mallNm,
                'platform' => $platform,
                'goodsNo' => $goodsNo,
                'goodsName' => $goodsName,
                'platformAccountId' => $platformAccountId,
                'requestNote' => $requestNote,
                'receiver' => $receiver,
                'email' => $email,
                'contact' => $contact,
                'quantity' => $quantity,
                'summaryPrice' => $summaryPrice,
                'customInfoHtml' => $customInfoHtml,
            ]);

            // 첨부 이미지 필수 (최소 1장)
            if (empty($images) || !is_array($images) || count($images) < 1) {
                throw new Exception('첨부 이미지를 1장 이상 업로드해주세요.');
            }

            // 첨부 이미지 처리(최대 5장, 총 20MB 제한)
            $attachments = $this->normalizeAttachments($images);
            if (empty($attachments) || count($attachments) < 1) {
                throw new Exception('첨부 이미지를 1장 이상 업로드해주세요.');
            }

            // 첨부 요약 로그
            $attTotal = 0;
            $attTypes = [];
            foreach ($attachments as $att) {
                $attTotal += strlen((string)($att['content'] ?? ''));
                $t = (string)($att['contentType'] ?? '');
                if ($t !== '') $attTypes[$t] = true;
            }
            $this->logMailEvent($requestId, 'attachments_ready', [
                'count' => count($attachments),
                'total_bytes' => $attTotal,
                'types' => array_values(array_keys($attTypes)),
            ]);
            $traceAdd('attachments_ready', [
                'count' => count($attachments),
                'total_bytes' => $attTotal,
                'types' => array_values(array_keys($attTypes)),
            ]);

            // 연락 정보는 본문에 포함하고, 실제 이메일 형식일 때만 Reply-To 설정
            $replyTo = $email;

            // 1) 판매자에게 전송
            $sendInfoSeller = $this->sendMailWithAttachments([
                'toEmail' => $toEmail,
                'toName' => 'Order Receiver',
                'fromEmail' => $fromEmail,
                'fromName' => $mallNm,
                'replyTo' => $replyTo,
                'subject' => $subject,
                'html' => $bodyHtml,
                'attachments' => $attachments,
                'requestId' => $requestId,
            ]);

            $this->logMailEvent($requestId, 'send_result_seller', $sendInfoSeller);
            $traceAdd('send_result_seller', $sendInfoSeller);

            if (empty($sendInfoSeller['success'])) {
                throw new Exception('메일 발송에 실패했습니다. (서버 발송 환경을 확인해주세요)');
            }

            // 2) 고객에게도 동일 내용 전송 (연락 정보 이메일)
            $subjectCustomer = $subject . ' (고객용)';
            $sendInfoCustomer = $this->sendMailWithAttachments([
                'toEmail' => $email,
                'toName' => 'Customer',
                'fromEmail' => $fromEmail,
                'fromName' => $mallNm,
                'replyTo' => null,
                'subject' => $subjectCustomer,
                'html' => $bodyHtml,
                'attachments' => $attachments,
                'requestId' => $requestId,
            ]);

            $this->logMailEvent($requestId, 'send_result_customer', $sendInfoCustomer);
            $traceAdd('send_result_customer', $sendInfoCustomer);

            if (empty($sendInfoCustomer['success'])) {
                throw new Exception('고객 이메일 발송에 실패했습니다. (서버 발송 환경을 확인해주세요)');
            }

            $resp = [
                'success' => true,
                'message' => '주문 요청이 전송되었습니다.',
                'request_id' => $requestId,
            ];
            if ($debugTrace) {
                $resp['trace'] = $trace;
            }
            $this->json($resp);

            return;
        } catch (\Throwable $e) {
            // Exception 뿐 아니라 Error 까지 잡아서 항상 JSON으로 내려준다 (프론트 JSON.parse 실패 방지)
            error_log('[CustomGoodsMailController] sendExternalOrder error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            $resp = [
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => $this->buildDebugContext([
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                ]),
                'request_id' => isset($requestId) ? $requestId : null,
            ];
            try {
                if (isset($debugTrace) && $debugTrace && isset($trace) && is_array($trace)) {
                    $trace[] = [
                        'request_id' => isset($requestId) ? $requestId : null,
                        'stage' => 'exception',
                        'ts' => date('c'),
                        'data' => [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ],
                    ];
                    $resp['trace'] = $trace;
                }
            } catch (\Throwable $ignore) {}
            $this->json($resp);

            return;
        }
    }

    private function buildBodyHtml(array $p)
    {
        $esc = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };

        $goodsNameLine = ($p['goodsName'] !== '') ? $esc($p['goodsName']) : '-';
        $contactLine = ($p['contact'] !== '') ? $esc($p['contact']) : '-';
        $summaryPriceLine = ($p['summaryPrice'] !== '') ? $esc($p['summaryPrice']) : '-';
        $platformAccountIdLine = trim((string)($p['platformAccountId'] ?? ''));
        $requestNoteLine = trim((string)($p['requestNote'] ?? ''));

        $html = [];
        $html[] = '<html><head><meta charset="utf-8"></head><body>';
        $html[] = '<h2>외부몰 주문 요청</h2>';
        if (!empty($p['platform'])) {
            $html[] = '<p>플랫폼: ' . $esc(strtoupper((string)$p['platform'])) . '</p>';
        }
        $html[] = '<p>쇼핑몰: ' . $esc($p['mallNm']) . '</p>';
        $html[] = '<hr>';

        $html[] = '<h3>수신자 정보</h3>';
        $html[] = '<table style="border-collapse:collapse;width:100%;font-size:13px;">';
        $html[] = '<tbody>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;width:140px;">수령인</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['receiver']) . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">연락 정보</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['email']) . '</td></tr>';
        if ($platformAccountIdLine !== '') {
            $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">외부몰 계정 ID</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($platformAccountIdLine) . '</td></tr>';
        }
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">연락처</th><td style="padding:8px;border:1px solid #e9ecef;">' . $contactLine . '</td></tr>';
        $html[] = '</tbody></table>';

        if ($requestNoteLine !== '') {
            $html[] = '<h3 style="margin-top:18px;">요청사항</h3>';
            $html[] = '<div style="border:1px solid #e9ecef;border-radius:6px;padding:10px;font-size:13px;line-height:1.5;">'
                . nl2br($esc($requestNoteLine))
                . '</div>';
        }

        // ✅ 상품 정보 + 네이버 결제 금액 안내(커스텀 요약) 합쳐서 1개 표로 표시
        $html[] = '<h3 style="margin-top:18px;">상품/결제 정보</h3>';
        $html[] = '<table style="border-collapse:collapse;width:100%;font-size:13px;">';
        $html[] = '<tbody>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;width:140px;">상품번호</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['goodsNo']) . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">상품명</th><td style="padding:8px;border:1px solid #e9ecef;">' . $goodsNameLine . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">수량</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['quantity']) . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">금액</th><td style="padding:8px;border:1px solid #e9ecef;">' . $summaryPriceLine . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;vertical-align:top;">네이버 결제 금액 안내</th><td style="padding:8px;border:1px solid #e9ecef;">' . (($p['customInfoHtml'] !== '') ? $p['customInfoHtml'] : '<p>-</p>') . '</td></tr>';
        $html[] = '</tbody></table>';

        $html[] = '<p style="margin-top:18px;color:#666;font-size:12px;">본 메일은 외부몰 주문 모드에서 자동 발송된 주문 요청입니다.</p>';
        $html[] = '</body></html>';

        return implode("\n", $html);
    }

    /**
     * 견적문의(request1) 게시판 저장 후 운영 메일 알림.
     * 실패 시 error_log 만 기록하고 예외를 밖으로 던지지 않는다.
     *
     * @param array $req board_ps.php POST
     * @param array $attachmentMeta 게시글 DB 에서 가져온 첨부 메타 (uploadFileNm, saveFileNm, bdUploadStorage, bdUploadPath)
     * @return void
     */
    public function sendEstimateInquiryNotification(array $req, array $attachmentMeta = [])
    {
        try {
            $basicInfo = gd_policy('basic.info');
            $mallNm = $basicInfo['mallNm'] ?? 'GodoMall';
            $fromEmail = 'hello@hellomisterlee.com';
            $toEmail = 'hello@hellomisterlee.com';

            $writerNm = trim((string)($req['writerNm'] ?? ''));
            $writerEmail = trim((string)($req['writerEmail'] ?? ''));
            $writerMobile = trim((string)($req['writerMobile'] ?? ''));
            $subjectPost = trim((string)($req['subject'] ?? ''));
            $contents = (string)($req['contents'] ?? '');

            $estimateCompany = trim((string)($req['estimate_company'] ?? ''));
            $estimateAddr1 = trim((string)($req['estimate_addr1'] ?? ''));
            $estimateAddr2 = trim((string)($req['estimate_addr2'] ?? ''));
            $estimateDue = trim((string)($req['estimate_due_date'] ?? ''));
            $estimateQty = trim((string)($req['estimate_qty'] ?? ''));

            $goodsNos = [];
            if (!empty($req['goodsNo']) && is_array($req['goodsNo'])) {
                foreach ($req['goodsNo'] as $g) {
                    $g = trim((string)$g);
                    if ($g !== '') {
                        $goodsNos[] = $g;
                    }
                }
            }

            $mailSubject = $subjectPost !== '' ? $subjectPost : '[견적문의] 알림';
            $goodsDisplayLines = $this->resolveEstimateGoodsDisplayLines($goodsNos);
            $bodyHtml = $this->buildEstimateInquiryMailHtml([
                'mallNm' => $mallNm,
                'writerNm' => $writerNm,
                'writerEmail' => $writerEmail,
                'writerMobile' => $writerMobile,
                'subject' => $subjectPost,
                'contents' => $contents,
                'estimateCompany' => $estimateCompany,
                'estimateAddr1' => $estimateAddr1,
                'estimateAddr2' => $estimateAddr2,
                'estimateDue' => $estimateDue,
                'estimateQty' => $estimateQty,
                'goodsDisplayLines' => $goodsDisplayLines,
            ]);

            $replyTo = null;
            if ($writerEmail !== '' && filter_var($writerEmail, FILTER_VALIDATE_EMAIL)) {
                $replyTo = $writerEmail;
            }

            try {
                $ridSuffix = bin2hex(random_bytes(4));
            } catch (\Throwable $ignore) {
                $ridSuffix = substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
            }
            $requestId = 'est_' . date('YmdHis') . '_' . $ridSuffix;

            // 프론트가 FileReader 로 읽어 보낸 base64 첨부 배열을 naver 외부주문과 동일한 방식으로 처리
            $loadedAttachments = [];
            if (!empty($attachmentMeta['frontAttachments']) && is_array($attachmentMeta['frontAttachments'])) {
                try {
                    $loadedAttachments = $this->normalizeAttachments($attachmentMeta['frontAttachments']);
                } catch (\Throwable $attErr) {
                    error_log('[CustomGoodsMailController] normalizeAttachments failed, sending without attachments: ' . $attErr->getMessage());
                    $loadedAttachments = [];
                }
            }

            $sendInfo = $this->sendMailWithAttachments([
                'toEmail' => $toEmail,
                'toName' => 'Estimate Receiver',
                'fromEmail' => $fromEmail,
                'fromName' => $mallNm,
                'replyTo' => $replyTo,
                'subject' => $mailSubject,
                'html' => $bodyHtml,
                'attachments' => $loadedAttachments,
                'requestId' => $requestId,
            ]);
            if (empty($sendInfo['success'])) {
                error_log('[CustomGoodsMailController] sendEstimateInquiryNotification transport failed: ' . json_encode($sendInfo, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            error_log('[CustomGoodsMailController] sendEstimateInquiryNotification: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * POST 의 goodsNo[] 에 대응하는 상품명 목록 (메일 표시용). 조회 실패 시 번호로 대체.
     *
     * @param array $goodsNos
     * @return string[]
     */
    private function resolveEstimateGoodsDisplayLines(array $goodsNos)
    {
        if ($goodsNos === []) {
            return [];
        }

        $uniqueIds = [];
        foreach ($goodsNos as $g) {
            $n = (int)$g;
            if ($n > 0) {
                $uniqueIds[$n] = true;
            }
        }
        $idList = array_keys($uniqueIds);

        $nmByNo = [];
        if ($idList !== [] && defined('DB_GOODS')) {
            try {
                $db = \App::load('DB');
                $placeholders = implode(',', array_fill(0, count($idList), '?'));
                $arrBind = [];
                foreach ($idList as $id) {
                    $db->bind_param_push($arrBind, 'i', $id);
                }
                $sql = 'SELECT goodsNo, goodsNm FROM ' . DB_GOODS . ' WHERE goodsNo IN (' . $placeholders . ')';
                $rows = $db->query_fetch($sql, $arrBind);
                if (!is_array($rows)) {
                    $rows = [];
                } elseif (isset($rows['goodsNo'])) {
                    $rows = [$rows];
                }
                if ($rows !== []) {
                    foreach ($rows as $r) {
                        if (!is_array($r) || !isset($r['goodsNo'])) {
                            continue;
                        }
                        $nmByNo[(int)$r['goodsNo']] = trim((string)($r['goodsNm'] ?? ''));
                    }
                }
            } catch (\Throwable $e) {
                error_log('[CustomGoodsMailController] resolveEstimateGoodsDisplayLines: ' . $e->getMessage());
            }
        }

        $out = [];
        foreach ($goodsNos as $g) {
            $n = (int)$g;
            if ($n <= 0) {
                continue;
            }
            $nm = $nmByNo[$n] ?? '';
            $out[] = $nm !== '' ? $nm : ('(상품번호 ' . $n . ')');
        }

        return $out;
    }

    /**
     * 게시판 첨부 메타로부터 실제 파일 바이너리를 로드한다 (메일 첨부용).
     * 총합 10MB(10485760) 초과 시 이후 파일 스킵, 개별 파일 실패 시 건너뛰고 로그.
     *
     * @param array $meta ['uploadFileNm','saveFileNm','bdUploadStorage','bdUploadPath']
     * @return array sendMailWithAttachments 가 기대하는 형식
     */
    private function loadBoardAttachments(array $meta)
    {
        $maxTotal = 10 * 1024 * 1024; // 10 MB
        $result = [];
        $totalSize = 0;

        // OBS 기본 스토리지: boardSno 기반으로 조회된 첨부 목록 처리
        if (!empty($meta['obsAttachments']) && is_array($meta['obsAttachments'])) {
            foreach ($meta['obsAttachments'] as $i => $row) {
                $imageUrl = trim((string)($row['imageUrl'] ?? ''));
                $saveNm   = trim((string)($row['saveFileNm'] ?? ''));
                $origName = trim((string)($row['uploadFileNm'] ?? ''));

                // imageUrl 은 OBS 내부 경로(/board/bdId/sno/file.jpg)이므로
                // getObjectUrl() 로 CDN URL 로 변환해야 file_get_contents/curl 로 접근 가능
                $fetchUrl = '';
                if (class_exists('\\Framework\\ObjectStorage\\Service\\ImageUploadService')) {
                    if ($imageUrl !== '') {
                        $fetchUrl = (string)\Framework\ObjectStorage\Service\ImageUploadService::getObjectUrl($imageUrl);
                    }
                    if ($fetchUrl === '' && $saveNm !== '') {
                        $fetchUrl = (string)\Framework\ObjectStorage\Service\ImageUploadService::getObjectUrl($saveNm);
                    }
                }
                // getObjectUrl 을 사용할 수 없는 경우 직접 URL 로 시도
                if ($fetchUrl === '') {
                    $fetchUrl = $imageUrl !== '' ? $imageUrl : $saveNm;
                }
                if ($fetchUrl === '') {
                    error_log('[CustomGoodsMailController] loadBoardAttachments OBS: no url idx=' . $i . ' imageUrl=' . $imageUrl . ' save=' . $saveNm);
                    continue;
                }

                try {
                    $content = $this->fetchUrlContent($fetchUrl);
                    if (!is_string($content) || $content === '') {
                        error_log('[CustomGoodsMailController] loadBoardAttachments OBS: empty content idx=' . $i . ' url=' . $fetchUrl);
                        continue;
                    }
                    $fileSize = strlen($content);
                    if ($totalSize + $fileSize > $maxTotal) {
                        error_log('[CustomGoodsMailController] loadBoardAttachments OBS: 10MB limit reached at idx=' . $i);
                        break;
                    }
                    $totalSize += $fileSize;

                    $nameBase = $origName !== '' ? $origName : $saveNm;
                    $ext      = strtolower(pathinfo($nameBase, PATHINFO_EXTENSION));
                    $filename = $origName !== '' ? $origName : ('attachment_' . $i . ($ext !== '' ? '.' . $ext : '.bin'));

                    $result[] = [
                        'content'     => $content,
                        'contentType' => $this->guessContentTypeByExtension($ext),
                        'filename'    => $filename,
                    ];
                } catch (\Throwable $e) {
                    error_log('[CustomGoodsMailController] loadBoardAttachments OBS error idx=' . $i . ': ' . $e->getMessage());
                }
            }
            return $result;
        }

        // 로컬/FTP 스토리지: 게시판 테이블의 saveFileNm 기반 처리
        $uploadNames = explode(defined('STR_DIVISION') ? STR_DIVISION : '|', (string)($meta['uploadFileNm'] ?? ''));
        $saveNames = explode(defined('STR_DIVISION') ? STR_DIVISION : '|', (string)($meta['saveFileNm'] ?? ''));
        $storageName = (string)($meta['bdUploadStorage'] ?? '');
        $uploadPath = (string)($meta['bdUploadPath'] ?? '');

        $count = min(count($uploadNames), count($saveNames));
        if ($count === 0 || ($count === 1 && trim($saveNames[0]) === '')) {
            return [];
        }

        for ($i = 0; $i < $count; $i++) {
            $origName = trim($uploadNames[$i] ?? '');
            $saveName = trim($saveNames[$i] ?? '');
            if ($saveName === '') {
                continue;
            }

            try {
                $content = null;

                if ($storageName === 'obs') {
                    if (class_exists('\\Framework\\ObjectStorage\\Service\\ImageUploadService')) {
                        $obsUrl = (string)\Framework\ObjectStorage\Service\ImageUploadService::getObjectUrl($saveName);
                        if ($obsUrl !== '') {
                            $content = $this->fetchUrlContent($obsUrl);
                        }
                    }
                } else {
                    $storage = \Component\Storage\Storage::disk(
                        \Component\Storage\Storage::PATH_CODE_BOARD,
                        $storageName !== '' ? $storageName : 'local'
                    );
                    $realPath = $storage->getRealPath($uploadPath . $saveName);
                    if ($realPath && @is_file($realPath) && @is_readable($realPath)) {
                        $content = @file_get_contents($realPath);
                    }
                }

                if (!is_string($content) || $content === '') {
                    error_log('[CustomGoodsMailController] loadBoardAttachments: empty/unreadable file idx=' . $i . ' save=' . $saveName);
                    continue;
                }

                $fileSize = strlen($content);
                if ($totalSize + $fileSize > $maxTotal) {
                    error_log('[CustomGoodsMailController] loadBoardAttachments: 10MB limit reached, skipping remaining files from idx=' . $i);
                    break;
                }
                $totalSize += $fileSize;

                $ext = strtolower(pathinfo($origName !== '' ? $origName : $saveName, PATHINFO_EXTENSION));
                $contentType = $this->guessContentTypeByExtension($ext);
                $filename = $origName !== '' ? $origName : ('attachment_' . $i . '.' . ($ext !== '' ? $ext : 'bin'));

                $result[] = [
                    'content' => $content,
                    'contentType' => $contentType,
                    'filename' => $filename,
                ];
            } catch (\Throwable $e) {
                error_log('[CustomGoodsMailController] loadBoardAttachments file error idx=' . $i . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * HTTP URL 로부터 파일 바이너리를 가져온다.
     * file_get_contents 를 먼저 시도하고, allow_url_fopen 이 꺼져 있거나 실패하면 curl 로 재시도한다.
     *
     * @param string $url
     * @return string|null
     */
    private function fetchUrlContent($url)
    {
        if ($url === '') {
            return null;
        }

        // 1차 시도: file_get_contents (allow_url_fopen = On 환경)
        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
            $content = @file_get_contents($url, false, $ctx);
            if (is_string($content) && $content !== '') {
                return $content;
            }
        }

        // 2차 시도: curl
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $content = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);
            if ($curlErr !== '') {
                error_log('[CustomGoodsMailController] fetchUrlContent curl error: ' . $curlErr . ' url=' . $url);
            }
            if (is_string($content) && $content !== '' && $httpCode >= 200 && $httpCode < 300) {
                return $content;
            }
        }

        return null;
    }

    private function guessContentTypeByExtension($ext)
    {
        $map = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'hwp' => 'application/x-hwp',
        ];

        return $map[$ext] ?? 'application/octet-stream';
    }

    private function buildEstimateInquiryMailHtml(array $p)
    {
        $esc = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };

        $contentsSafe = $this->sanitizeEstimateBoardHtml((string)($p['contents'] ?? ''));
        $goodsLines = isset($p['goodsDisplayLines']) && is_array($p['goodsDisplayLines']) ? $p['goodsDisplayLines'] : [];
        $goodsStr = !empty($goodsLines) ? implode(', ', array_map($esc, $goodsLines)) : '-';

        $html = [];
        $html[] = '<html><head><meta charset="utf-8"></head><body>';
        $html[] = '<h2>견적문의 알림</h2>';
        $html[] = '<p>쇼핑몰: ' . $esc($p['mallNm'] ?? '') . '</p>';
        $html[] = '<hr>';
        $html[] = '<table style="border-collapse:collapse;width:100%;font-size:13px;">';
        $html[] = '<tbody>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;width:160px;">작성자</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['writerNm'] ?? '') . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">이메일</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['writerEmail'] ?? '') . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">연락처</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['writerMobile'] ?? '') . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">업체명</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['estimateCompany'] ?? '') . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">납품 주소</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc(trim(($p['estimateAddr1'] ?? '') . ' ' . ($p['estimateAddr2'] ?? ''))) . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">희망 납기일</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['estimateDue'] ?? '') . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">주문 수량</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['estimateQty'] ?? '') . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">상품명</th><td style="padding:8px;border:1px solid #e9ecef;">' . $goodsStr . '</td></tr>';
        $html[] = '<tr><th style="text-align:left;padding:8px;border:1px solid #e9ecef;background:#f8f9fa;">게시글 제목</th><td style="padding:8px;border:1px solid #e9ecef;">' . $esc($p['subject'] ?? '') . '</td></tr>';
        $html[] = '</tbody></table>';
        $html[] = '<h3 style="margin-top:18px;">문의 본문</h3>';
        $html[] = '<div style="border:1px solid #e9ecef;border-radius:6px;padding:12px;font-size:13px;line-height:1.6;">' . ($contentsSafe !== '' ? $contentsSafe : '<p>-</p>') . '</div>';
        $html[] = '<p style="margin-top:18px;color:#666;font-size:12px;">본 메일은 견적문의 게시판(request1) 신규 글 등록 시 자동 발송되었습니다. 첨부파일은 메일에 포함되지 않습니다.</p>';
        $html[] = '</body></html>';

        return implode("\n", $html);
    }

    /**
     * 게시판 에디터 HTML 을 메일 본문용으로 제한적으로 정제
     */
    private function sanitizeEstimateBoardHtml($html)
    {
        $allowed = '<p><br><br/><strong><b><em><i><u><span><div><ul><ol><li><a><img><h1><h2><h3><h4><blockquote><table><tbody><thead><tr><th><td><hr><colgroup><col><font>';
        $clean = strip_tags((string)$html, $allowed);
        $clean = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $clean);
        $clean = preg_replace('/<\s*style[^>]*>.*?<\s*\/\s*style\s*>/is', '', $clean);
        $clean = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/javascript\s*:/i', '', $clean);

        return $clean;
    }

    private function sanitizeCustomInfoHtml($html)
    {
        // 허용 태그: 기본 표 + 간단한 레이아웃(div/span/p) + 줄바꿈
        // (프론트에서 생성한 "네이버 결제 금액 안내" 블록이 div 기반이므로 최소한의 태그를 허용)
        $allowed = '<table><tbody><tr><th><td><br><div><span><p><strong><b>';
        $clean = strip_tags((string)$html, $allowed);

        // script/style 제거(방어적)
        $clean = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $clean);
        $clean = preg_replace('/<\s*style[^>]*>.*?<\s*\/\s*style\s*>/is', '', $clean);

        // 이벤트 핸들러 속성 제거(onclick 등)
        $clean = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean);

        // javascript: 제거 (href/src 등에 들어갈 수 있음)
        $clean = preg_replace('/javascript\s*:/i', '', $clean);

        return $clean ?: '';
    }

    /**
     * 프론트에서 넘어온 images[] 를 첨부 파일 배열로 정규화
     * return: [ ['filename'=>..., 'contentType'=>..., 'content'=>...], ... ]
     */
    private function normalizeAttachments(array $images)
    {
        // ✅ 외부몰 주문: 합성 SVG + 업로드 도안(여러 장)까지 첨부될 수 있어 개수 제한 상향
        $maxCount = 30;
        $maxTotal = 20 * 1024 * 1024; // 20MB
        $total = 0;

        $result = [];
        $i = 1;
        foreach ($images as $img) {
            if (count($result) >= $maxCount) {
                break;
            }
            if (!is_array($img)) {
                continue;
            }

            $name = trim((string)($img['name'] ?? ''));
            $type = trim((string)($img['type'] ?? ''));
            $dataUrl = (string)($img['dataUrl'] ?? '');
            $rawText = (string)($img['rawText'] ?? '');

            // content 추출
            $contentType = '';
            $content = null;
            $filename = '';

            // SVG는 rawText 우선
            if ($type === 'image/svg+xml' || preg_match('/\.svg$/i', $name)) {
                $contentType = 'image/svg+xml';
                $content = ($rawText !== '') ? $rawText : $this->extractDataUrlContent($dataUrl, $contentType);
                if (!is_string($content) || $content === '') {
                    continue;
                }
                $filename = $this->safeFilename($name !== '' ? $name : ('attachment_' . $i . '.svg'));
            } else {
                $content = $this->extractDataUrlContent($dataUrl, $contentType);
                if (!is_string($content) || $content === '') {
                    continue;
                }
                if ($contentType === '') {
                    // fallback
                    $contentType = $type !== '' ? $type : 'application/octet-stream';
                }
                $ext = $this->guessExtensionByContentType($contentType);
                $filename = $this->safeFilename($name !== '' ? $name : ('attachment_' . $i . $ext));
            }

            $size = strlen($content);
            $total += $size;
            if ($total > $maxTotal) {
                throw new Exception('첨부파일 총 용량이 너무 큽니다. (최대 20MB)');
            }

            $result[] = [
                'filename' => $filename,
                'contentType' => $contentType,
                'content' => $content,
            ];
            $i++;
        }

        return $result;
    }

    /**
     * data:* URL 에서 raw content 추출
     */
    private function extractDataUrlContent($dataUrl, &$contentTypeOut)
    {
        $dataUrl = (string)$dataUrl;
        if ($dataUrl === '' || stripos($dataUrl, 'data:') !== 0) {
            return null;
        }

        // data:[<mediatype>][;base64],<data>
        $pos = strpos($dataUrl, ',');
        if ($pos === false) {
            return null;
        }

        $meta = substr($dataUrl, 5, $pos - 5);
        $data = substr($dataUrl, $pos + 1);

        $isBase64 = false;
        if (stripos($meta, ';base64') !== false) {
            $isBase64 = true;
            $meta = str_ireplace(';base64', '', $meta);
        }

        $meta = trim($meta);
        if ($meta !== '') {
            $contentTypeOut = $meta;
        }

        if ($isBase64) {
            $decoded = base64_decode($data, true);
            return ($decoded === false) ? null : $decoded;
        }

        // utf8 / urlencoded
        return urldecode($data);
    }

    private function guessExtensionByContentType($contentType)
    {
        switch (strtolower((string)$contentType)) {
            case 'image/png':
                return '.png';
            case 'image/jpeg':
                return '.jpg';
            case 'image/svg+xml':
                return '.svg';
            default:
                return '.bin';
        }
    }

    private function safeFilename($name)
    {
        $name = (string)$name;
        $name = preg_replace('/[^\w\.\-\(\)\[\]\s]+/u', '_', $name);
        $name = trim($name);
        if ($name === '') {
            $name = 'attachment.bin';
        }
        return $name;
    }

    /**
     * 첨부 포함 메일 발송 (PEAR Mail + Mail_mime)
     */
    private function sendMailWithAttachments(array $p)
    {
        $requestId = $p['requestId'] ?? null;
        // PEAR Mail / Mail_mime
        if (!class_exists('\\Mail') || !class_exists('\\Mail_mime')) {
            // autoload 상태에 따라 class_exists 실패할 수 있으니, 파일을 한번 더 로드 시도
            @include_once 'Mail.php';
            @include_once 'Mail/mime.php';
        }

        if (!class_exists('\\Mail') || !class_exists('\\Mail_mime')) {
            throw new Exception('메일 라이브러리(Mail/Mail_mime)를 로드할 수 없습니다.');
        }

        $eol = "\r\n";
        $mime = new \Mail_mime(['eol' => $eol]);
        // ✅ 문자셋 고정 (Gmail 등에서 본문/헤더 깨짐 방지)
        // Mail_mime 은 파라미터로 헤더/본문 charset 을 별도 지정할 수 있음
        $mime->setParam('text_charset', 'UTF-8');
        $mime->setParam('html_charset', 'UTF-8');
        $mime->setParam('head_charset', 'UTF-8');
        $mime->setHTMLBody($p['html']);

        if (!empty($p['attachments']) && is_array($p['attachments'])) {
            foreach ($p['attachments'] as $att) {
                if (!is_array($att)) continue;
                $mime->addAttachment(
                    $att['content'],
                    $att['contentType'] ?: 'application/octet-stream',
                    $att['filename'] ?: 'attachment.bin',
                    false,
                    'base64',
                    'attachment'
                );
            }
        }

        $body = $mime->get();

        $headers = [
            'From' => sprintf('"%s" <%s>', $this->encodeHeaderUtf8($p['fromName']), $p['fromEmail']),
            'To' => $p['toEmail'],
            'Subject' => $this->encodeHeaderUtf8($p['subject']),
        ];
        if (!empty($p['replyTo'])) {
            $headers['Reply-To'] = $p['replyTo'];
        }

        $headers = $mime->headers($headers);

        // ✅ SMTP 우선 발송 (QQ 메일) - Net_SMTP 의존성 없이 직접 SMTP 대화로 전송
        // NOTE: 보안상 권장되진 않지만, 요구사항에 따라 인증코드를 코드에 고정한다.
        //       (trace/log 에는 절대 노출하지 않음)
        $smtpHost = 'smtp.worksmobile.com';
        $smtpPort = 465;
        $smtpEncryption = 'ssl'; // ssl or tls(starttls)
        $smtpUser = 'hello@hellomisterlee.com';
        $smtpPass = '4zMrUmd6U4zF';

        $smtpAttempt = $this->sendViaSmtpRaw([
            'host' => $smtpHost,
            'port' => $smtpPort,
            'encryption' => $smtpEncryption,
            'username' => $smtpUser,
            'password' => $smtpPass,
            'from' => $p['fromEmail'],
            'to' => $p['toEmail'],
            'headers' => $headers,
            'body' => $body,
            'timeout' => 20,
        ]);

        if (!empty($smtpAttempt['success'])) {
            return [
                'success' => true,
                'transport' => 'smtp',
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_user' => $smtpUser,
                'pear_error' => null,
                'request_id' => $requestId,
                'smtp_debug' => $smtpAttempt['debug'] ?? null,
            ];
        }

        // SMTP 실패 시 fallback (sendmail/mail)
        $transport = 'sendmail';
        $sendmailPath = null;
        $sendmailCandidates = [
            'C:\\wamp64\\bin\\sendmail\\sendmail.exe',
            '/usr/sbin/sendmail',
        ];
        $mail = null;
        foreach ($sendmailCandidates as $path) {
            if (@is_file($path)) {
                $params = [
                    'sendmail_path' => $path,
                    'sendmail_args' => '-t -i',
                ];
                $mail = \Mail::factory('sendmail', $params);
                $sendmailPath = $path;
                break;
            }
        }
        if (!$mail) {
            $transport = 'mail';
            $mail = \Mail::factory('mail');
        }

        if ($mail instanceof \PEAR_Error) {
            return [
                'success' => false,
                'transport' => $transport,
                'sendmail_path' => $sendmailPath,
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_user' => $smtpUser,
                'pear_error' => $mail->getMessage(),
                'request_id' => $requestId,
                'smtp_fallback_error' => $smtpAttempt['error'] ?? 'smtp_failed',
                'smtp_debug' => $smtpAttempt['debug'] ?? null,
            ];
        }

        $result = $mail->send($p['toEmail'], $headers, $body);
        if ($result instanceof \PEAR_Error) {
            return [
                'success' => false,
                'transport' => $transport,
                'sendmail_path' => $sendmailPath,
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_user' => $smtpUser,
                'pear_error' => $result->getMessage(),
                'request_id' => $requestId,
                'smtp_fallback_error' => $smtpAttempt['error'] ?? 'smtp_failed',
                'smtp_debug' => $smtpAttempt['debug'] ?? null,
            ];
        }

        return [
            'success' => true,
            'transport' => $transport,
            'sendmail_path' => $sendmailPath,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_user' => $smtpUser,
            'pear_error' => null,
            'request_id' => $requestId,
            'smtp_fallback_error' => $smtpAttempt['error'] ?? 'smtp_failed',
            'smtp_debug' => $smtpAttempt['debug'] ?? null,
        ];
    }

    /**
     * Net_SMTP 의존 없이 SMTP 프로토콜로 직접 전송
     * return: ['success'=>bool,'error'=>string|null,'debug'=>array]
     */
    private function sendViaSmtpRaw(array $cfg)
    {
        $debug = [];
        $host = (string)($cfg['host'] ?? '');
        $port = (int)($cfg['port'] ?? 0);
        $enc = strtolower((string)($cfg['encryption'] ?? ''));
        $user = (string)($cfg['username'] ?? '');
        $pass = (string)($cfg['password'] ?? '');
        $from = (string)($cfg['from'] ?? '');
        $to = (string)($cfg['to'] ?? '');
        $headersArr = (array)($cfg['headers'] ?? []);
        $body = (string)($cfg['body'] ?? '');
        $timeout = (int)($cfg['timeout'] ?? 20);

        if ($host === '' || $port <= 0 || $user === '' || $pass === '' || $from === '' || $to === '') {
            return ['success' => false, 'error' => 'smtp_config_invalid', 'debug' => $debug];
        }

        $remote = $host . ':' . $port;
        if ($enc === 'ssl') {
            $remote = 'ssl://' . $remote;
        }

        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            return ['success' => false, 'error' => 'smtp_connect_failed: ' . $errstr . ' (' . $errno . ')', 'debug' => $debug];
        }
        stream_set_timeout($fp, $timeout);

        $read = function () use (&$fp) {
            $data = '';
            while (!feof($fp)) {
                $line = fgets($fp, 515);
                if ($line === false) {
                    break;
                }
                $data .= $line;
                // multi-line response ends when 4th char is space
                if (strlen($line) >= 4 && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };

        $write = function ($cmd) use (&$fp) {
            fwrite($fp, $cmd . "\r\n");
        };

        $expect = function ($code) use (&$debug, $read) {
            $resp = $read();
            $debug[] = ['resp' => $resp];
            if ($resp === '') {
                return ['ok' => false, 'resp' => $resp];
            }
            $respCode = (int)substr($resp, 0, 3);
            return ['ok' => ($respCode === (int)$code), 'resp' => $resp];
        };

        // 220
        $r = $expect(220);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_greeting_invalid', 'debug' => $debug];
        }

        $ehloHost = 'localhost';
        $write('EHLO ' . $ehloHost);
        $resp = $read();
        $debug[] = ['cmd' => 'EHLO', 'resp' => $resp];
        if ((int)substr($resp, 0, 3) !== 250) {
            // fallback to HELO
            $write('HELO ' . $ehloHost);
            $r2 = $expect(250);
            if (!$r2['ok']) {
                fclose($fp);
                return ['success' => false, 'error' => 'smtp_helo_failed', 'debug' => $debug];
            }
        }

        // AUTH LOGIN
        $write('AUTH LOGIN');
        $r = $expect(334);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_auth_login_not_accepted', 'debug' => $debug];
        }
        $write(base64_encode($user));
        $r = $expect(334);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_auth_username_rejected', 'debug' => $debug];
        }
        $write(base64_encode($pass));
        $r = $expect(235);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_auth_failed', 'debug' => $debug];
        }

        // MAIL FROM / RCPT TO
        $write('MAIL FROM:<' . $from . '>');
        $r = $expect(250);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_mail_from_failed', 'debug' => $debug];
        }

        $write('RCPT TO:<' . $to . '>');
        $r = $expect(250);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_rcpt_to_failed', 'debug' => $debug];
        }

        $write('DATA');
        $r = $expect(354);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_data_not_accepted', 'debug' => $debug];
        }

        // Build raw message
        $rawHeaders = [];
        foreach ($headersArr as $k => $v) {
            if (is_int($k)) {
                // already header line
                $rawHeaders[] = (string)$v;
            } else {
                $rawHeaders[] = $k . ': ' . (string)$v;
            }
        }
        // SMTP DATA 는 CRLF 로 통일
        $normalizedBody = str_replace(["\r\n", "\r"], ["\n", "\n"], $body);
        $normalizedBody = str_replace("\n", "\r\n", $normalizedBody);
        $raw = implode("\r\n", $rawHeaders) . "\r\n\r\n" . $normalizedBody;

        // dot-stuffing
        $raw = preg_replace("/\r\n\./", "\r\n..", $raw);
        if (substr($raw, -2) !== "\r\n") {
            $raw .= "\r\n";
        }

        fwrite($fp, $raw);
        fwrite($fp, ".\r\n");

        $r = $expect(250);
        if (!$r['ok']) {
            fclose($fp);
            return ['success' => false, 'error' => 'smtp_message_rejected', 'debug' => $debug];
        }

        $write('QUIT');
        $expect(221);
        fclose($fp);

        return ['success' => true, 'error' => null, 'debug' => $debug];
    }
}


