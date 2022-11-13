# Setup guide 

If you have [mageplaza/magento-2-webhook](https://github.com/mageplaza/magento-2-webhook) already installed, you can integrate parcelLab easily through this extension as well.

Navigate to `System > Webhook > Configuration`:

<img width="250" alt="image" src="https://user-images.githubusercontent.com/625618/201502764-ba53eb2a-5656-41ea-877b-12f57d753631.png">

Confirm that the extension is enabled:

<img width="450" alt="image" src="https://user-images.githubusercontent.com/625618/201502784-84a863be-4565-4998-808a-f9e71e9b1000.png">

Navigate to `System > Webhook > Manage Hooks`:

<img width="250" alt="image" src="https://user-images.githubusercontent.com/625618/201502805-c3193f76-d3fe-47bd-a1b4-cc543d2db882.png">

In the top right corner, click on the down arrow in button `Add New` top open the dropdown, from the dropdown select the first entry `Order`:

<img width="180" alt="image" src="https://user-images.githubusercontent.com/625618/201502885-3b57899f-5931-4df9-b261-e788a9b128b9.png">

In the following screen fill all fields:

- `Name`: Pick a descriptive name for this webhook, e.g. `parcelLab Transfer Webhook on Event order`
- `Status`: Pick `Enable`
- `Order Status`: Select all status
- `Store Views`: Pick `All Store Views` or any other selection if you only wish to integrate specific shops
- `Priority`: Enter `10`

<img width="650" alt="image" src="https://user-images.githubusercontent.com/625618/201503026-74463389-8ed2-4959-b058-f235e21ca6ee.png">

Navigate to the next screen by clicking on `Actions` in the navigation box on the left:

<img width="210" alt="image" src="https://user-images.githubusercontent.com/625618/201503067-fcd2b66c-c4fa-47f1-8044-a7946a2908c7.png">

Now enter following values:

- `Payload URL`: Enter `https://api.parcellab.com/magento2/order`
- `Method`: Select `POST` from the dropdown
- `Authentication`: Select `Basic` from the dropdown
- `Username`: Enter your **parcelLab User ID**, typically a 7 digit numerical code
- `Password`: Enter your **parcelLab API Token**, it requires write scope
- `Headers`: No changes required
- `Content Type`: Select `application/json` from the dropdown
- `Body`: Copy and paste the body from [order.liquid](https://raw.githubusercontent.com/parcelLab/magento-2-webhook/2.4/order.liquid)

Afterwards, click `Save` in the top right corner.

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201503372-188f8dee-9f7f-4c5e-a9a7-03a49c801f0d.png">

The first webhook is now set up and active and will transfer order data to parcelLab:

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201503433-9b317312-164a-4747-9b5d-1f80fc5aea68.png">

As the webhook is already active we can confirm correct data transfer after an order has been placed or updated:

<img width="600" alt="image" src="https://user-images.githubusercontent.com/625618/201503476-c7cbb8b2-97f7-4638-92e5-7c0674d9ea6c.png">

Now, the process needs to be repeated a second time for another webhook. The process is identical, but 4 fields require different data entry. All data is shown below:

Field | Webhook "Order" | Webhook "New Shipment"
:-- | :-- | :--
Create by clicking... | `Add New > Order` | `Add New > New Shipment`
`Name` | parcelLab Transfer Webhook<br />on Event **order** | parcelLab Transfer Webhook<br />on Event **new_shipment**
`Status` | Enable | Enable
`Order Status` | All Status | **n/a**
`Store Views` | All Store Views | All Store Views
`Priority` | 10 | 10
`Payload URL` | `https://api.parcellab.com/magento2/order`<br />**important:** `.../order` | `https://api.parcellab.com/magento2/new_shipment`<br />**important:** `.../new_shipment`
`Method` | POST | POST
`Authentication` | Basic | Basic
`Username` | parcelLab User ID | parcelLab User ID
`Password` | parcelLab API Token | parcelLab API Token
`Headers` | - | -
`Content Type` | application/json | application/json
`Body` | [order.liquid](https://raw.githubusercontent.com/parcelLab/magento-2-webhook/2.4/order.liquid) | [new_shipment.liquid](https://raw.githubusercontent.com/parcelLab/magento-2-webhook/2.4/new_shipment.liquid)
