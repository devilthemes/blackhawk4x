<div class="images-container">
  {*
  {block name='product_cover'}
    <div class="product-cover">
      <a href="{$product.cover.bySize.large_default.url}" data-featherlight="image"><img src="{$product.cover.bySize.medium_default.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" width="{$product.cover.bySize.medium_default.width}" itemprop="image"></a>
    </div>
  {/block}
*}
  {block name='product_images'}
 
    <ul class="product_images bxslider">
      {foreach from=$product.images item=image}
        <li><a href="{$image.large.url}" data-featherlight="image"><img src="{$image.large.url}" alt="{$image.legend}" title="{$image.legend}"  itemprop="image" /></a></li>
      {/foreach}
    </ul>
	
	
	<div id="bx-pager">
	 {foreach from=$product.images key=k item=image}
        <a data-slide-index="{$k}" href=""><img src="{$image.medium.url}" alt="{$image.legend}" title="{$image.legend}" width="100" itemprop="image"></a>
     {/foreach}
	
	
	</div>
	
	
  {/block}
</div>
