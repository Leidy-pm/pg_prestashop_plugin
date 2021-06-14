# Paymentez Payment Gateway Plugin for Prestashop

## 1.- Prerequisites
### 1.1.- XAMPP, LAMPP, MAMPP, Bitnami or any PHP development environment
- XAMPP: https://www.apachefriends.org/download.html
- LAMPP: https://www.apachefriends.org/download.html
- MAMPP: https://www.mamp.info/en/mac/
- Bitnami: https://bitnami.com/stack/prestashop
### 1.2.- Prestashop
If you already install the Bitnami option this steps can be omitted.

Prestashop is an e-commerce solution, it's developed on PHP. Now the last stable version is the 1.7.X.
- Download: https://www.prestashop.com/en/download
- Install Guide: https://www.prestashop.com/en/blog/how-to-install-prestashop

## 2.- Git Repository
You can download the current stable release from: https://github.com/paymentez/pg_prestashop_plugin/releases

## 3.- Plugin Installation on Prestashop
1. First, we need to downoload the current stable release of Paymentez Prestashop plugin from the previus step.
2. We need to login to our Prestashop admin page.
3. Now we click on **Improve -> Modules -> Module Manager**
4. In the Module manager we click on the **Upload a mudule** button
5. We click on **select file** or we can **Drop** the Paymentez Prestashop plugin folder on .zip or .rar format.
6. We will wait until the **Installing module** screen changes to **Module installed!**.
7. Now we can click on **Configure** button displayed on the screen or in the **Configure** button displayed on the **Payment** section on the **Module manager**.
8. Inside of the **Payment Gateway Configurations** we need to configure or CLIENT/SERVER credentials provided by **Paymentez**, we can select the **Checkout Language** that will be displayed to the user, also we need to select an **Environment**, by default STG(Staging) is selected.
9. Congrats! Now we have the Paymentez Prestashop plugin correctly configured.

## 4.- Considerations and Comments
### 4.1.- Refunds
- The **1.0.0** plugin version does not support the **Partial Refunds** by Prestashop. However the plugin supports **Standard Refunds** by Prestashop. 
- The **Standard Refund** can be interpreted as a partial refund on Paymentez side, the success of the operation depends on the configured payment network accepting partial refunds.
### 4.2.- Webhook
The Paymentez Prestashop plugin has an internal webhook in order to keep updated the transactions statuses between Prestashop and Paymentez. You need to follow the next steps to configure the webhook:
  1. Login into the Prestashop Back-office.
  2. Navigate to Advance Parameters -> Web Services menu options to open the Web Services page.
  3. It will redirect to the Web Services page having the listing of available Webservices, and the configuration form to configure the service.
  4. We need to enable the field called **Enable Prestashop webservice**.
  5. Click on **Save** button.
  6. Click on the **Add new web service key** button to add new web service key to access only to the certain resources of the Prestashop store.
  7. We need to configure the **Key**, this is a unique key. You can enter it manually or click on the Generate button to generate a random key for the web service.
  8. We also configure the **Key Description**, you can provide the description regarding the key for better understanding.
  9. We will set the **Status** on Enable to provide a grant to access the data using the key.
  10. Finally, we need to configure the **Permission** field to provide the permission to access the data using the certain key. Here we need to search the resourde called **paymentezwebhook** and select the **Add (POST)** checkbox. 
  11. The webhook its located on **https://{mystoreurl}/prestashop/api/paymentezwebhook?ws_key=KEY_GENERATED_ON_STEP_6**. 
  12. You need to give this URL to your Paymentez agent.