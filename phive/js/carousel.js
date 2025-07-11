/**
* Carousel
* arg0 = div(jQuery object), div containing ul
* arg1(optional) = pause(int), time for element to pause before showing next. Default 3000.
* arg2(optional) = speed(int), animation speed. Default 500.
* arg3(optional) = esing(string), type of easing. Default "wipe".
* arg4(optional) = iterations(int)or(string), number of iterations. Default "infinite".
* arg5(optional) = fade(bool)or(string), fade out/in instead of rotate, mixed == fade + rotate. Default false.  
* ex. carousel.init($("#containerDiv"), 2000, 300, "easeOutBounce", "infinite", true);
*/

function Carousel(container){
	this.container 		= container;
	this.pause 			= arguments[1] || 3000;
	this.speed 			= arguments[2] || 500;
	this.easing 		= arguments[3] || "jswing";
	this.iterations 	= arguments[4] || 0;
	this.ul 			= container.find("ul");
	this.width 			= this.ul.find("li").outerWidth(true);
	this.left_value 	= this.width * (-1);
	this.onRotate		= function(){};
	
	this.setPause 		= function(pause){ this.pause = pause || 3000; }
	this.setSpeed 		= function(speed){ this.speed = speed || 500; }
	this.setEasing 		= function(easing){ this.easing = easing || "swing"; }
	this.setIterations 	= function(iterations){ this.iterations = iterations || 0; }
	this.setFade 		= function(fade){ this.fade = fade || false; }
	
	this.rotate_me		= true;
	
	this.tOutId			= 0;
	
	this.goTo = function(n){
		this.rotate_me = false;
		window.clearTimeout(this.tOutId);
		var poffs 	= $("#fullFlashBox").offset();
		var px 		= poffs.left;
		var coffs	= $("#banner-"+n).offset();
		var cx		= coffs.left;
		var spos	= this.ul.position();
		var dx 		= spos.left - (cx - 0);
		//alert(px+' '+cx+' '+dx);
		this.ul.animate({left: dx});
	}
	
	Carousel.prototype.rotate = function(){
		var i 		= 0;
		var that 	= this;
		shift();
		function shift(){
			if (that.rotate_me) {
				i++;
				
				if (i == that.iterations && that.iterations != 0) 
					return false;
				
				var left_indent = -that.width;
				
				if (that.fade) {
					/* not tested
					that.ul.fadeIn(that.speed, function(){
						that.ul.delay(that.pause).fadeOut(that.speed, function(){
							that.ul.css("left", 0);
							that.ul.find("li:last").after(that.ul.find("li:first"));
							that.ul.show(0, function(){
								shift();
							})
						});
					});
					*/				
				} else { 
				
					that.tOutId = window.setTimeout(function(){
						that.ul.animate(
							{left: left_indent}, 
							that.speed, 
							that.easing, 
							function(){
								that.ul.find("li").last().after(that.ul.find("li:first"));
								that.ul.css({
									'left': 0
								});
								that.onRotate.call();
								shift();});
					}, that.pause);
				
					
				}
			}
		}
	}
}
