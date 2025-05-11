<?php
namespace Stripe\Services;

use Stripe\Contracts\StripeInterface;
use Stripe\Exceptions\StripeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class StripeService implements StripeInterface
{
    private string $apiKey;
    private string $enable_3d;
    private string $apiUrl;

    public function __construct(string $apiKey, bool $enable_3d)
    {
        $this->apiKey = $apiKey;
        $this->enable_3d = $enable_3d;
        $this->apiUrl = 'https://api.stripe.com/v1';
    }

    /**
     * Create New Payment Intent
     * @throws StripeException
     */
    public function create_checkout_session(array $p_data): array
    {

        $missingKeys = $this->checkMissingKeys($p_data, [
            'currency', 'amount', 'product_name', 'success_url'
        ]);

        if (!empty($missingKeys)) {
            throw new StripeException($missingKeys . ' parameters are required');
        }

        $data = [
            'payment_method_types[]' => 'card',
            'mode' => 'payment',
            'line_items[0][price_data][unit_amount]' => $p_data['amount'],
            'line_items[0][price_data][currency]' => $p_data['currency'],
            'line_items[0][price_data][product_data][name]' => $p_data['product_name'],
            'line_items[0][quantity]' => 1,
            'success_url' => $p_data['success_url'],
        ];


        if (array_key_exists('ref_id', $p_data))
            $data['metadata[reference_id]'] = $p_data['ref_id'];

        if (array_key_exists('quantity', $p_data))
            $data['line_items[0][quantity]'] = $p_data['quantity'];

        if (array_key_exists('product_description', $p_data))
            $data['line_items[0][price_data][product_data][description]'] = $p_data['product_description'];

        if (array_key_exists('cancel_url', $p_data))
            $data['cancel_url'] = $p_data['cancel_url'];

        if ($this->enable_3d) {
            $data['payment_method_options[card][request_three_d_secure]'] = 'challenge';
        }

        return $this->request('post', '/checkout/sessions', $data);
    }

    /**
     * Get Payment Intent Status
     */
    public function get_checkout_session_status(string $session_id): array
    {
        return $this->request('get', '/checkout/sessions/' . $session_id, []);
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->asForm()
            ->{$method}("{$this->apiUrl}{$endpoint}", $data);

        return $response->json();
    }

    private function checkMissingKeys(array $data, array $requiredKeys): string
    {
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (!Arr::has($data, $key)) {
                $missingKeys[] = $key;
            }
        }

        return implode(', ', $missingKeys);
    }

}
