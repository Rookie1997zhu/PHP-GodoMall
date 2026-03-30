$(document).ready(function(){
	/* 상단 배너 */
	var topBnH = $('.top-line .top-bn').find('img').height();
	function topBanner(){
		if ($('.top-line').css('height')=='0px') {
			$('.top-line').stop(true,true).animate({height:topBnH});
		}
	}
	//topBanner();

	$('.top-line .top-btn-close').click(function() {
		$('.top-line').stop(true,true).animate({height:0});
	});
});