/* 메인 애니메이션 */
function animateM($item){
	var scrollTop = $(window).scrollTop() + ($(window).height()/7*5);
	if (scrollTop > $item.offset().top) {
		$item.addClass('motion');
	} 
}
function showDiv(){
	var mainDivEl = $('.main-wrap > div');
	for (var i = 0; i < mainDivEl.length; i++) {
		animateM( mainDivEl.eq(i) );
	}
	animateM( $('.main-visual') );
}

/*  반응형 스와이퍼 */
var popularSwiper = undefined;		//인기상품
var recTabSwiper = undefined;    // 추천상품 상단 탭
var recContSwipers = [];         // 추천상품 하단 리스트들 (여러 개이므로 배열)

function initSwiper() {
	var ww = $(window).outerWidth();

	/*인기상품*/
	if (ww >= 768 && popularSwiper == undefined) {
		popularSwiper = new Swiper('.popular-prd .swiper-container', {
			spaceBetween: 28,
			slidesPerView: 4,
			loop: true,
			autoplay: { delay: 3000, disableOnInteraction: false },
			navigation: {
				nextEl: ".popular-prd .swiper-button-next",
				prevEl: ".popular-prd .swiper-button-prev",
			},
		});
    } else if (ww < 768 && popularSwiper != undefined) {
		popularSwiper.destroy(true, true);
		popularSwiper = undefined;
    }

	/* 2. 추천상품  */
	if (ww < 768) {
		// 상단 탭 
		if (recTabSwiper == undefined) {
			recTabSwiper = new Swiper('.recommend-prd .goods_tab_tit', {
				spaceBetween: 10,
				slidesPerView: 'auto',
				freeMode: true,
				slidesOffsetAfter: 10,
				slidesOffsetBefore: 10,
				observer: true,
				observeParents: true,	
			});
		}
		// 하단 상품 리스트 (배열이 비어있을 때만 실행)
		if (recContSwipers.length === 0) {
			$('.recommend-prd .goods_tab_box').each(function() {
				var s = new Swiper(this, {
					spaceBetween: 10,
					slidesPerView: 2.36,
					slidesOffsetAfter: 10,
					slidesOffsetBefore: 10,
					observer: true,
					observeParents: true,	
				});
				recContSwipers.push(s); // 생성된 스와이퍼들을 배열에 담음
			});
		}
	} else {
		if (recTabSwiper != undefined) {
			recTabSwiper.destroy(true, true);
			recTabSwiper = undefined;
		}
		if (recContSwipers.length > 0) {
			recContSwipers.forEach(function(s) {
				s.destroy(true, true);
			});
			recContSwipers = []; // 배열 초기화 (매우 중요)
		}
	}

}

$(window).resize(function(){
	ww = $(window).outerWidth();
	initSwiper();
});


$(document).ready(function(){
	showDiv();
	$(window).scroll(showDiv);
	initSwiper();

	/* 메인 비주얼 */
	$(".main-visual").each(function () {
		var $visual = $(this);
		$visual.find(".swiper-container").each(function () {
			var $container = $(this);
			var swiper = this.swiper; 

			$container.find('.swiper-slide').each(function() {
				var title = $visual.find('.slider-nav > li');
				var idx = $(this).data('swiper-slide-index');
				if ($(this).find('.visual-title').length === 0) {
					$(this).append(title.eq(idx).html());
				}
			});

			function updateProgress(s) {
				var total = $(s.el).find('.swiper-slide:not(.swiper-slide-duplicate)').length;
				var current = s.realIndex + 1;
				var percent = (current / total) * 100;
				$container.closest('.visual-inner').find(".progress-fill").css("width", percent + "%");
			}

			updateProgress(swiper);
			swiper.on("slideChange", function () {
				updateProgress(this);
			});

			var $parentView = $container.closest('.visual-inner');
			
			$parentView.find(".btn-play").off("click").on("click", function () {
				swiper.autoplay.start();
				$(this).hide();
				$parentView.find(".btn-pause").show();
			});
			
			$parentView.find(".btn-pause").off("click").on("click", function () {
				swiper.autoplay.stop();
				$(this).hide();
				$parentView.find(".btn-play").show();
			});
		});
	});

	/* 메인 퀵 메뉴 배너 모션 */
	$('.main-quick-menu ul > li ').each(function(i){
		var num = 3;
		for (j=0;j<=i;j++) {
			num += 1;
		}
		$('.main-quick-menu ul > li').eq(i).addClass('ani'+num);
	});

	/* 사업자몰 고객님 주목하는 제품 */
	$('.interest-prd .swiper-container').each(function() {
		var swiper = new Swiper(this, {
			slidesPerView: 1,
			spaceBetween: 30,
			loop: true,
			observer: true,
			observeParents: true,
			loopAdditionalSlides: 2,
			autoplay: {
				delay: 3000,
				disableOnInteraction: false,
			},
			pagination: {
				el: ".interest-prd .swiper-pagination",
				clickable: true,
			},
			breakpoints: {
				1200: {
					spaceBetween: 60,
					slidesPerView: 3,
					navigation: {
						nextEl: ".interest-prd .swiper-button-next",
						prevEl: ".interest-prd .swiper-button-prev",
					},
				},
				768: {
					slidesPerView: 3,
					spaceBetween: 20,
					navigation: {
						nextEl: ".interest-prd .swiper-button-next",
						prevEl: ".interest-prd .swiper-button-prev",
					},
				},
			},
		});
	});

	/* 인기 브랜드 추천 */
	$('.brand-prd .swiper-container').each(function() {
		var swiper = new Swiper(this, {
			spaceBetween: 10,
			slidesPerView: 1,
			loop: true, 
			observer: true,
			observeParents: true,    
			pagination: {
				el: ".brand-prd .swiper-pagination",
				clickable: true,
			},
			breakpoints: {
				912: {
					loop: false, 
					slidesPerView: 2,
					slidesPerGroup: 2,
					spaceBetween: 20,
				},
				1200: {
					loop: false, 
					slidesPerView: 2,
					slidesPerGroup: 2,
					spaceBetween: 60,
				}
			}
		});
	});

	/* 사업자몰 고객지원 */
	$('.main-support .swiper-container').each(function() {
		var swiper = new Swiper(this, {
			spaceBetween: 30,
			slidesPerView: 1,
			loop: true,
			observer: true,
			observeParents: true,
			autoplay: {
				delay: 3000,
				disableOnInteraction: false,
			},
			pagination: {
				el: ".main-support .swiper-pagination",
				clickable: true,
			},
			breakpoints: {
				768: {
					slidesPerView: 2,
					loop: false,
				},
				1200: {
					slidesPerView: 3,
					loop: false,
					navigation: {
						nextEl: ".main-support .swiper-button-next",
						prevEl: ".main-support .swiper-button-prev",
					},
				}
			}
		});
	});

	/* 베스트 상품 추천 */
	$('.best-prd').each(function() {
		var $this = $(this);
		new Swiper($this.find('.swiper-container')[0], {
			spaceBetween: 10,
			slidesPerView: 2.36,
			loop: false,
			grabCursor: true,
			slidesOffsetBefore: 10,
			slidesOffsetAfter: 10,
			scrollbar: {
				el: $this.find('.swiper-scrollbar')[0],
				hide: false,
				draggable: true
			},
			breakpoints: {
				768: {
					slidesPerView: 1.8,
				},
				1200: {
					spaceBetween: 28,
					slidesPerView: 3,
					slidesOffsetBefore: 0,
					slidesOffsetAfter: 0,
				}
			}
		});
	});
});