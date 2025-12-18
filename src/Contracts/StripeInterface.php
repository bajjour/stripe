<?php
namespace Stripe\Contracts;

interface StripeInterface
{

    public function create_checkout_session(array $p_data): array;

    public function create_subscription(array $p_data): array;

    public function create_setup_intent(array $p_data): array;

    public function create_invoice(array $p_data): array;

    public function charge_invoice(string $invoice_id, string $payment_method_id): array;

    public function get_checkout_session_status(string $session_id): array;

    public function get_subscription_status(string $subscription_id): array;

    public function get_setup_intent_status(string $intent_id): array;

    public function get_invoice_status(string $invoice_id): array;

    public function create_refund(array $p_data): array;
    public function get_refund(string $refund_id): array;
    public function cancel_refund(string $refund_id): array;

}
