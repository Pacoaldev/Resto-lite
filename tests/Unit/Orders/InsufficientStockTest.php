<?php

use App\Orders\Domain\InsufficientStockException;

it('formatea el mensaje de stock insuficiente', function () {
    $error = InsufficientStockException::forProduct('Tostada', 50);

    expect($error->getMessage())->toBe('Stock insuficiente para Tostada (disponible: 50)');
});
