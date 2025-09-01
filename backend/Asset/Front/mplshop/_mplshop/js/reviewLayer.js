$(function() {
	var getData = function (id, target) {
		id = id.toLowerCase();
		switch (id) {
			case 'bdid' :
			case 'sno' :
			case 'notice' :
				return $(target).closest('.js_detail').data(id);
				break;
			case 'memosno' :
				return $(target).closest('.js_data_comment_row').data(id);
			case 'memoauth' :
				return $(target).closest('.js_data_comment_row').data(id);
		}
	}

	$('#reviewDetailLayer').delegate('.js_comment_btn_write', 'click', function (e) {
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var notice = getData('notice', this);
		var domRoot = domSelect(bdId, sno, notice);
		var isSecret = 'n';

		if ($('#info_collection_agree_write').length > 0) {
			if($('#info_collection_agree_write').is(':checked') == false) {
				alert(__('개인정보 수집항목에 동의해주세요.'));
				return;
			}
		}

		if(gd_is_valid_form(domRoot.commentFormWrite.element) == false) {
			return false;
		}

		if (domRoot.commentFormWrite.isSecret.is(':checked') || domRoot.commentFormWrite.isSecret.val() === 'y' ) {
			isSecret = 'y';
		}

		$.ajax({
			method: "POST",
			url: "../board/memo_ps.php",
			data: {
				mode: 'write',
				bdSno: sno,
				bdId: bdId,
				memo: domRoot.commentFormWrite.memo.val(),
				writerNm: domRoot.commentFormWrite.writerNm.val(),
				writerPw: domRoot.commentFormWrite.password.val(),
				isSecret: isSecret,
			},
			dataType: 'json'
		}).success(function (data) {
			if (data['result'] == 'ok') {
				alert(data['msg']);
				domRoot = domSelect(bdId, sno, 'n');
				var memoCnt = domRoot.dataRow.parent().find('.js_comment_count').html();
				var regex = /[^0-9]/g;
				if (typeof memoCnt != 'undefined') {
					memoCnt = memoCnt.replace(regex, '');
					memoCnt = Number(memoCnt) + 1;
					domRoot.dataRow.parent().find('.js_comment_count').html(memoCnt);
				}
				else {
					var memoHtml = '댓글&nbsp;<span class="js_comment_count">1</span>';
					domRoot.dataRow.parent().find('.board_memo').after(memoHtml);
				}
				gd_show_ajax_detail(bdId, sno, true, notice);
			}
			else {
				alert(data['msg']);
				return;
			}
		}).error(function (e) {
			alert(e.responseText);
		});
	});

	$('#reviewDetailLayer').delegate('.js_comment_btn_modify', 'click', function () {
		var mode = 'modify';
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var notice = getData('notice', this);
		var memoSno = getData('memosno', this);
		var auth = getData('memoauth', this);
		var commentFormAction = domSelect(bdId, sno, notice).commentFormAction(memoSno);

		if (auth === 'c' && commentFormAction.secretReply.val() === 'y') {
			if (commentFormAction.element.is(':visible')) {
				showModifyArea();
			}else{
				passwordLayerMemo.show();
				passwordLayerMemo.btn.unbind('click').bind('click', function () {
					checkReplyPassWord();
				});
			}
		} else {
			showModifyArea();
		}

		function checkReplyPassWord() {
			$.ajax({
				method: "POST",
				url: "../board/memo_ps.php",
				data: {
					mode: 'checkPassWord',
					bdSno: sno,
					sno: memoSno,
					bdId: bdId,
					writerPw: passwordLayerMemo.value(),
				},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] === 'ok') {
					passwordLayerMemo.close();
					showModifyArea();
				}
				else {
					alert(data['msg']);
					return;
				}
			}).error(function (e) {
				alert(e.responseText);
			});
		}

		function showModifyArea() {
			commentFormAction.init();
			if (commentFormAction.element.is(':visible')) {
				commentFormAction.element.hide();
			}
			else {
				commentFormAction.element.show();
			}

			commentFormAction.writerNm.prop('readonly', true);
			$.ajax({
				method: "POST",
				url: "../board/memo_ps.php",
				data: {
					mode: 'getMemo',
					bdId: bdId,
					bdSno: sno,
					sno: memoSno,
				},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] == 'ok') {
					commentFormAction.writerNm.val(data.writerNm);
					commentFormAction.memo.val(data.memo);
				}
				else {
					alert(data['msg']);
					return;
				}
			});
		}

		commentFormAction.btn.unbind('click').bind('click', function () {
			var isSecret = 'n';

			if ($('#info_collection_agree_action').length > 0) {
				if($('#info_collection_agree_action').is(':checked') == false) {
					alert(__('개인정보 수집항목에 동의해주세요.'));
					return;
				}
			}

			if(gd_is_valid_form(commentFormAction.element) == false) {
				return false;
			}

			if ($('#secretReplyModify').is(":checked") || commentFormAction.isSecret.val() === 'y') {
				isSecret = 'y';
			}

			$.ajax({
				method: "POST",
				url: "../board/memo_ps.php",
				data: {
					mode: mode,
					bdId: bdId,
					bdSno: sno,
					sno: memoSno,
					writerNm: commentFormAction.writerNm.val(),
					writerPw: commentFormAction.password.val(),
					memo: commentFormAction.memo.val(),
					isSecret: isSecret,
				},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] == 'ok') {
					alert(data['msg']);
					gd_show_ajax_detail(bdId, sno, true, notice);
				}
				else {
					alert(data['msg']);
					return;
				}
			}).error(function (e) {
				alert(e.responseText);
			});
		});
	});

	$('#reviewDetailLayer').delegate('.js_comment_btn_reply', 'click', function () {
		var mode = 'reply';
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var isSecret = 'n';
		var notice = getData('notice', this);
		var memoSno = getData('memosno', this);
		var commentFormAction = domSelect(bdId, sno, notice).commentFormAction(memoSno);
		commentFormAction.init();

		if (commentFormAction.element.is(':visible')) {
			commentFormAction.element.hide();
		}
		else {
			$('.js_action_form').hide();
			commentFormAction.element.show();
		}

		commentFormAction.writerNm.prop('readonly', false);
		commentFormAction.btn.unbind('click').bind('click', function () {
			if ($('#info_collection_agree_action').length > 0) {
				if($('#info_collection_agree_action').is(':checked') == false) {
					alert(__('개인정보 수집항목에 동의해주세요.'));
					return;
				}
			}

			if ($('#secretReplyModify').is(":checked") || commentFormAction.isSecret.val() === 'y') {
				isSecret = 'y';
			}

			if (!gd_is_valid_form(commentFormAction.element)) {
				return false;
			}

			$.ajax({
				method: "POST",
				url: "../board/memo_ps.php",
				data: {
					mode: mode,
					bdId: bdId,
					bdSno: sno,
					sno: memoSno,
					writerNm: commentFormAction.writerNm.val(),
					writerPw: commentFormAction.password.val(),
					memo: commentFormAction.memo.val(),
					isSecret: isSecret,
				},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] == 'ok') {
					alert(data['msg']);
					gd_show_ajax_detail(bdId, sno, true, notice);
				}
				else {
					alert(data['msg']);
					return;
				}
			}).error(function (e) {
				alert(e.responseText);
			});
		});
	});

	$('#reviewDetailLayer').delegate('.js_comment_btn_delete', 'click', function () {
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var notice = getData('notice', this);
		var memoSno = getData('memosno', this);
		var auth = getData('memoauth', this);

		if (auth == 'c') {
			passwordLayerMemo.show();
			passwordLayerMemo.btn.unbind('click').bind('click', function () {
				gd_memo_delete_ajax_process();
			});
			return;
		}
		if (!confirm(__('정말 삭제하시겠습니까?'))) {
			return false;
		}
		gd_memo_delete_ajax_process();

		function gd_memo_delete_ajax_process() {
			$.ajax({
				method: "POST",
				url: "../board/memo_ps.php",
				data: {
					mode: 'delete',
					bdSno: sno,
					sno: memoSno,
					bdId: bdId,
					writerPw: passwordLayerMemo.value(),
				},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] == 'ok') {
					alert(data['msg']);
					gd_show_ajax_detail(bdId, sno, true, notice);
				}
				else {
					alert(data['msg']);
					return;
				}
			}).error(function (e) {
				alert(e.responseText);
			});
		}
	});

	$('#reviewDetailLayer').delegate('.js_comment_btn_secret', 'click', function () {
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var memoSno = getData('memosno', this);
		var auth = getData('memoauth', this);

		var commentContent = domSelect(bdId, sno);

		if (auth == 'c') {
			passwordLayerMemo.show();
			passwordLayerMemo.btn.unbind('click').bind('click', function () {
				viewSecretMemoAjaxProcess();
			});
			return;
		}

		function viewSecretMemoAjaxProcess() {
			$.ajax({
				method: "POST",
				url: "../board/memo_ps.php",
				data: {
					mode: 'getSecretMemo',
					bdSno: sno,
					sno: memoSno,
					bdId: bdId,
					writerPw: passwordLayerMemo.value(),
				},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] == 'ok') {
					commentContent.commentRow(memoSno).find('p > em').text(data['memo']);
					passwordLayerMemo.close();
				}else {
					alert(data['msg']);
					return;
				}
			}).error(function (e) {
				alert(e.message);
			});
		}
	});

	$('body').delegate('.reviewBtnViewPop', 'click', function () {
		var bdId = $(this).data('bdid');
		var sno = $(this).data('sno');
		var auth = $(this).data('auth');
		var notice = $(this).data('notice');

		if (auth == 'n') {
			alert(__('권한이 없습니다.'));
			return;
		} else if (auth == 'c') {
			passwordLayer.show();
			passwordLayer.btn.unbind('click').bind('click', function () {
				gd_show_ajax_detail(bdId, sno, false, notice);
			});
			return;
		}
		gd_show_ajax_detail(bdId, sno, false, notice);
	});

	$('#reviews').delegate('.js_btn_view', 'click', function () {
		var bdId = $(this).closest('.js_data_row').data('bdid');    //전역변수등록
		var sno = $(this).closest('.js_data_row').data('sno');  //전역변수등록
		var auth = $(this).closest('.js_data_row').data('auth');
		var notice = $(this).closest('.js_data_row').data('notice');

		if (auth == 'n') {
			alert(__('권한이 없습니다.'));
			return;
		} else if (auth == 'c') {
			passwordLayer.show();
			passwordLayer.btn.unbind('click').bind('click', function () {
				gd_show_ajax_detail(bdId, sno, false, notice);
			});
			return;
		}
		gd_show_ajax_detail(bdId, sno, false, notice);
    });

	$('#reviewDetailLayer').delegate('.js_btn_modify', 'click', function () {
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var auth = $(this).data('auth');

		switch (auth) {
			case 'n' :
				alert(__('권한이 없습니다.'));
				break;
			case 'y' :
				gd_modify_write();
				break;
			case 'c' :
				passwordLayerMemo.show();
				passwordLayerMemo.btn.unbind('click').bind('click', function () {
					$.ajax({
						method: "POST",
						url: "../board/board_ps.php",
						data: {mode: 'modifyCheck', sno: sno, bdId: bdId, writerPw: passwordLayerMemo.value()},
						dataType: 'json'
					}).success(function (data) {
						if (data['result'] == 'ok') {
							gd_modify_write();
							passwordLayerMemo.close();
						}
						else {
							alert(data['msg']);
							return;
						}
					}).error(function (e) {
						alert(e.responseText);
					});
				});
				break;
		}

		function gd_modify_write() {
			var url="../board/popup_goods_board_write.php?mode=modify&bdId="+bdId+"&sno="+sno+"&oldPassword="+passwordLayerMemo.value();
			//window.open(url,'review','width=780,height=850,left=300,scrollbars=yes');

			var formHtml = "<form method='post' action='" + boardData.modifyUrl.format(bdId, sno) + "' id='frmModify'>";
			formHtml += '<input type="hidden" name="bdId" value="' + bdId + '"/>';
			formHtml += '<input type="hidden" name="sno" value="' + sno + '"/>';
			formHtml += '<input type="hidden" name="oldPassword" value="' + passwordLayerMemo.value() + '"/>';
			$('body').append(formHtml);
			$('#frmModify').submit();
		}
	});

	//상품문의,후기 작성 레이어창
	var writerLayer = {
		element: $('#lyWritePop'),
		show: function (data) {
			$('#layerDim').removeClass('dn');
			this.element.removeClass('dn');
			this.element.html(data);
			this.element.currentCenter();
		},
		close: function () {
			this.element.find('.layer_close').trigger('click');
		},
	}

	$('#reviewDetailLayer').delegate('.js_btn_delete', 'click', function () {
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		var auth = $(this).data('auth');
		switch (auth) {
			case 'n' :
				alert(__('권한이 없습니다.'));
				break;

			case 'y' :
				if (confirm(__('정말 삭제하시겠습니까?'))) {
					gd_delete_ajax_process();
				}
				break;
			case 'c' :
				passwordLayerMemo.show();
				passwordLayerMemo.btn.unbind('click').bind('click', function () {
					gd_delete_ajax_process();
				});
				break;
		}

		function gd_delete_ajax_process() {
			$.ajax({
				method: "POST",
				url: "../board/board_ps.php",
				data: {mode: 'delete', sno: sno, bdId: bdId, writerPw: passwordLayerMemo.value()},
				dataType: 'json'
			}).success(function (data) {
				if (data['result'] == 'ok') {
					alert(data['msg']);
					self.location.reload();
				}
				else if (data['result'] == 'confirmPassword') {
					passwordLayerMemo.show();
				}
				else {
					alert(data['msg']);
					return;
				}
			}).error(function (e) {
				alert(e.responseText);
			});
		}
	});

	$('#reviewDetailLayer').delegate('.js_btn_recommend', 'click', function () {
		var bdId = getData('bdId', this);
		var sno = getData('sno', this);
		$.get('../board/board_ps.php', {
			'mode': 'recommend',
			'bdId': bdId,
			'sno': sno
		}, function (data, status) {
			if (status == 'success') {
				alert(data['message']);
				$('#recommendCount').find('strong').html(data['recommendCount']);
			}
			else {
				alert('request fail. ajax status (' + status + ')');
			}
		}, 'json');
	});

	$("#reviewLayerDim, .js_password_layer .review_layer_close, #reviewDetailLayer .detailLayerClose").click(function(){
		reviewCloseLayer();
		return false;
	});

	$(".js_password_layer2 .review_layer_close").click(function(){
		reviewCloseLayer2();
		return false;
	});
	var passwordLayer = {
		element: $('.js_password_layer'),
		btn: $('.js_password_layer').find('.js_submit'),
		value: function () {
			return $('.js_password_layer').find('input[name="writerPw"]').val();
		},
		show: function () {
			this.element.removeClass('dn');
			this.element.currentCenter();
			$('#reviewLayerDim').removeClass('dn');
			$('#scroll_left, #scroll_right').addClass('dim');
			$('.js_password_layer .ly_cont .text').focus();
		},
		close: function () {
			this.element.addClass('dn');
			$('.js_password_layer').find('input[name="writerPw"]').val('');
		}
	}

	var passwordLayerMemo = {
		element: $('.js_password_layer2'),
		btn: $('.js_password_layer2').find('.js_submit'),
		value: function () {
			return $('.js_password_layer2').find('input[name="writerPw"]').val();
		},
		show: function () {
			this.element.removeClass('dn');
			this.element.currentCenter();
			$('#reviewLayerDim').removeClass('dn');
			$('#scroll_left, #scroll_right').addClass('dim');
			$('.js_password_layer2 .ly_cont .text').focus();
		},
		close: function () {
			this.element.addClass('dn');
			$('.js_password_layer2').find('input[name="writerPw"]').val('');
		}
	}

	var gd_show_ajax_detail = function (bdId, sno, isReload, notice) {
		var domRoot = domSelect(bdId, sno, notice);

		$('#reviewLayerDim').removeClass('dn');
		$('#scroll_left, #scroll_right').addClass('dim');

		$.ajax({
			method: "get",
			url: "../goods/goods_board_view.php",
			async: true,
			cache: false,
			data: {sno: sno, bdId: bdId, writerPw: passwordLayer.value()},
			dataType: 'json'
		}).success(function (data) {
			if (data.result == 'fail') {
				alert(data.contents);
				$('.js_password_layer').find('input[name="writerPw"]').val('');
				$('#reviewLayerDim').addClass('dn').removeAttr('style');
				return;
			}

			if (domRoot.detail.html().length == 0) {
				$("#reviewDetailLayer .detailContentsImagesSlider").slick('unslick');
			}

			var detailContents = $(data.contents),
				detailContentsImage = detailContents.find(".detailViewContents img").detach();

			detailContents.find(".detailContentsImages .detailContentsImagesSlider").prepend(detailContentsImage);
			detailContents.find(".detailContentsImages .detailContentsImagesSlider").find("img").wrap('<div>');
			detailContents.find(".addReview").removeClass('dn');
			detailContents.find(".board_view").remove();

			if( !isReload ){
				domRoot.detail.attr({"data-bdid":bdId, "data-sno":sno, "data-notice":notice}).html("");
			}
			$("#reviewDetailLayer").removeClass('dn').currentCenter();
			domRoot.detail.html(detailContents.html());
			$('#reviewDetailLayer .detailContentsImagesSlider').on('init', function(event, slick){	
				if( $(this).find(".slick-active").length > 0 ){
					$(this).find(".slick-active").find('img').load(function(){
						$('#reviewDetailLayer .detailContentsImagesSlider').find(".slick-list").height($(this).height());
					});
				}else{
					$(this).find(".slick-list").height('auto');
				}
			});
			$("#reviewDetailLayer .detailContentsImagesSlider").slick({adaptiveHeight:true,dots:true});
			$('#reviewLayerDim').removeAttr('style');

			passwordLayer.close();
			passwordLayerMemo.close();
		}).error(function (e) {
			alert('fail');
			alert(e.responseText);
			$('#reviewLayerDim').addClass('dn').removeAttr('style');
		});
	};

	var domSelect = function (bdId, sno, notice) {

		var addDataAttribute = '';
		if($.trim(notice) !== ''){
			addDataAttribute = "[data-notice=" + notice + "]";
		}

		var formWrite = $("#reviewDetailLayer .js_detail").find('.js_form_write');
		return {
			dataRow: $(".detailViewContent[data-bdid=" + bdId + "][data-sno=" + sno + "]"+addDataAttribute),
			detail: $("#reviewDetailLayer .js_detail"),
			commentRow: function (memoSno) {
				return this.detail.find('.js_data_comment_row[data-memosno=' + memoSno + ']');
			},
			commentFormWrite: {
				element: formWrite,
				writerNm: formWrite.find('input[name=writerNm]'),
				password: formWrite.find('input[name=password]'),
				memo: formWrite.find('textarea[name=memo]'),
				isSecret: formWrite.find('input[name=isSecretReply]'),
			},
			commentFormAction: function (memoSno) {
				return {
					element: this.commentRow(memoSno).find('.js_action_form'),
					writerNm: this.commentRow(memoSno).find('input[name=writerNm]'),
					password: this.commentRow(memoSno).find('input[name=password]'),
					memo: this.commentRow(memoSno).find('textarea[name=memo]'),
					isSecret: this.commentRow(memoSno).find('input[name=isSecretReply]'),
					btn: this.commentRow(memoSno).find('.js_comment_btn_action'),
					secretReply: this.commentRow(memoSno).find('input[name=secretReply]'),
					init: function () {
						this.writerNm.val(null);
						this.password.val(null);
						this.memo.val(null);
					},
				}
			}
		}
	};

	var gd_is_valid_form = function (elObj) {
		var writerNmEl = elObj.find('input[name=writerNm]');
		var passwordEl = elObj.find('input[name=password]');
		var memoEl = elObj.find('textarea[name=memo]');
		if (writerNmEl.length > 0) {
			if (writerNmEl.val() == '') {
				alert(__('이름을 입력해주세요.'));
				writerNmEl.focus();
				return false;
			}
		}

		if (passwordEl.length > 0) {
			if (passwordEl.val() == '') {
				alert(__('비밀번호를 입력해주세요.'));
				passwordEl.focus();
				return false;
			}
		}

		if (memoEl.length > 0) {
			if (memoEl.val() == '') {
				alert(__('댓글 내용을 입력해주세요.'));
				memoEl.focus();
				return false;
			}
		}

		return true;
	};

	var reviewCloseLayer = function () {
		if ($('#reviewLayerDim').is(':visible') ) {
			if( $('#reviewDetailLayer').is(':visible') ){
				$('#reviewDetailLayer').addClass('dn').removeAttr('style');
				$('#reviewLayerDim').addClass('dn').removeAttr('style');
			}
			if( $('.js_password_layer').is(':visible') ){
				$('#reviewLayerDim').addClass('dn');
				passwordLayer.close();
			}
		}
	}

	var reviewCloseLayer2 = function () {
		if ($('#reviewLayerDim').is(':visible') && $('.js_password_layer2').is(':visible')) {
			passwordLayerMemo.close();
		}
	}

	$(window).resize(function(){
		if( $('#reviewDetailLayer').is(':visible') ){
			$("#reviewDetailLayer").currentCenter();
		}
	});
	
});

function gd_open_write_layer(bdId, goodsNo, sno) {
    $.ajax({
        method: "GET",
        url: "../goods/goods_board_write.php",
        data: {mode: 'write', bdId: bdId, goodsNo: goodsNo, orderGoodsNo : sno},
        dataType: 'text'
    }).success(function (data) {
        writerLayer.show(data);
        gd_center_layer();
    }).error(function (e) {
        alert(e.responseText);
    });
};

function gd_open_write_popup(bdId, goodsNo,orderGoodsNo) {
    if(_.isUndefined(orderGoodsNo)){
        orderGoodsNo = 0;
    }
    var url="../board/popup_goods_board_write.php?mode=write&bdId="+bdId+"&goodsNo="+goodsNo+"&orderGoodsNo="+orderGoodsNo;
    window.open(url,'review','width=850,height=850,left=300,scrollbars=yes');
}

//페이징
function goGoodsAjaxPage(bdId, queryStirng) {
	var ajaxDataEl = '';
	var page = gd_get_query_variable(queryStirng, 'page');
	var goodsNo = gd_get_query_variable(queryStirng, 'goodsNo');
	if (bdId == 'goodsreview') {
		ajaxDataEl = $('#ajax-goods-goodsreview-list');
	}
	else if (bdId == 'goodsqa') {
		ajaxDataEl = $('#ajax-goods-goodsqa-list');
	}
	else {
		alert('error illgal board id');
		return;
	}
	$.ajax({
		method: "GET",
		cache: false,
		url: "./goods_board_list.php",
		data: {bdId: bdId, goodsNo: goodsNo, page: page, gboard: 'y'},
		dataType: 'text'
	}).success(function (data) {
		ajaxDataEl.hide();
		ajaxDataEl.fadeIn('fast');
		ajaxDataEl.html(data);
	}).error(function (e) {
		alert(e.responseText);
	});
}

function gd_load_goodsBoardList(bdId, goodsNo, sno) {
	$.ajax({
		method: "GET",
		url: "./goods_board_list.php",
		data: {bdId: bdId, goodsNo: goodsNo, gboard: 'y'},
		dataType: 'text',
		cache: false,
		async: true,
	}).success(function (data) {
		$('#ajax-goods-' + bdId + '-list').html(data);
		if (typeof sno != 'undefined') {
			gd_show_ajax_detail(bdId, sno, true, '');
		}
	}).error(function (e) {
		alert(e.responseText);
	});
}