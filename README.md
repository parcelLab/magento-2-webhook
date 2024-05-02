# parcelLab Magento 2 Webhook Extension

_Please note:_ This extension is a fork of the excellent [Magento 2 Webhook by Mageplaza](https://www.mageplaza.com/magento-2-webhook/) published under MIT license [on Github](https://github.com/mageplaza/magento-2-webhook) (see fork source). This fork simply eases to simplify setup for integration with parcelLab by providing appropriate defaults.

> 🚨 **Compatibility notice:**
>
> For Magento < `2.6` use plugin version `2.4.21`
> 
> For Magento >= `2.6` use plugin version `2.4.23` or later 

## Install & Setup Guide

> **Important:** If you have [Mageplaza Webhook](https://www.mageplaza.com/magento-2-webhook/) already installed, **DO NOT INSTALL** this extension and follow our setup guide instead: [Setup Guide with Mageplaza Webhook](https://github.com/parcelLab/magento-2-webhook/blob/2.4/setup-guide-with-mageplaza.md)

Run the following command in Magento 2 root folder:

```
composer require parcellab/magento-2-webhook
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

After successful installation and deploy log in to your Admin Backend of Magento.

Navigate to `System > parcelLab Webhook > Configuration`:

<img width="250" alt="image" src="https://user-images.githubusercontent.com/625618/201497094-81a2a030-811e-430e-9a3b-586a0bc997e5.png">

Open the section `General`:

<img width="350" alt="image" src="https://github.com/parcelLab/magento-2-webhook/assets/625618/4e7784cb-e705-44f2-bca9-c7f68e542fc6">

In this screen, you will have to enter your parcelLab API credentials. Generate a new **parcelLab API Token** with `write` access in the parcelLab Portal and enter this token together with your **parcelLab User ID**:

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201497110-0d365e8c-01c5-4544-b419-bb8ae6d7cf17.png">

The extension might be disabled by default system setting. Make sure, setting `Enable` is set to `Yes`. You might have to remove the flag to use the system setting 1️⃣ and then switch the setting 2️⃣.

<img width="350" alt="image" src="https://github.com/parcelLab/magento-2-webhook/assets/625618/753927a6-fa38-4952-8621-eb621b4cd7e8">

This is the correct setting:

<img width="350" alt="image" src="https://github.com/parcelLab/magento-2-webhook/assets/625618/da1fd669-7a8b-4b59-9cdc-d69f4c07fbd8">

Navigate to `System > parcelLab Webhook > Manage Events`:

<img width="250" alt="image" src="https://user-images.githubusercontent.com/625618/201497129-c83ea938-a738-48fa-ad12-f5b5b853e706.png">

You'll find the 2 required events already preconfigured:

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201497175-accef56a-3675-43a4-9682-ac15fff4ac9f.png">

If you have entered your parcelLab credentials earlier, you are okay to activate the events:

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201497313-a2465dbf-c138-4877-8713-6e6aa2b846ba.png">

🎉 **Your integration is done!** 🎉

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201497290-46d41236-0493-45f8-83d3-43dcbfdbec95.png">

## FAQs: 

#### Q: Can I add more events?
A: Due to the tailored use of this extension, the number of events is limited. For more flexible webhooks please use the fork source [Magento 2 Webhook by Mageplaza](https://www.mageplaza.com/magento-2-webhook/).

#### Q: Error `Mageplaza_Core has been already defined`
A: Find the solution [here](https://github.com/mageplaza/module-core/issues/3)

#### Q: My site is down, what should I do?
A: Please follow this guide: https://www.mageplaza.com/blog/magento-site-down.html

#### Q: Where do I find transfer logs?

Navigate to `System > parcelLab Webhook > Transfer Logs`:

<img width="250" alt="image" src="https://user-images.githubusercontent.com/625618/201497376-016e840b-2885-4029-8143-e1b7da291ba9.png">

You'll find a list of recent transfers and can view details like so:

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201497417-1bdf6c6a-71c7-4d3f-ac58-850dea3212de.png">

Please share both the `Response` as well as the `Request Body` when requesting support:

<img width="400" alt="image" src="https://user-images.githubusercontent.com/625618/201497496-22f90aba-28a2-4b1b-93d0-2d8f1c895d8a.png">

#### Q: I am unsure whether I have "Magento 2 Webhook" by Mageplaza installed, how can I check?

Run `php bin/magento module:status` and check whether `Mageplaza_Webhook` is listed. If it is, please don't install this extension but rather follow our [Setup Guide with Mageplaza Webhook](https://github.com/parcelLab/magento-2-webhook/blob/2.4/setup-guide-with-mageplaza.md).

#### Q: I already have "Magento 2 Webhook" by Mageplaza installed, how can I integrate with parcelLab?

The easiest way is to follow our setup guide for this extension, which will take you less than 15 minutes to complete:

[Setup Guide with Mageplaza Webhook](https://github.com/parcelLab/magento-2-webhook/blob/2.4/setup-guide-with-mageplaza.md)
## Credit

This extension is a fork of the excellent [Magento 2 Webhook by Mageplaza](https://www.mageplaza.com/magento-2-webhook/) published under MIT license [on Github](https://github.com/mageplaza/magento-2-webhook) (see fork source).

It also uses [mageplaza/module-core](https://packagist.org/packages/mageplaza/module-core) and [liquid/liquid](https://packagist.org/packages/liquid/liquid)
