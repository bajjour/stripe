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

## API Documentation

For more details about the Stripe API, refer to the official documentation:

[Stripe API](https://docs.stripe.com/api)

## License

[MIT](https://choosealicense.com/licenses/mit/)