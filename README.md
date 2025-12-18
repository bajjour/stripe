# Stripe Checkout Sessions Gateway for Laravel

This package provides a simple and easy-to-use interface to interact with 
**Stripe** in Laravel applications. It includes methods for creating checkout session and 
query checkout session status.

---

## Installation

You can install the package via Composer:

```bash
composer require bajjour/stripe
```

## Configuration

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --provider="Stripe\StripeServiceProvider" --tag="stripe-config"
```

Update your .env file with your Stripe API credentials:

```bash
STRIPE_API_KEY="your-secret-key"
STRIPE_3D_ENABLED=true #true or false
```

## Usage

`Initialize the Service`

You can initialize the Stripe service in your controller:

```bash
use Stripe\Services\StripeService;


public function __construct(StripeService $stripe)
{
    $this->stripe = $stripe;
}
```

`Create a Checkout Session`

Create a new Checkout Session to generate payment link.

```php
$response = $this->stripe->create_checkout_session([
            'currency' => 'paying-currency',
            'amount' => 'unit-amount-in-cents-to-be-charged',//for 1 dollar set to 100
            'product_name' => 'your product name',
            'success_url' => 'https://yourdomain.com/{success-route}',
            'ref_id' => 'your local reference id', //optional
            'quantity' => 'quantity', //optional, total price will be amount * quantity
            'product_description' => 'your-product-description', //optional
            'cancel_url' => 'https://yourdomain.com/{cancel-route}', //optional
        ]);
```

Response

detailed array from stripe returned, the main values we may use is
```json
{
  "id":"checkout-session-id",
  "object": "checkout.session",
  "amount_total": "total-amount-to-pay",
  "currency": "pay-currency",
  "cancel_url": "cancel-url",
  "livemode": "false or true",
  "metadata": "sent reference id",
  "mode": "payment",
  "payment_method_options": {
    "card": {
      "request_three_d_secure": "challenge, any, or automatic"
    }
  },
  "payment_status": "unpaid",
  "status": "open",
  "success_url": "succcess-url",
  "ui_mode": "hosted",
  "url": "paying link"
}
```

`Get Checkout Session Status`

Retrieve details of a specific checkout session.

to ensure payment **status use status = complete && payment_status = paid**.

```php
$this->stripe->get_checkout_session_status(session_id: 'your-checkout-session-id')
```

Response

same response of create checkout session returned but with updated status and payment status.
```json
{
  "id":"checkout-session-id",
  "object": "checkout.session",
  "amount_total": "total-amount-to-pay",
  "currency": "pay-currency",
  "cancel_url": "cancel-url",
  "livemode": "false or true",
  "metadata": "sent reference id",
  "mode": "payment",
  "payment_method_options": {
    "card": {
      "request_three_d_secure": "challenge, any, or automatic"
    }
  },
  "payment_status": "paid",
  "status": "complete",
  "success_url": "succcess-url",
  "ui_mode": "hosted",
  "url": null
}
```

## Subscription Functions

`Create subscription with specified interval`
```php
$response = $this->stripe->create_subscription([
    'currency' => 'pay-currency',
    'amount' => 'total-amount-to-pay',
    'product_name' => 'your product name',
    'success_url' => 'https://yourdomain.com/{success-route}',
    'product_description' => 'your-product-description', //optional
    'interval' => 'month', //day, week, month, or year
    'interval_count' => '1', //each one month
]);
```
Response

detailed array from stripe returned, the main values we may use is
```json
{
  "id":"checkout-session-id",
  "object": "checkout.session",
  "amount_subtotal": "sub-total-amount",
  "amount_total": "total-amount-to-pay",
  "currency": "pay-currency",
  "cancel_url": "cancel-url",
  "livemode": "false or true",
  "metadata": "sent reference id",
  "mode": "subscription",
  "payment_method_collection": "always",
  "payment_method_options": {
    "card": {
      "request_three_d_secure": "challenge, any, or automatic"
    }
  },
  "payment_status": "unpaid",
  "status": "open",
  "success_url": "succcess-url",
  "ui_mode": "hosted",
  "url": "paying link"
}
```


`Get Subscription Checkout Session`

you will use `get_checkout_session_status($session_id)` function to get status of checkout session and also get `$subscription_id` to followup subscription next payments

`Get Subscription Status`

you will use `get_subscription_status($subscription_id)` to get status of subscription, to get more info about how to handle subscriptions and created invoices in stripe you may go to stripe official documentation.

## Checkout Session in Setup mode
to create checkout session with Setup Mode, 
you will use `create_setup_intent()` to generate link that allows saving customer payment info permanently in stripe and give you the ability to charge user when needed like the following scenario.

```php
//generate stripe link that allows user to securely save their payment info.
$response = $this->stripe->create_setup_intent([
    'success_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel', //optional
]);
// we need "id", "setup_intent", "url" parameters from response to the next steps.

//to get customer info (customer_id, payment_method_id), and status of session.
$this->stripe->get_setup_intent_status($setup_intent_id);

//create invoice generating invoice in stripe that can be charged directly from your side, or sent to user to pay it.
$response = $this->stripe->create_invoice([
    'customer_id' => $customer_id,
    'amount' => 'total-amount',
    'currency' => 'pay-currency',
    'description' => 'your-product-description',
]);
// essentials returned parameters (id, hosted_invoice_url, invoice_pdf, status).

// to charge invoice automatically
$response = $this->stripe->charge_invoice($invoice_id, $payment_method_id);
// you can depend on "status" parameter to know if invoice has been paid.

// you can check invoice status using the following function:
$response = $this->stripe->get_invoice_status($invoice_id);
// essentials returned parameters (id, hosted_invoice_url, invoice_pdf, status).
```

## Refund
to handle stripe refunds functions you need to call the following functions.

```php

//create refund
$response = $this->stripe->create_refund([
    'payment_intent' => 'pi_xxxxxxxxxx',
    'reason' => 'requested_by_customer', //optional one of the following (duplicate, fraudulent, requested_by_customer)
    'amount' => '10', //optional, send only if you need to make a partial refund
]);

//the following response is expected
{
    "id": "refund_id",
    "object": "refund",
    "amount": refund_amount,
    "currency": "usd",
    "destination_details": {
        "card": {
            "reference_status": "pending",
            "reference_type": "acquirer_reference_number",
            "type": "refund"
        },
        "type": "card"
    },
    "metadata": {},
    "payment_intent": "pi_xxxxxxxxxx",
    "reason": null,
    "receipt_number": null,
    "source_transfer_reversal": null,
    "status": "succeeded",
    "transfer_reversal": null
}

//get refund
$response = $this->stripe->get_refund('refund_id');
//return the same of create refund response, but with updated status

//cancel refund in rare cases
$response = $this->stripe->cancel_refund('refund_id');

```

## API Documentation

For more details about the Stripe API, refer to the official documentation:

[Stripe API](https://docs.stripe.com/api)

## License

[MIT](https://choosealicense.com/licenses/mit/)