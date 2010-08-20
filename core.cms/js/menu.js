$(function(){
	var c = new $.cookie();
	var $larr = $(".b-cms-menu-box .arrow_left"),
		$rarr = $(".b-cms-menu-box .arrow_right"),
		$scroll = $(".b-cms-menu-scroll"),
		
		$speed = 'slow';
	
	c.get();
	if (c.scroll) {
		$scroll.scrollLeft(c.scroll);
	}
	
	$rarr.mouseover(function(e){
		var scroll_width = $scroll.attr('scrollWidth') - $scroll.width();
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