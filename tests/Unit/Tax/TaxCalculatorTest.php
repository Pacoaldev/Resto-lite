<?php

namespace Tests\Unit\Tax;

use App\Orders\Infrastructure\Tax\SpainTaxCalculator;
use App\Orders\Infrastructure\Tax\MexicoTaxCalculator;
use App\Orders\Infrastructure\Tax\ArgentinaTaxCalculator;
use App\Orders\Infrastructure\Tax\ChileTaxCalculator;
use App\Orders\Infrastructure\Tax\ColombiaTaxCalculator;
use App\Orders\Infrastructure\Tax\PeruTaxCalculator;

test('Spain tax calculator applies 21% IVA', function () {
    $calculator = new SpainTaxCalculator();
    expect($calculator->getLabel())->toBe('IVA');
    expect($calculator->getRate())->toBe(0.21);
    expect($calculator->calculate(100))->toBe(21.0);
    expect($calculator->calculate(10.55))->toBe(2.22);
});

test('Mexico tax calculator applies 16% IVA', function () {
    $calculator = new MexicoTaxCalculator();
    expect($calculator->getLabel())->toBe('IVA');
    expect($calculator->getRate())->toBe(0.16);
    expect($calculator->calculate(100))->toBe(16.0);
    expect($calculator->calculate(10.55))->toBe(1.69);
});

test('Argentina tax calculator applies 21% IVA', function () {
    $calculator = new ArgentinaTaxCalculator();
    expect($calculator->getLabel())->toBe('IVA');
    expect($calculator->getRate())->toBe(0.21);
    expect($calculator->calculate(100))->toBe(21.0);
});

test('Chile tax calculator applies 19% IVA', function () {
    $calculator = new ChileTaxCalculator();
    expect($calculator->getLabel())->toBe('IVA');
    expect($calculator->getRate())->toBe(0.19);
    expect($calculator->calculate(100))->toBe(19.0);
});

test('Colombia tax calculator applies 19% IVA', function () {
    $calculator = new ColombiaTaxCalculator();
    expect($calculator->getLabel())->toBe('IVA');
    expect($calculator->getRate())->toBe(0.19);
    expect($calculator->calculate(100))->toBe(19.0);
});

test('Peru tax calculator applies 18% IGV', function () {
    $calculator = new PeruTaxCalculator();
    expect($calculator->getLabel())->toBe('IGV');
    expect($calculator->getRate())->toBe(0.18);
    expect($calculator->calculate(100))->toBe(18.0);
    expect($calculator->calculate(10.55))->toBe(1.9);
});
