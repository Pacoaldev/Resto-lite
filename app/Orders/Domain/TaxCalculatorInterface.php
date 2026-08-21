<?php

namespace App\Orders\Domain;

interface TaxCalculatorInterface
{
    /**
     * Calcula los impuestos correspondientes para un total neto de venta.
     *
     * @param float $subtotal
     * @return float
     */
    public function calculate(float $subtotal): float;

    /**
     * Obtiene el código o etiqueta representativa de la tasa (e.g. IVA, IGV).
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Obtiene la tasa porcentual aplicada como decimal (e.g. 0.21 para 21%).
     *
     * @return float
     */
    public function getRate(): float;
}
