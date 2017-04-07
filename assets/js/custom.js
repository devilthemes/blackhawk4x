$(document).ready(function(){
	/*
	$('body#product ul.product-images li a').click(function(){
		
		//alert($(this).attr('data-url'));
		$('.product-cover img').attr('src',$(this).attr('data-url'));
		$('.product-cover a').attr('href',$(this).attr('data-url'));
	})
	*/
	
	
	slider =$('.bxslider').bxSlider({
 pagerCustom: '#bx-pager'
});



$("body#product .product-actions ul.product-variants li ul.color li input").on( "click", function() {
	slider.reloadSlider();
  console.log(true);
});

})

