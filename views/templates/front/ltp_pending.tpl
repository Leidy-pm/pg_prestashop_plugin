{extends "$layout"}
{block name="content"}
<div class="container text-center" style="padding: 60px 20px;">
    <h3>{l s='Processing your payment…' mod='pg_prestashop_plugin'}</h3>
    <p>{l s='Please wait while we confirm your payment. This page will refresh automatically.' mod='pg_prestashop_plugin'}</p>
    <div style="margin: 20px auto; width: 40px; height: 40px; border: 4px solid #ccc; border-top-color: #3498db; border-radius: 50%; animation: pg-spin 0.8s linear infinite;"></div>
</div>
<style>
    @keyframes pg-spin { to { transform: rotate(360deg); } }
</style>
<script>
    setTimeout(function () {
        window.location.href = "{$ltp_pending_refresh_url|escape:'javascript':'UTF-8'}";
    }, 5000);
</script>
{/block}
