$(document).ready(function(){
	function createBxSlider(){
		let ele= $('.bxslider').bxSlider({
			pagerCustom: '#bx-pager'
		});
		return ele;
	}
	var slider= createBxSlider();


	$("#product").on("click",".product-actions ul.product-variants li ul.color li input", function() {
		//Pre destroying slider before prestashop changes it's HTML DOM
		slider.destroySlider();
		
		setTimeout(function(){
			createBxSlider();
		},1000); //configurable timeout
  		
	});

})

