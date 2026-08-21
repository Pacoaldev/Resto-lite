import { Component, input, OnInit, output, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { OrderItem, OrdersService, Product } from '../core/orders.service';

@Component({
  selector: 'app-order-taking',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './order-taking.component.html',
  styleUrl: './order-taking.component.scss',
})
export class OrderTakingComponent implements OnInit {
  readonly tableId = input.required<number>();
  readonly orderPlaced = output<void>();

  readonly availableItems = signal<Product[]>([]);
  readonly quantities = signal<Record<string, number>>({});
  readonly saving = signal(false);

  constructor(private ordersService: OrdersService) {}

  ngOnInit(): void {
    this.loadProducts();
  }

  loadProducts(): void {
    this.ordersService.getProducts().subscribe({
      next: (data) => {
        this.availableItems.set(data ?? []);
        const qty: Record<string, number> = {};
        for (const item of data ?? []) {
          qty[item.name] = 0;
        }
        this.quantities.set(qty);
      },
      error: (err) => console.error('Error al cargar productos:', err),
    });
  }

  setQuantity(name: string, value: string | number): void {
    const n = typeof value === 'number' ? value : Number(value);
    this.quantities.update((q) => ({ ...q, [name]: Number.isFinite(n) ? n : 0 }));
  }

  submitOrder(): void {
    const qty = this.quantities();
    const items: OrderItem[] = this.availableItems()
      .filter((item) => (qty[item.name] ?? 0) > 0)
      .map((item) => ({
        name: item.name,
        price: item.price,
        quantity: qty[item.name],
      }));

    if (items.length === 0) {
      return;
    }

    this.saving.set(true);

    this.ordersService.createOrder(this.tableId(), items).subscribe({
      next: () => {
        const reset: Record<string, number> = {};
        for (const item of this.availableItems()) {
          reset[item.name] = 0;
        }
        this.quantities.set(reset);
        this.saving.set(false);
        this.orderPlaced.emit();
      },
      error: () => {
        this.saving.set(false);
      },
    });
  }
}
