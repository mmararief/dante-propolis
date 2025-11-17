<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forProduct(int $productId): self
    {
        return new self("Stok untuk produk {$productId} tidak mencukupi untuk proses ini.");
    }
}


