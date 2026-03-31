$(function(){
	var $basicContainer = $("#basicInfo"),
		$basicCS = $basicContainer.find(".basicShopInfo"),
		$basicBank = $basicContainer.find(".basicShopBank"),
		$addCategory1 = $basicContainer.find(".addCategory1"),
		$addCategory2 = $basicContainer.find(".addCategory2");


	var $quickContent = $(".quickContent"),
		$quickLink = $quickContent.find(".quickTxtLink ul"),
		$quickBank = $quickContent.find(".quickBank"),
		$quickCs = $quickContent.find(".quickCs"),

		$footer = $("#footer"),
		$footerCs = $footer.find(".footerCs"),
		$footerBank = $footer.find(".footerBank"),
		$footerFavorite = $footer.find(".footerFavorite"),
		$footerBoard = $footer.find(".footerBoard"),

		$bannerCategory = $(".categorySlide .gnbMenuBox .menuCategory").children("li"),

		$mobileAddMenu = $(".mobileAddMenu .mobileAddMenuContainer"),
		$mobileAddMenuAdd1 = $mobileAddMenu.find(".addCategory1"),
		$mobileAddMenuAdd2 = $mobileAddMenu.find(".addCategory2"),
			
		$headerCsBoard = $(".headerCsBoard"),
		$allCategoryDim = $("#allCategoryDim").find(".menuCategory"),
		$serviceLink = $(".serviceLink ul"),
		$asideCommunity = $("#aside .asideCommunity"),
		$boardAddGroup = $headerCsBoard.add($serviceLink).add($asideCommunity).add($footerBoard);


	var centerOpen = undefineRemove($basicCS.find(".centerOpen").html()),
		centerLunch = undefineRemove($basicCS.find(".centerLunch").html()), 
		centerOff = undefineRemove($basicCS.find(".centerOff").html()),
		centerAdd = $basicCS.find("div").not(".centerOpen,.centerLunch,.centerOff"),
		csArray = [centerOpen,centerLunch,centerOff],
		csLunchCheck = true,

		accountHolder = undefineRemove($basicBank.find(".bankAccountHolder").html()),
		accountNumber = $basicBank.find(".bankAccountNumber"),

		basicFavoriteContent = $basicContainer.find(".basicFavorite").html(),
		basicBoardContent = $basicContainer.find(".basicBoard").html(),

		addCategory1Content = $addCategory1.html(),
		addCategory2Content = $addCategory2.html();

	accountNumber.each(function( index ){
		var $this = $(this),
			html = undefineRemove($this.html());

		$quickBank.add($footerBank).append('<li class="contents">'+html+'</li>');
	});

	$quickBank.add($footerBank).append('<li class="contents2">'+accountHolder+'</li>');


	if( csArray[1] != "" ){
		$footerCs.append('<li class="contents">'+csArray[0]+' / '+ csArray[1] +'</li>');
		$footerCs.append('<li class="contents">'+csArray[2]+'</li>');
		csLunchCheck = false;
	}

	$.each(csArray, function(index, item){
		$quickCs.append('<li class="contents">'+item+'</li>');
		if ( csLunchCheck ) $footerCs.append('<li class="contents">'+item+'</li>');
	});

	centerAdd.each(function( index ){
		var $this = $(this),
			html = undefineRemove($this.html());

		$quickCs.add($footerCs).append('<li class="contents">'+html+'</li>');
	});


	$boardAddGroup.append(basicBoardContent);
	$footerFavorite.append(basicFavoriteContent);
	$allCategoryDim.append(addCategoryStructure(basicBoardContent));

	$mobileAddMenuAdd1.append(addCategory1Content);
	$mobileAddMenuAdd2.append(addCategory2Content);

	$bannerCategory.each(function( index ){
		var $this = $(this),
			$child = $this.children(".subCategory"),
			checkChild = ( $child.length > 0 ) ? true : false,
			$bannerContainer = $(".categoryBannerArea"),
			banner = $bannerContainer.children().eq(index).html();

		if( banner ){
			if( checkChild ){
				$child.append("<div class='categoryBanner'>"+banner+"</div>");
			}else{
				$this.addClass("hasChild").append("<div class='subCategory depth1'><div class='categoryBanner'>"+banner+"</div></div>");
			}
		}
	});

	joinPointFunction( useJoinPoint, joinPoint );
	if ( goodsThumb == true ){$("body").addClass("productEffect");}

	var mpl_copy = [];
	mpl_copy[0] = 'padding:4px 17px 3px 13px; font-family:tahoma; line-height:15px; font-size:10px; background: #fafafa; color: #555';
	mpl_copy[1] = 'padding:4px 17px 3px 13px; font-family:tahoma; line-height:15px; font-size:10px; background: #9dc6f7; color: #fff';
	console.log('%c  ', '');
	console.log('%c  Designed by MPLSHOP  ', 'font-family:tahoma; font-size:20px; padding:2px 0; background: #d9fde6; color: #555');
	console.log('%c website : http://mplshop.co.kr', mpl_copy[0]);
	console.log('%c contact us :     chima2003@nate.com', mpl_copy[0]);
	console.log('%c COPYRIGHT - MPLSHOP. ALL RIGHTS RESERVED', mpl_copy[1]);
	console.log('%c  ', '');
});

function addCategoryStructure( boardContents ){
	var categoryHtml = '<li><a href="../service/index.php">고객센터</a><div class="subCategory depth1">'+ boardContents +'</div></li>';
	return categoryHtml;
};

function undefineRemove( text ){
	var retunrDom = text ? text : "";
	return retunrDom;
};

function joinPointFunction( useJoinPoint, joinPoint ){
	var $pointContainer = $("#header .headerTop .headerMember .accent");

	if ( useJoinPoint == true ){
		$pointContainer.show().find("strong").html(joinPoint);
	}else{
		$pointContainer.remove();
	}
};

var MplInsgo = $.parseJSON('{"displayType":"grid","thumbnails":[{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/68668514_169752404157599_606347731488235476_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=EEWu4vgvFKMAX9etwS7&oh=ea396b102c502b71b8b6a9ebbf944b8b&oe=5EB7754F"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/68668514_169752404157599_606347731488235476_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=EEWu4vgvFKMAX9etwS7&oh=ea396b102c502b71b8b6a9ebbf944b8b&oe=5EB7754F"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/68668514_169752404157599_606347731488235476_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=EEWu4vgvFKMAX9etwS7&oh=ea396b102c502b71b8b6a9ebbf944b8b&oe=5EB7754F"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbn5cHh5t"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67449791_845041839226279_8314413900631428764_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=107&_nc_ohc=XA7DbanimdQAX88RY2h&oh=b22215fa218cdbcaf98c6c57ecba0734&oe=5EB77339"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67449791_845041839226279_8314413900631428764_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=107&_nc_ohc=XA7DbanimdQAX88RY2h&oh=b22215fa218cdbcaf98c6c57ecba0734&oe=5EB77339"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67449791_845041839226279_8314413900631428764_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=107&_nc_ohc=XA7DbanimdQAX88RY2h&oh=b22215fa218cdbcaf98c6c57ecba0734&oe=5EB77339"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbmlfHWWO"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69387480_108153503740055_7254300447911319682_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=M8Qk4tw5imUAX9Jns2H&oh=9c2daf9bd50d72d3b1f6f4d0d80c0e92&oe=5EB3F55D"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69387480_108153503740055_7254300447911319682_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=M8Qk4tw5imUAX9Jns2H&oh=9c2daf9bd50d72d3b1f6f4d0d80c0e92&oe=5EB3F55D"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69387480_108153503740055_7254300447911319682_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=M8Qk4tw5imUAX9Jns2H&oh=9c2daf9bd50d72d3b1f6f4d0d80c0e92&oe=5EB3F55D"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vblOynLCG"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69228450_592761377797278_988068177722759518_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=106&_nc_ohc=9Y_xrSZ7MmEAX8zD34V&oh=55c03df92e5031af269bdf9d328ad060&oe=5EB734E9"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69228450_592761377797278_988068177722759518_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=106&_nc_ohc=9Y_xrSZ7MmEAX8zD34V&oh=55c03df92e5031af269bdf9d328ad060&oe=5EB734E9"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69228450_592761377797278_988068177722759518_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=106&_nc_ohc=9Y_xrSZ7MmEAX8zD34V&oh=55c03df92e5031af269bdf9d328ad060&oe=5EB734E9"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbkSNHbeR"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67418627_115590646234265_1906380424526905119_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=105&_nc_ohc=4Ob_YPbrRdYAX8-Up-h&oh=5d75901a958e331beff917d8c1b8b198&oe=5EB3A223"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67418627_115590646234265_1906380424526905119_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=105&_nc_ohc=4Ob_YPbrRdYAX8-Up-h&oh=5d75901a958e331beff917d8c1b8b198&oe=5EB3A223"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67418627_115590646234265_1906380424526905119_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=105&_nc_ohc=4Ob_YPbrRdYAX8-Up-h&oh=5d75901a958e331beff917d8c1b8b198&oe=5EB3A223"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbjAWH4kQ"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67152184_2461125777441759_6332859130249634073_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=103&_nc_ohc=jAEKbQsuZ94AX93G7K6&oh=2129f5b9bcc24b16d3a459047459410b&oe=5EB4A379"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67152184_2461125777441759_6332859130249634073_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=103&_nc_ohc=jAEKbQsuZ94AX93G7K6&oh=2129f5b9bcc24b16d3a459047459410b&oe=5EB4A379"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67152184_2461125777441759_6332859130249634073_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=103&_nc_ohc=jAEKbQsuZ94AX93G7K6&oh=2129f5b9bcc24b16d3a459047459410b&oe=5EB4A379"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbiBnnOw0"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67819363_724003014736894_7670098603551705416_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=110&_nc_ohc=4CnvlNxCYWsAX8T1P-1&oh=a43b2452d68e39427bf0e367fdda6be4&oe=5EB4C964"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67819363_724003014736894_7670098603551705416_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=110&_nc_ohc=4CnvlNxCYWsAX8T1P-1&oh=a43b2452d68e39427bf0e367fdda6be4&oe=5EB4C964"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67819363_724003014736894_7670098603551705416_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=110&_nc_ohc=4CnvlNxCYWsAX8T1P-1&oh=a43b2452d68e39427bf0e367fdda6be4&oe=5EB4C964"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbgdanBp3"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67974428_349838815919811_8810835448422291377_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=103&_nc_ohc=flsn3aFkTc4AX8J74nT&oh=bcb52baad78a14b02a49be88ce90bb23&oe=5EB594C0"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67974428_349838815919811_8810835448422291377_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=103&_nc_ohc=flsn3aFkTc4AX8J74nT&oh=bcb52baad78a14b02a49be88ce90bb23&oe=5EB594C0"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67974428_349838815919811_8810835448422291377_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=103&_nc_ohc=flsn3aFkTc4AX8J74nT&oh=bcb52baad78a14b02a49be88ce90bb23&oe=5EB594C0"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbejLnSDq"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67945842_132143994722315_2302693002989530801_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=bb5wDkCw9NIAX9CTQpz&oh=53ad9d86906259ce3797aa5e175e3373&oe=5EB4ADC7"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67945842_132143994722315_2302693002989530801_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=bb5wDkCw9NIAX9CTQpz&oh=53ad9d86906259ce3797aa5e175e3373&oe=5EB4ADC7"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67945842_132143994722315_2302693002989530801_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=bb5wDkCw9NIAX9CTQpz&oh=53ad9d86906259ce3797aa5e175e3373&oe=5EB4ADC7"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbdWHnMem"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67199584_2834229883268189_6606081532345057249_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=105&_nc_ohc=mNQ9SRvgrUsAX918QQr&oh=ea83f9f45ceea02e1400558a5de993a1&oe=5EB5B74B"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67199584_2834229883268189_6606081532345057249_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=105&_nc_ohc=mNQ9SRvgrUsAX918QQr&oh=ea83f9f45ceea02e1400558a5de993a1&oe=5EB5B74B"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67199584_2834229883268189_6606081532345057249_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=105&_nc_ohc=mNQ9SRvgrUsAX918QQr&oh=ea83f9f45ceea02e1400558a5de993a1&oe=5EB5B74B"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbcBzHOe4"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67308162_169164267581123_2001924565398691627_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=tqCFZH6c4WwAX9XpSCC&oh=79c9a554b5f131c0fe6b7aae36ad044d&oe=5EB4D840"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67308162_169164267581123_2001924565398691627_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=tqCFZH6c4WwAX9XpSCC&oh=79c9a554b5f131c0fe6b7aae36ad044d&oe=5EB4D840"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/67308162_169164267581123_2001924565398691627_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=tqCFZH6c4WwAX9XpSCC&oh=79c9a554b5f131c0fe6b7aae36ad044d&oe=5EB4D840"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vba9enfXR"},{"image":{"thumbnail":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69857508_173486047034111_4032525767628244585_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=MkJp1kADl08AX9R-3qU&oh=3753c6dc82bdbc5373d4b7c2d9ae861b&oe=5EB4CE36"},"low_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69857508_173486047034111_4032525767628244585_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=MkJp1kADl08AX9R-3qU&oh=3753c6dc82bdbc5373d4b7c2d9ae861b&oe=5EB4CE36"},"standard_resolution":{"url":"https:\/\/scontent-ssn1-1.cdninstagram.com\/v\/t51.2885-15\/sh0.08\/e35\/s640x640\/69857508_173486047034111_4032525767628244585_n.jpg?_nc_ht=scontent-ssn1-1.cdninstagram.com&_nc_cat=111&_nc_ohc=MkJp1kADl08AX9R-3qU&oh=3753c6dc82bdbc5373d4b7c2d9ae861b&oe=5EB4CE36"}},"viewUrl":"https:\/\/www.instagram.com\/p\/B1vbZ02HT6z"}],"data":{"widgetSno":"1","widgetInstagramId":"mplshop2019","widgetName":"\uc5e0\ud50c\uc0f5","widgetDisplayType":"grid","widgetThumbnailSize":"auto","widgetThumbnailBorder":"n","widgetOverEffect":"blurPoint","widgetWidthCount":"6","widgetHeightCount":"2","widgetBackgroundColor":"","widgetImageMargin":"5","total":12}}');