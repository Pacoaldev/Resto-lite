import { Component, effect, input, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { OrdersService, Product } from '../core/orders.service';

@Component({
  selector: 'app-inventory-view',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './inventory-view.component.html',
  styleUrl: './inventory-view.component.scss',
})
export class InventoryViewComponent {
  readonly refreshKey = input(0);
  readonly products = signal<Product[]>([]);
  readonly loadError = signal<string | null>(null);
  readonly actionError = signal<string | null>(null);
  readonly busyProductId = signal<number | null>(null);
  readonly wasteQty: Record<number, number> = {};
  readonly wasteReason: Record<number, string> = {};

  constructor(readonly ordersService: OrdersService) {
    effect(() => {
      this.refreshKey();
      this.loadInventory();
    });
  }

  loadInventory(): void {
    this.loadError.set(null);
    this.ordersService.getProducts().subscribe({
      next: (data) => {
        this.products.set(data ?? []);
      },
      error: (err) => {
        console.error('Error al cargar inventario:', err);
        this.products.set([]);
        this.loadError.set('No se pudo cargar el inventario. ¿Está el backend en http://localhost:8080?');
      },
    });
  }

  registerWaste(product: Product): void {
    const quantity = Number(this.wasteQty[product.id] ?? 1);
    if (!Number.isFinite(quantity) || quantity < 1) {
      this.actionError.set('Cantidad de merma inválida');
      return;
    }

    this.actionError.set(null);
    this.busyProductId.set(product.id);
    this.ordersService.registerWaste(product.id, quantity, this.wasteReason[product.id]).subscribe({
      next: () => {
        this.busyProductId.set(null);
        this.wasteQty[product.id] = 1;
        this.wasteReason[product.id] = '';
        this.loadInventory();
      },
      error: (err) => {
        this.busyProductId.set(null);
        const message = err?.error?.message;
        this.actionError.set(typeof message === 'string' ? message : 'No se pudo registrar la merma');
      },
    });
  }

  stockClass(stock: number): string {
    if (stock < 10) {
      return 'low-stock';
    }
    if (stock < 40) {
      return 'mid-stock';
    }
    return '';
  }
}
