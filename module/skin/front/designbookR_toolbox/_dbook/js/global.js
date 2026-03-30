var frmPath = location.pathname;
var frmSearch = location.search;
var frmPathSearch = location.pathname + location.search;

/* 상단 고정 */
function showHeader() {
	var headH = $('#header').height();
	if ($(window).scrollTop() > headH) {
		$('#header').addClass('fixed');
	} else {
		$('#header').removeClass('fixed');
	}
}

function responsiveListener() {
	
	/* 탑배너 높이 */
	if ( $('#header #topLine.mo-view .top-bn img').length )	{
		var topImgH = $('#header #topLine.mo-view').height();
	} else {
		var topImgH = 0;
	}
	document.documentElement.style.setProperty('--top-line-height', topImgH + 'px');

	/* class 변경 */
	var bodyW = window.innerWidth;
	var deviceW = 768;
	if (bodyW <= deviceW) {
		$('body').removeClass('pc').addClass('mobile');
	} else if (bodyW >= deviceW) {
		$('body').removeClass('mobile').addClass('pc');
	}
	if ($('body').hasClass('.mobile')) {
		// 모바일 
	}

	/* 검색 레이어 */
	if (window.matchMedia('(max-width: 912px)').matches) {
		$('.search-layer').addClass('off');
	} else {
		$('.search-layer').removeClass('off');
	}

}

$(window).resize(function(){
	responsiveListener();	
});
$(document).ready(function(){
	responsiveListener();

	/* 상단 고정 */
	$(window).scroll(showHeader);
	showHeader();

	/* 검색 창 : 열림 & 닫힘 */
    const $searchLayer = $('.search-layer');
    function closeSearch() {
        $searchLayer.hide();
    }
    $('#header .search > button').click(function(e) {
        e.preventDefault();
        if ($searchLayer.is(':visible')) {
            closeSearch();
        } else {
            $searchLayer.show();
        }
    });
    $('.search-layer .search-close-btn').click(function(e) {
        e.preventDefault();
        closeSearch();
    });

	/* 상품 목록 */
	if ( $('.body-goods-list, .body-goods-search, .body-goods-main, .body-time-sale').length )	{

		// 상품 정렬
		$('.sort-select em').click(function(){			
			$(this).toggleClass('on');
			$(this).next().slideToggle(200);
		});
		if(frmSearch.indexOf('sort=') > -1){
			setTimeout(function(){
				$('.sort-select em').text($('.sort-select label.on').text());
			}, 1);

		}
	}

	/* 로그인 */
	$('.body-login .member-login-tab li a').on('click', function(e) {
		var $btn = $(this);
		var onForm = $btn.attr('href');
		$('.member_cont').find(onForm).show().siblings().hide();
		e.preventDefault();
	});
	
	/* 회원가입 - 약관 동의 */
	$('.join_agreement_cont .form_element span a').on('click', function(e) {
		var $this = $(this);
		var $contEl = $this.closest('.form_element').next();
		if( $contEl.css('display') == 'none' ) {
			$this.addClass('on');
			$contEl.slideDown();
		} else {
			$this.removeClass('on');
			$contEl.slideUp();
		}
		e.preventDefault();
	});
	
	/* 마이페이지 메인 - 진행 중인 주문 */
	$('.mypage_order_info_cont .order_case_list ul li b').each(function(){
		var str = $(this).text();
			str = str.replace(/• ?/g, '');
		$(this).text(str);
	});
});