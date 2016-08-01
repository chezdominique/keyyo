{capture name=path}{l s='keyyo' mod='keyyo'}{/capture}

<h1 class="page-heading bottom-indent">
    {l s='Keyyo' mod='keyyo'}
</h1>
<br>
<p>{$smarty.server.HTTP_HOST}{$smarty.server.REQUEST_URI}</p>

{if $confirmation}
    <p class="alert alert-success">{$confirmation}</p>
{/if}
{include file="$tpl_dir./errors.tpl"}

