# Paymentez Payment Gateway Plugin for Prestashop

## 1. Prerequisites
### 1.1. XAMPP, LAMPP, MAMPP, Bitnami or any PHP development environment
- XAMPP: https://www.apachefriends.org/download.html
- LAMPP: https://www.apachefriends.org/download.html
- MAMPP: https://www.mamp.info/en/mac/
- Bitnami: https://bitnami.com/stack/prestashop
### 1.2. Prestashop
Warning, if you already install the Bitnami option this step can be omitted.

Prestashop is an e-commerce solution, it's developed on PHP.
This plugin is compatible with **PrestaShop 8.0.0 and newer** (8.x and 9.x).
- Download: https://www.prestashop.com/en/download
- Install Guide: https://www.prestashop.com/en/blog/how-to-install-prestashop

## 2. Git Repository
You can download the current stable release from: https://github.com/paymentez/pg_prestashop_plugin/releases

## 3. Plugin Installation on Prestashop
1. First, we need to download the current stable release of Paymentez Prestashop plugin from the previous step.
2. We need to unzip the file to get the pg_prestashop_plugin-3.0.0 folder.
3. Now you rename the folder from **pg_prestashop_plugin-3.0.0** to **pg_prestashop_plugin**.
4. Compress on zip format the folder to get a file called **pg_prestashop_plugin.zip**.
5. We need to log in to our Prestashop admin page.
6. Now we click on **Improve -> Modules -> Module Manager**
7. In the Module manager we click on the **Upload a mudule** button
8. We click on **select file**, or we can **Drop** the Paymentez Prestashop plugin folder on .zip or .rar format.
9. We will wait until the **Installing module** screen changes to **Module installed!**.
10. Now we can click on **Configure** button displayed on the screen or in the **Configure** button displayed on the **Payment** section on the **Module manager**.
11. Inside the **Payment Gateway Configurations** we need to configure the **Server credentials** provided by **Paymentez**, we can select the **Checkout Language** that will be displayed to the user, also we need to select an **Environment**, by default STG(Staging) is selected.
12. Congrats! Now we have the Paymentez Prestashop plugin correctly configured.

## 4. Considerations and Comments
### 4.1. Refunds
- The plugin supports **Partial Refunds** and **Standard Refunds** only for **card payments**. LinkToPay does not support refunds.
- The **Standard Refund** now uses the full paid order amount when **Credit slip** is selected in PrestaShop. Partial refunds still use the selected products and shipping amount. A success refund operation depends on the configured payment network accepting refunds.
### 4.2. Webhook
The Paymentez Prestashop plugin has an internal webhook in order to keep updated the transactions statuses between Prestashop and Paymentez. You need to follow the next steps to configure the webhook:
  1. Login into the Prestashop Back-office.
  2. Navigate to Advance Parameters -> Web Services menu options to open the Web Services page.
  3. It will redirect to the Web Services page having the listing of available Webservices, and the configuration form to configure the service.
  4. We need to enable the field called **Enable Prestashop webservice**.
  5. Click on **Save** button.
  6. The module creates the **paymentezwebhook** webservice key automatically during installation.
  7. Edit that key in **Advanced Parameters -> Web Services** and manually enable **POST** permission for **paymentezwebhook**.
  8. You can review/copy the generated key in that same screen.
  9. The webhook is located on **https://{mystoreurl}/api/paymentezwebhook?ws_key=THE_KEY_CREATED_BY_THE_MODULE**.
  10. You need to give this URL to your Paymentez agent.
