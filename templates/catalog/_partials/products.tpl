<div id="js-product-list">
  <div>
    {foreach from=$listing.products item="product"}
      {block name='product_miniature'}
        {include file='catalog/_partials/miniatures/product.tpl' product=$product}
      {/block}
    {/foreach}
  </div>

  {block name='pagination'}
    {include file='_partials/pagination.tpl' pagination=$listing.pagination}
  {/block}

  <div class="gotop"><a href="#header"><i class="fa fa-chevron-up"></i><span class="sr-only">{l s='Back to top' d='Shop.Actions'}</span></a></div>
</div>
