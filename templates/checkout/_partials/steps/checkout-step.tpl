<section id="{$identifier}"
  class="{[
    'checkout-step' => true,
    '-current' => $step_is_current,
    '-reachable' => $step_is_reachable,
    '-complete' => $step_is_complete,
    'js-current-step' => $step_is_current
  ]|classnames}"
>
  <h3><span class="step-number">{$position}</span>{$title}</h3>
  <div class="content">
    {block name='step_content'}DUMMY STEP CONTENT{/block}
  </div>
</section>
