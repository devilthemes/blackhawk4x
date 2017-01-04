$(document).ready(function(){
	
	$('body#product ul.product-images li a').click(function(){
		
		//alert($(this).attr('data-url'));
		$('.product-cover img').attr('src',$(this).attr('data-url'));
	})
	$('.bxslider').bxSlider({
  minSlides: 2,
  maxSlides: 4,
  slideWidth: 170,
  slideMargin: 10,
  pager:false
});
})