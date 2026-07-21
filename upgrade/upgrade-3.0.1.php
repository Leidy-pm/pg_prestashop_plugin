<?php

function upgrade_module_3_0_1($module)
{
    if (!($module instanceof PG_Prestashop_Plugin)) {
        return false;
    }

    return $module->registerHook('addWebserviceResources')
        && $module->registerHook('actionOrderSlipAdd')
        && $module->registerHook('displayBackOfficeHeader')
        && $module->registerHook('paymentReturn')
        && $module->syncWebhookWebservice();
}
