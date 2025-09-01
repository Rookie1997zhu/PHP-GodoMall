$(window).load(function(){
	if( $("#grid").length > 0){
		var grid = document.querySelector('.grid');

		var msnry = new Masonry( grid, {
		  itemSelector: '.item',
		  percentPosition: true
		});

		imagesLoaded( grid ).on( 'progress', function() {
		  msnry.layout();
		});
	}
});

