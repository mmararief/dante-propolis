<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Pelanggan hanya boleh melihat order miliknya, admin bisa melihat semua.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    /**
     * Hanya admin yang bisa memverifikasi pembayaran.
     */
    public function verify(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya admin yang bisa melakukan pengiriman.
     */
    public function ship(User $user): bool
    {
        return $user->isAdmin();
    }
}


