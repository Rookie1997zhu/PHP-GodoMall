$(function() {

	var $mainTopSilde = $('.mainTopSilde');

	$mainTopSilde.slick({
		draggable : true,
		fade: true,
		speed: 300,
		autoplaySpeed:6000,
		autoplay: true,
		dots: true,
		adaptiveHeight: true
	});

	slideFilter( useMobileImage, $mainTopSilde );

	$(".mainSilde1").slick({
		centerMode:true,
		centerPadding:'0px',
		speed: 500,
		autoplaySpeed:8000,
		autoplay: true,
		infinite: true,
		dots: false,
		arrows: true,
		slidesToShow: 2,
		slidesToScroll: 1,
		responsive: [
			{ breakpoint: 760, settings: { slidesToShow: 1, slidesToScroll: 1 } }
		],
	});

	$(".reviewSlider").slick({
		speed: 400,
		autoplay: true,
	});

	instagramInit( instagramUse);

});

function instagramInit( instagramUse){
	var active = instagramUse;

	if( active == false ){
		$(".instagramWidget").remove();
	}
}

function slideFilter( useMobileImage, $mainTopSilde ){
	var mainTopSildeFilter = ($(window).width() > 800) ? false : true;

	var filterActive  = function(){
		if( useMobileImage == true ){
			if( $(window).width() > 800){
				if( !mainTopSildeFilter ) filterChange.pc();
			}else{
				if( mainTopSildeFilter ) filterChange.mobile();
			}
		}else{
			filterChange.pc();
		}
	};

	var filterChange  = {
		pc: function() {
			$mainTopSilde.slick('slickUnfilter');
			$mainTopSilde.slick('slickFilter','.wideImages');
			mainTopSildeFilter = true;
		},
		mobile : function() {
			$mainTopSilde.slick('slickUnfilter');
			$mainTopSilde.slick('slickFilter','.fitImages'); 
			mainTopSildeFilter = false;
		},
	};

	filterActive();
	$(window).on( "resize", function() { filterActive(); });
}

