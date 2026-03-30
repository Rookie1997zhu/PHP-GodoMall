var windowWidth = $(window).width();

function sizeListener() {
}

$(window).resize(function(){
	sizeListener();	
});
$(document).ready(function(){

	sizeListener();

	/* 하단 고정 */
	var last_scrollTop = 0;
	$(window).scroll(function () {
		var tmp = $(this).scrollTop();

		if ( tmp > 100 ) {
			if (tmp > last_scrollTop) {
				$('.bottom-nav').addClass('active');
				$('.side-floating').removeClass('on');
			} else {
				$('.bottom-nav').removeClass('active');
				$('.side-floating').addClass('on');
			}
			last_scrollTop = tmp;
		}
	});


	$(".goodsview-big-slide").each(function() {
		var $this = $(this);
		var slideCount = $this.find('.thumbs-slide .swiper-slide').length;

		if (slideCount > 2) {
			var thumbSlide = new Swiper($this.find('.thumbs-slide')[0], {
				spaceBetween: 30,
				slidesPerView: 'auto',
				freeMode: true,
				watchSlidesProgress: true,
			});

			var gallerySlide = new Swiper($this.find('.gallery-slide')[0], {
				loop: true,
				slidesPerView: 1,
				spaceBetween: 0,
				observer: true,
				observeParents: true,
				pagination: {
					el: $this.find('.swiper-pagination')[0], 
					clickable: true,
				},
				navigation: {
					nextEl: $this.find('.swiper-button-next')[0],
					prevEl: $this.find('.swiper-button-prev')[0],
				},
				 thumbs: {	
					swiper: thumbSlide,
				 },	
			});
		} else {
			$this.find(".swiper-button-next, .swiper-button-prev, .swiper-pagination, .slick_goods_next, .slick_goods_prev").hide();
		}
	});
});