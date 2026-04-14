/**
 * request1 견적문의: 제출 전 subject / 본문(에디터)에 견적 필드 병합
 */
(function () {
    var META_START = '<!--REQUEST1_META_START-->';
    var META_END = '<!--REQUEST1_META_END-->';

    function getWriterName() {
        var $in = $('input[name="writerNm"]');
        if ($in.length) {
            return $.trim($in.val());
        }
        var $disp = $('.board_request1_writer_display');
        if ($disp.length) {
            return $.trim($disp.text());
        }
        return '';
    }

    function buildMetaHtml() {
        var company = $.trim($('input[name="estimate_company"]').val() || '');
        var addr1 = $.trim($('input[name="estimate_addr1"]').val() || '');
        var addr2 = $.trim($('input[name="estimate_addr2"]').val() || '');
        var due = $.trim($('input[name="estimate_due_date"]').val() || '');
        var qty = $.trim($('input[name="estimate_qty"]').val() || '');
        var lines = [];
        lines.push('<div class="request1-estimate-meta" style="border:1px solid #e0e0e0;padding:12px;margin-bottom:12px;background:#fafafa;">');
        lines.push('<p style="margin:0 0 8px;font-weight:bold;">[견적문의 정보]</p>');
        lines.push('<table style="border-collapse:collapse;width:100%;font-size:13px;">');
        lines.push('<tr><th style="text-align:left;padding:4px 8px;border:1px solid #eee;width:120px;">업체명</th><td style="padding:4px 8px;border:1px solid #eee;">' + $('<div>').text(company).html() + '</td></tr>');
        lines.push('<tr><th style="text-align:left;padding:4px 8px;border:1px solid #eee;">납품 주소</th><td style="padding:4px 8px;border:1px solid #eee;">' + $('<div>').text(addr1 + ' ' + addr2).html() + '</td></tr>');
        lines.push('<tr><th style="text-align:left;padding:4px 8px;border:1px solid #eee;">희망 납기일</th><td style="padding:4px 8px;border:1px solid #eee;">' + $('<div>').text(due).html() + '</td></tr>');
        lines.push('<tr><th style="text-align:left;padding:4px 8px;border:1px solid #eee;">주문 수량</th><td style="padding:4px 8px;border:1px solid #eee;">' + $('<div>').text(qty).html() + '</td></tr>');
        lines.push('</table></div>');
        return META_START + lines.join('') + META_END;
    }

    function stripOldMeta(html) {
        if (!html || html.indexOf(META_START) === -1) {
            return html;
        }
        var re = /<!--REQUEST1_META_START-->[\s\S]*?<!--REQUEST1_META_END-->/g;
        return html.replace(re, '').trim();
    }

    function validateRequest1() {
        var writer = getWriterName();
        if (!writer) {
            alert(__('작성자를 입력해주세요.'));
            return false;
        }
        var company = $.trim($('input[name="estimate_company"]').val() || '');
        if (!company) {
            alert(__('업체명을 입력해주세요.'));
            return false;
        }
        var $mob = $('input[name="writerMobile"]');
        if ($mob.length && !$.trim($mob.val() || '')) {
            alert(__('휴대폰 번호를 입력해주세요.'));
            return false;
        }
        if ($('.board_request1_need_goods').length && $('#selectGoodsBox input[name="goodsNo[]"]').length === 0) {
            alert(__('상품을 선택해주세요.'));
            return false;
        }
        return true;
    }

    function applyMerge() {
        if (!validateRequest1()) {
            return false;
        }
        var writer = getWriterName();
        var company = $.trim($('input[name="estimate_company"]').val() || '');
        var subject = '[견적문의] ' + company + ' - ' + writer;
        $('input#request1Subject').val(subject);

        var meta = buildMetaHtml();
        var $ta = $('#editor');
        var html = $ta.val() || '';
        html = stripOldMeta(html);
        $ta.val(meta + (html ? '\n' : '') + html);
        return true;
    }

    $(function () {
        if (typeof bdId === 'undefined' || bdId !== 'request1') {
            return;
        }

        setTimeout(function () {
            var $form = $('#frmWrite');
            var validator = $form.data('validator');
            if (!validator) {
                return;
            }
            if (validator.settings.rules && validator.settings.rules.subject) {
                delete validator.settings.rules.subject;
            }

            validator.settings.submitHandler = function (form) {
                if (typeof boardSuubmitFl !== 'undefined' && boardSuubmitFl === false) {
                    alert(__('처리중입니다. 잠시만 기다려주세요.'));
                    return false;
                }
                if ($(form).find('[name="uploadType"]').val() == 'ajax') {
                    $('input:file').prop('disabled', true);
                }
                if (typeof cfgEditorFl !== 'undefined' && cfgEditorFl == 'y' && typeof oEditors !== 'undefined' && oEditors.getById && oEditors.getById['editor']) {
                    oEditors.getById['editor'].exec('UPDATE_CONTENTS_FIELD', []);
                }
                if (!applyMerge()) {
                    return false;
                }

                // 첨부파일을 base64 DataURL로 읽어 hidden field에 담은 뒤 submit
                // (naver 외부주문과 동일한 방식 — OBS 경로 접근 불필요)
                var MAX_TOTAL = 10 * 1024 * 1024; // 10MB
                var $fileInputs = $(form).find('#uploadBox input[type="file"]');
                var filesToRead = [];
                $fileInputs.each(function () {
                    var files = this.files;
                    if (!files) return;
                    for (var i = 0; i < files.length; i++) {
                        filesToRead.push(files[i]);
                    }
                });

                function readOneFile(file) {
                    return new Promise(function (resolve) {
                        var reader = new FileReader();
                        reader.onload = function () {
                            resolve({ name: file.name, type: file.type, size: file.size, dataUrl: reader.result });
                        };
                        reader.onerror = function () { resolve(null); };
                        reader.readAsDataURL(file);
                    });
                }

                var chain = Promise.resolve();
                var imageList = [];
                var totalSize = 0;
                filesToRead.forEach(function (file) {
                    chain = chain.then(function () {
                        if (totalSize + file.size > MAX_TOTAL) {
                            return null;
                        }
                        return readOneFile(file);
                    }).then(function (item) {
                        if (item) {
                            totalSize += item.size;
                            imageList.push(item);
                        }
                    });
                });

                chain.then(function () {
                    $('#request1AttachmentsJson').val(JSON.stringify(imageList));
                    if (typeof boardSuubmitFl !== 'undefined') {
                        boardSuubmitFl = false;
                    }
                    form.submit();
                }).catch(function () {
                    // 파일 읽기 실패 시에도 첨부 없이 그냥 제출
                    $('#request1AttachmentsJson').val('[]');
                    if (typeof boardSuubmitFl !== 'undefined') {
                        boardSuubmitFl = false;
                    }
                    form.submit();
                });
            };
        }, 0);
    });
})();
