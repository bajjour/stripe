<?php
namespace Stripe\Contracts;

interface StripeInterface
{
    public function create_checkout_session(array $p_data): array;

    public function get_checkout_session_status(string $session_id): array;

}
