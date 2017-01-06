{if $field.type === 'select'}

  <div  class='form-group select-field {if $field.required}required{/if}'>
    <label>{$field.label}</label>
    <select class="form-control" name="{$field.name}" {if $field.required}required{/if}>
      <option value disabled selected>{l s='-- please choose --' d='Shop.Forms.Labels'}</option>
      {foreach from=$field.availableValues item="label" key="value"}
        <option value="{$value}" {if $value eq $field.value}selected{/if}>{$label}</option>
      {/foreach}
    </select>
  </div>

{elseif $field.type === 'countrySelect'}

   <div  class='form-group select-field {if $field.required}required{/if}'>
    <label>{$field.label}</label>
    <select class="js-country" name="{$field.name}" {if $field.required}required{/if}>
      <option value disabled selected>{l s='-- please choose --' d='Shop.Forms.Labels'}</option>
      {foreach from=$field.availableValues item="label" key="value"}
        <option value="{$value}" {if $value eq $field.value} selected {/if}>{$label}</option>
      {/foreach}
    </select>
  </div>

{else if $field.type === 'radio-buttons'}

  <div class='form-group radio-field {if $field.required}required{/if}'>
   <span class="radio-inline col-sm-3">{$field.label}</span>
    {foreach from=$field.availableValues item="label" key="value"}
      <label class="radio-inline">
        <input class="form-control"
          name="{$field.name}"
          type="radio"
          value="{$value}"
          {if $field.required}required{/if}
          {if $value eq $field.value}checked{/if}
        >
        {$label}
      </label>
    {/foreach}
  </div>

{elseif $field.type === 'checkbox'}

 <div class='form-group checkbox-field {if $field.required}required{/if}'>
    <label class="checkbox-inline">
	<input class="form-control"
      name="{$field.name}"
      type="checkbox"
      value="1"
      {if $field.required}required{/if}
      {if $field.value}checked{/if}
    >
    <span>{$field.label}</span>
	</label>
  </div>

{elseif $field.type === 'password'}

 <div class='form-group  {if $field.required}class="required"{/if}>
    <span class="radio-inline col-sm-3">{$field.label}</span>
    
	<label class="radio-inline">
	<input class="form-control"
      name="{$field.name}"
      type="password"
      value=""
      pattern=".{literal}{{/literal}5,{literal}}{/literal}"
      {if $field.required}required{/if}
    >
	</label>
  </div>

{elseif $field.type === 'hidden'}

  <input type="hidden" name="{$field.name}" value="{$field.value}">

{else}

  <div  class='form-group {if $field.required}class="required"{/if}>
    <label>{$field.label}</label>
    <input class="form-control" name="{$field.name}" type="{$field.type}" value="{$field.value}" {if $field.required}required{/if}>
  </div>

{/if}

{include file='_partials/form-errors.tpl' errors=$field.errors}
