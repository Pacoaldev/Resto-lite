import { Component, effect, input, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OrdersService, Product } from '../core/orders.service';

@Component({
  selector: 'app-inventory-view',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './inventory-view.component.html',
  styleUrl: './inventory-view.component.scss',
})
export class InventoryViewComponent {
  readonly refreshKey = input(0);
  readonly products = signal<Product[]>([]);
  readonly loadError = signal<string | null>(null);

  constructor(private ordersService: OrdersService) {
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
}
