{if $pg_payment_approved}
<h3>{l s='Payment approved!' mod='pg_prestashop_plugin'}</h3>
<p>
    {l s='Your payment was approved by %s.' sprintf=[$module_gtw] mod='pg_prestashop_plugin'}<br/>
    {l s='Your order is being confirmed — you will receive an email once it is processed.' mod='pg_prestashop_plugin'}
</p>
{else}
<h3>{l s='Order received' mod='pg_prestashop_plugin'}</h3>
<p>
    {l s='Your order has been received and payment is being verified.' mod='pg_prestashop_plugin'}<br/>
    {l s='You will receive a confirmation email once your payment is processed.' mod='pg_prestashop_plugin'}
</p>
{/if}
{if $payment_id}
<p>{l s='Payment reference: %s' sprintf=[$payment_id] mod='pg_prestashop_plugin'}</p>
{/if}
