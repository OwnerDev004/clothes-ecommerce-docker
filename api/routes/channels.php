<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.orders', function ($user) {
    return request()->attributes->get('broadcast_guard') === 'admin' && $user !== null;
});

Broadcast::channel('customers.{customerId}', function ($customer, $customerId) {
    return request()->attributes->get('broadcast_guard') === 'customer'
        && (int) $customer->id === (int) $customerId;
});
