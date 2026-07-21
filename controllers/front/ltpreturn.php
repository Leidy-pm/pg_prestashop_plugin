<?php
/**
 * LTP return controller.
 *
 * Handles the redirect from the LinkToPay gateway after payment (success, failure, pending, review).
 * It looks up the PS order by cart ID (the webhook may have already updated the state) and
 * redirects to the standard order-confirmation page.
 * If the order is not found yet (webhook processing lag), it shows a brief pending page that
 * auto-refreshes every few seconds.
 */
class PG_Prestashop_PluginLtpreturnModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        if (!$this->module->active) {
            Tools::redirect($this->context->link->getPageLink('cart', true));
        }
    }

    public function initContent()
    {
        parent::initContent();

        $id_cart = (int) Tools::getValue('id_cart');
        $key     = (string) Tools::getValue('key');

        if (!$id_cart || !$key) {
            Tools::redirect($this->context->link->getPageLink('cart', true));
            return;
        }

        $id_order = (int) Order::getIdByCartId($id_cart);

        if ($id_order > 0) {
            Tools::redirect(
                $this->context->link->getPageLink('order-confirmation', true, null, [
                    'id_cart'   => $id_cart,
                    'id_module' => (int) $this->module->id,
                    'id_order'  => $id_order,
                    'key'       => $key,
                ])
            );
            return;
        }

        // Order not found yet (webhook may still be processing).
        // Show a pending page that refreshes automatically.
        $this->context->smarty->assign([
            'ltp_pending_refresh_url' => $this->context->link->getModuleLink(
                $this->module->name,
                'ltpreturn',
                ['id_cart' => $id_cart, 'key' => $key],
                true
            ),
        ]);
        $this->setTemplate('module:pg_prestashop_plugin/views/templates/front/ltp_pending.tpl');
    }
}
