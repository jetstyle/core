$(function(){
	var c = new $.cookie();
	var $larr = $(".b-cms-menu-box .arrow_left"),
		$rarr = $(".b-cms-menu-box .arrow_right"),
		$scroll = $(".b-cms-menu-scroll"),
		$ul = $(".b-cms-menu"),
		
		$speed = 'slow';
	
	var scroll_width = $scroll.attr('scrollWidth') - $scroll.width();
	
	if (scroll_width > 0) {
		c.get($ul.width());
		if (c.scroll) {
			$scroll.scrollLeft(c.scroll);
		}
	}
	else {
		$larr.hide();
		$rarr.hide();
	}
	
	$rarr.mouseover(function(e){
		$scroll.animate({
			'scrollLeft': scroll_width + 'px'
		}, $speed);
		return false;	
	});
	
	$rarr.mouseout(function(e){
		$scroll.stop();
		setC($scroll.scrollLeft())
		return false;	
	});
	
	$larr.mouseover(function(e){
		$scroll.animate({
			'scrollLeft': '0px'
		}, $speed);
		return false;		
	})

	$larr.mouseout(function(e){
		$scroll.stop();
		setC($scroll.scrollLeft())
		return false;	
	});
	
	function setC(left) {
		c.scroll = left;
		c.set({ expires: 1, path: '/' });
	}
});