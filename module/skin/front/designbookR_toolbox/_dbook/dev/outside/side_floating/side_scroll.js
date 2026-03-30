$(document).ready(function(){
	const scrollFloating = () => {

		var $this = $('.side-floating'),
			scrollTop = $(window).scrollTop(),
			winH = $(window).height(),
			_bottom = $('#footer').offset().top - winH;

		if ( $('.body-main.body-index').length ) {
			if ( scrollTop > 10 ) {
				$this.fadeIn();
			} else {
				$this.fadeOut();
			}
		}

		if ( scrollTop > _bottom ) {
			$this.addClass('end-stop');
		} else {
			$this.removeClass('end-stop');
		}

	}
	$(window).scroll(scrollFloating);
	scrollFloating();

});