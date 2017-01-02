{foreach $linkBlocks as $linkBlock}
  <h3>{$linkBlock.title}</h3>
  <ul>
    {foreach $linkBlock.links as $link}
      <li>
        <a
          id="{$link.id}-{$linkBlock.id}"
          class="{$link.class}"
          href="{$link.url}"
          title="{$link.description}"
        >
          {$link.title}
        </a>
      </li>
    {/foreach}
  </ul>
{/foreach}
