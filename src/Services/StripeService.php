<?php
namespace Stripe\Services;

use Stripe\Contracts\StripeInterface;
use Stripe\Exceptions\StripeException;
use Illuminate\Support\Facades\Http;

class StripeService implements StripeInterface
{
    private string $apiKey;
    private bool $enable_3d;
    private string $apiUrl;

    public function __construct(string $apiKey, bool $enable_3d)
    {
        $this->apiKey = $apiKey;
        $this->enable_3d = $enable_3d;
        $this->apiUrl = 'https://api.stripe.com/v1';
    }

    /**
     * Create a One-Time Payment Session
     * @throws StripeException
     */
    public function create_checkout_session(array $p_data): array
    {
        $p_data['mode'] = 'payment';
        return $this->create_session($p_data);
    }

    /**
     * Create Subscription Session
     * @throws StripeException
     */
    public function create_subscription(array $p_data): array
    {
        $this->validate_required_keys($p_data, [
            'interval'
        ]);

        $p_data['mode'] = 'subscription';

        return $this->create_session($p_data);
    }

    /**
     * Create Setup Session for Saving Payment Method
     * @throws StripeException
     */
    public function create_setup_intent(array $p_data): array
    {
        $this->validate_required_keys($p_data, [
            'success_url'
        ]);

        $data = [
            'mode' => 'setup',
            'customer_creation' => 'always',
            'payment_method_types[]' => 'card',
            'success_url' => $p_data['success_url'],
        ];

        $this->fill_optional_data($data, $p_data, 'cancel_url', 'cancel_url');
        $this->fill_optional_data($data, $p_data, 'metadata[reference_id]', 'ref_id');

        if ($this->enable_3d) {
            $data['payment_method_options[card][request_three_d_secure]'] = 'challenge';
        }

        return $this->request('post', '/checkout/sessions', $data);
    }

    /**
     * Create and Finalize an Invoice
     * @throws StripeException
     */
    public function create_invoice(array $p_data): array
    {
        $this->validate_required_keys($p_data, [
            'customer_id', 'amount', 'currency', 'description'
        ]);

        $data = [
            'customer' => $p_data['customer_id'],
            'auto_advance' => 'false',
            'currency' => $p_data['currency'],
        ];

        $invoice = $this->request('post', '/invoices', $data);

        if (array_key_exists('id', $invoice)) {
            $item = [
                'customer' => $p_data['customer_id'],
                'invoice' => $invoice['id'],
                'amount' => $p_data['amount'],
                'description' => $p_data['description'],
            ];

            $this->fill_optional_data($item, $p_data, 'metadata[reference_id]', 'ref_id');

            $invoice_item = $this->request('post', '/invoiceitems', $item);

            if (array_key_exists('id', $invoice_item))
                $invoice = $this->request('post', '/invoices/' . $invoice['id'] . '/finalize');
            else
                return $invoice_item;
        }

        return $invoice;
    }

    /**
     * Charge an Invoice
     */
    public function charge_invoice(string $invoice_id, string $payment_method_id): array
    {
        return $this->request('post', '/invoices/' . $invoice_id . '/pay', [
            'off_session' => 'true',
            'payment_method' => $payment_method_id,
        ]);
    }

    /**
     * Get Payment Intent Status
     */
    public function get_checkout_session_status(string $session_id): array
    {
        return $this->request('get', '/checkout/sessions/' . $session_id, []);
    }

    /**
     * Get Subscription Status
     */
    public function get_subscription_status(string $subscription_id): array
    {
        return $this->request('get', '/subscriptions/' . $subscription_id, []);
    }

    /**
     * Get Setup Intent Status
     */
    public function get_setup_intent_status(string $intent_id): array
    {
        return $this->request('get', '/setup_intents/' . $intent_id, []);
    }

    /**
     * Get Invoice Status
     */
    public function get_invoice_status(string $invoice_id): array
    {
        return $this->request('get', '/invoices/' . $invoice_id, []);
    }

    /**
     * Create Refund
     * @throws StripeException
     */
    public function create_refund(array $p_data): array
    {
        $this->validate_required_keys($p_data, [
            'payment_intent',
        ]);

        $data = [
            'payment_intent' => $p_data['payment_intent'],
        ];

        $this->fill_optional_data($p_data, $p_data, 'reason', 'reason');
        $this->fill_optional_data($p_data, $p_data, 'amount', 'amount');

        return $this->request('post', '/refunds/', $data);
    }

    /**
     * Get Refund Status
     */
    public function get_refund(string $refund_id): array
    {
        return $this->request('get', '/refunds/' . $refund_id, []);
    }

    /**
     * Cancel Refund
     */
    public function cancel_refund(string $refund_id): array
    {
        return $this->request('post', '/refunds/' . $refund_id, []);
    }

    /**
     * @throws StripeException
     */
    private function create_session(array $p_data): array
    {

        $this->validate_required_keys($p_data, [
            'currency', 'amount', 'product_name', 'success_url', 'mode'
        ]);

        $data = [
            'payment_method_types[]' => 'card',
            'mode' => $p_data['mode'],
            'line_items[0][price_data][unit_amount]' => $p_data['amount'],
            'line_items[0][price_data][currency]' => $p_data['currency'],
            'line_items[0][price_data][product_data][name]' => $p_data['product_name'],
            'line_items[0][quantity]' => 1,
            'success_url' => $p_data['success_url'],
        ];

        $this->fill_optional_data($data, $p_data, 'metadata[reference_id]', 'ref_id');
        $this->fill_optional_data($data, $p_data, 'line_items[0][quantity]', 'quantity');
        $this->fill_optional_data($data, $p_data, 'line_items[0][price_data][product_data][description]', 'product_description');
        $this->fill_optional_data($data, $p_data, 'cancel_url', 'cancel_url');

        if ($p_data['mode'] === 'subscription') {
            $data['line_items[0][price_data][recurring][interval]'] = $p_data['interval'];
            $this->fill_optional_data($data, $p_data, 'line_items[0][price_data][recurring][interval_count]', 'interval_count');
        }

        if ($this->enable_3d) {
            $data['payment_method_options[card][request_three_d_secure]'] = 'challenge';
        }

        return $this->request('post', '/checkout/sessions', $data);
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->asForm()
            ->{$method}("{$this->apiUrl}{$endpoint}", $data);

        return $response->json();
    }

    /**
     * @throws StripeException
     */
    private function validate_required_keys(array $data, array $requiredKeys): void
    {
        $missingKeys = array_diff($requiredKeys, array_keys($data));

        if (!empty($missingKeys)) {
            throw new StripeException(
                'Missing required parameters: ' . implode(', ', $missingKeys)
            );
        }
    }

    private function fill_optional_data(array &$stripe_data, array $user_arr, string $stripe_field, string $user_field): void
    {
        if (array_key_exists($user_field, $user_arr)) {
            $stripe_data[$stripe_field] = $user_arr[$user_field];
        }
    }

}
