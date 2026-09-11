<?php

use App\Orders\Domain\RecipeCost;

it('calcula el coste teorico de un escandallo', function () {
    $cost = RecipeCost::theoretical([
        ['quantity' => 2, 'unit_cost' => 0.15],
        ['quantity' => 0.01, 'unit_cost' => 8.00],
        ['quantity' => 0.05, 'unit_cost' => 2.50],
    ]);

    expect($cost)->toBe(0.51);
});

it('calcula el margen frente al precio de venta', function () {
    expect(RecipeCost::margin(3.00, 0.51))->toBe(2.49);
});
