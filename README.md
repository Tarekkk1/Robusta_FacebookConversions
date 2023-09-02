 # Robusta Facebook Conversions Magento 2 Extension

This extension integrates Facebook's Standard Events with Magento 2 using the Conversions API. 

## Installation

1. Install the extension via composer (Under preparing)

2. Enable the extension in Magento Admin:

```
System > Configuration > Robusta Extensions > Facebook Conversions
```

3. Enter your Facebook Pixel ID and Access Token in the settings.

## Usage

The extension will automatically send the following events to Facebook:

* AddToCart
* AddToWishlist
* Purchase
* CompleteRegistration
* Search

You can also send custom events to Facebook using the `Robusta\FacebookConversions\Services\CAPI` class.


