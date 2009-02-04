var Slideshow = function() 
{
	/**
	 * private vars
	 */
	var cont = null;
	var imageCont = null;
	var prevArrow = null;
	var nextArrow = null;
	
	var data = null;
	var total = 0;
	
	var autoNext = true;
	var autoNextTimeout = null;
	var currentImage = 0;
	var currentImageObj = null;
	var nextImageObj = null;
	var nextInQueue = null;
	var prevArrowShown = false;
	var nextArrowShown = false;
	var inProgress = false;
	var continuous = true;
	var isInitialized = false;
	
	var effectDuration = 1000;
	var delay = 2000;
	
	/**
	 * public methods
	 */
	return {
		setContainer : function(id)
		{
			cont = $("#" + id);
		},
		setImageContainer : function(id)
		{
			imageCont = $("#" + id);
			imageCont.removeAttr('id');
		},
		setNextArrow : function(id)
		{
			nextArrow = $("#" + id);
			nextArrow.css('opacity', 0);
			nextArrow.hide();
		},
		setPrevArrow : function(id)
		{
			prevArrow = $("#" + id);
			prevArrow.css('opacity', 0);
			prevArrow.hide();
		},
		setData : function(d)
		{
			data = d;
		},
		setContinuous : function(v)
		{
			continuous = v;
		},
		setDelay : function(v)
		{
			v = parseInt(v, 10);
			if (v > 0)
				delay = v;
		},
		setEffectDuration : function(v)
		{
			v = parseInt(v, 10);
			if (v > 0)
				effectDuration = v;
		},
		setAutoNext : function(v)
		{
			autoNext = v;
		},
		process : function()
		{
			if (isInitialized || !prepare())
			{
				return;
			}
			isInitialized = true;
			
			if (total == 1)
			{
				continuous = false;
				autoNext = false;
			}
			
			bindEvents();
			showImage(0);
		}
	};
	
	/**
	 * private methods
	 */
	function prepare()
	{
		if (data)
		{
			// count total images, container width and height
			var i, w = 0, h = 0;
			for (i in data)
			{
				data[i].image.loaded = false;
				data[i].image.cache = null;
				
				w = Math.max(w, data[i].image.width);
				h = Math.max(h, data[i].image.height);
				
				total++;
			}
			cont.width(w);
			cont.height(h);
			
			nextArrow.css({
				'left' : w - nextArrow.width(),
				'top' : (h - nextArrow.height()) / 2
			});
			
			prevArrow.css({
				'left' : 0,
				'top' : (h - nextArrow.height()) / 2
			});
		}
		
		if (total == 0)
		{
			cont.hide();
		}
		else
		{
			cont.show();
		}
		
		// if no iamge container, create one
		if (!imageCont || !imageCont.length)
		{
			imageCont = document.createElement('img');
			imageCont.style.position = 'absolute';
			imageCont.style.top = '0px';
			imageCont.style.left= '0px';
			imageCont = $(imageCont);
			cont.prepend(imageCont);
		}
		
		currentImageObj = imageCont;
		return total;
	}
	
	function showImage(imageNum)
	{
		if (inProgress)
		{
			nextInQueue = imageNum;
			return false;
		}
		var next = data[imageNum];
		if (!next)
			return;
		
		inProgress = true;
		
		if (!next.image.cache)
		{
			cacheImage(imageNum);
		}
		
		nextImageObj = next.image.cache;
		nextImageObj.insertBefore(currentImageObj);
		nextImageObj.css('opacity', 1);
				
		if (next.image.loaded)
		{
			swapImages();
		}
		else
		{
			next.image.swapAfterLoad = true;
		}
				
		currentImage = imageNum;
		checkArrows();
		return false;
	}
	
	function cacheImage(imageNum)
	{
		var img = data[imageNum].image;
		if (!img.cache)
		{
			img.cache = imageCont.clone();
			img.cache.one('load', imageLoaded.prototypeBind(this, img));
			img.cache.attr('src', img.src);
		}
	}
	
	function cacheBounds()
	{
		var n;
		
		if (n = getNextImageNum())
			cacheImage(n);
					
		if (n = getPrevImageNum())
			cacheImage(n);
	}
	
	function bindEvents()
	{
		prevArrow.bind('click', prevArrowClick.prototypeBind(this));
		nextArrow.bind('click', nextArrowClick.prototypeBind(this));
	}
	
	function disableAutoNext()
	{
		if (autoNext)
		{
			autoNext = false;
			clearTimeout(autoNextTimeout);
			autoNextTimeout = null;
		}
	}
	
	function prevArrowClick()
	{
		disableAutoNext();
		prevImage();
		return false;		
	}
	
	function nextArrowClick()
	{
		disableAutoNext();
		nextImage();
		return false;
	}
	
	function prevImage()
	{
		showImage(getPrevImageNum());
	}
	
	function nextImage()
	{
		showImage(getNextImageNum());
	}
	
	function getPrevImageNum()
	{
		var result = null;
		if (currentImage <= 0)
		{
			if (continuous)
				result = total - 1;
		}
		else
		{
			result = currentImage - 1;
		}
		
		return result;
	}
	
	function getNextImageNum()
	{
		var result = null;
		if ((currentImage + 1) >= total)
		{
			if (continuous)
				result = 0;
		}
		else
		{
			result = currentImage + 1;
		}
		
		return result;
	}
	
	function imageLoaded(img)
	{
		img.loaded = true;
		if (img.swapAfterLoad)
		{
			img.swapAfterLoad = false;
			swapImages();
		}
	}
	
	function swapImages()
	{
		currentImageObj.animate({ 
			opacity: 0
		}, effectDuration, null, imagesSwapped.prototypeBind(this));
	}
	
	function imagesSwapped()
	{
		currentImageObj.remove();
		currentImageObj = null;
		currentImageObj = nextImageObj;
		nextImageObj = null;
		
		inProgress = false;
	
		cacheBounds();
		
		triggerNext();
	}
		
	function triggerNext()
	{
		if (autoNext)
		{
			autoNextTimeout = setTimeout(nextImage.prototypeBind(this), delay);
		}
		else if (nextInQueue !== null)
		{
			showImage(nextInQueue);
			nextInQueue = null;
		}
	}
	
	function checkArrows()
	{
		if (continuous)
		{
			if (!prevArrowShown)
			{
				prevArrowShown = true;
				prevArrow.show();
				prevArrow.animate({ 
					opacity: 1
				}, 500);
			}
			if (!nextArrowShown)
			{
				nextArrowShown = true;
				nextArrow.show();
				nextArrow.animate({ 
					opacity: 1
				}, 500);
			}
			return;
		}
		
		if (currentImage > 0)
		{
			if (!prevArrowShown)
			{
				prevArrowShown = true;
				prevArrow.show();
				prevArrow.animate({ 
					opacity: 1
				}, 500);
			}
		}
		else if (prevArrowShown)
		{
			prevArrowShown = false;
			prevArrow.animate({ 
				opacity: 0
			}, 500, null, function() {prevArrow.hide();});
		}
		
		if (currentImage == (total - 1))
		{
			if (nextArrowShown)
			{
				nextArrowShown = false;
				nextArrow.animate({ 
					opacity: 0
				}, 500, null, function() {nextArrow.hide();});
			}
		}
		else if (!nextArrowShown)
		{
			nextArrowShown = true;
			nextArrow.show();
			nextArrow.animate({ 
				opacity: 1
			}, 500);
		}
	}
}