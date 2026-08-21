<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Infrastructure\Tax\TaxCountryConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EstablishmentController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->payload(TaxCountryConfig::currentCountry()));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'required|string|in:es,mx,ar,cl,co,pe',
        ]);

        TaxCountryConfig::setCountry($validated['country']);

        return response()->json($this->payload(TaxCountryConfig::currentCountry()));
    }

    /** @return array{country: string, currency: string, name: string, taxLabel: string, taxRate: float, options: list<array{country: string, currency: string, name: string}>} */
    private function payload(string $country): array
    {
        $calculator = TaxCountryConfig::calculator($country);

        return [
            'country' => $country,
            'currency' => TaxCountryConfig::currency($country),
            'name' => TaxCountryConfig::establishmentName($country),
            'taxLabel' => $calculator->getLabel(),
            'taxRate' => $calculator->getRate(),
            'options' => TaxCountryConfig::options(),
        ];
    }
}
