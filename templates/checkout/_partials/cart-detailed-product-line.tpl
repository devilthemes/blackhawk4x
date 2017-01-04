<span class="product-image td"><img src="{$product.cover.small.url}"></span>
<span class="product-name td"><a href="{$product.url}">{$product.name}</a>
<span class="attributes ">
{foreach from=$product.attributes key="attribute" item="value"}
  <span class="product-attributes">
    <span class="label">{$attribute}:</span>
    <span class="value">{$value}</span>
  </span>
{/foreach}

{if $product.customizations|count}
  {foreach from=$product.customizations item="customization"}
    {foreach from=$customization.fields item="field"}
      <span class="product-line-info">
        <span class="label">{$field.label}:</span>
        <span class="value">
          {if $field.type == 'text'}
            {if $field.id_module}
              {$field.text nofilter}
            {else}
             {$field.text}
            {/if}
          {elseif $field.type == 'image'}
            <img src="{$field.image.small.url}">
          {/if}
        </span>
      </span>
    {/foreach}
  {/foreach}
{/if}

<span class="product-availability">{$product.availability}</span>
</span>

</span>
<span class="product-price td">{$product.price}</span>
{if $product.unit_price_full}
  <small class="sub">{$product.unit_price_full}</small>
{/if}
<span class="td">
<span class="product-quantity">{$product.quantity}</span>
{if $product.down_quantity_url}
  <a href="{$product.down_quantity_url}" class="btn btn-default  js-decrease-product-quantity" data-link-action="update-quantity"><i class="fa fa-minus"></i></a>
{/if}

{if $product.up_quantity_url}
  <a href="{$product.up_quantity_url}" class="btn btn-default  js-increase-product-quantity" data-link-action="update-quantity"><i class="fa fa-plus"></i></a>
{/if}
</span>
<span class="td">
<a
  class="remove-from-cart"
  data-link-action="remove-from-cart"
  data-id-product="{$product.id_product|escape:'javascript'}"
  data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}"
  href="{$product.remove_from_cart_url}"
  rel="nofollow"
 >
  <i class="fa fa-trash"></i>
  <span class="sr-only">
  {l s='Remove' d='Shop.Theme.Actions'}
  </span>
</a>
</span>
<span class="td">
{hook h='displayCartExtraProductActions' product=$product}

<span class="product-price">{$product.total}</span>
</span>