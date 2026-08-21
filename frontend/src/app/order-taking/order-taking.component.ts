import { Component, input, OnInit, output, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OrderItem, OrdersService, Product } from '../core/orders.service';

@Component({
  selector: 'app-order-taking',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './order-taking.component.html',
  styleUrl: './order-taking.component.scss',
})
export class OrderTakingComponent implements OnInit {
  readonly tableId = input.required<number>();
  readonly orderPlaced = output<void>();

  readonly availableItems = signal<Product[]>([]);
  readonly quantities = signal<Record<string, number>>({});
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);

  constructor(readonly ordersService: OrdersService) {}

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

  setQuantity(product: Product, value: string | number): void {
    const n = typeof value === 'number' ? value : Number(value);
    const safe = Number.isFinite(n) ? Math.max(0, Math.floor(n)) : 0;
    const clamped = Math.min(safe, product.stock);
    this.quantities.update((q) => ({ ...q, [product.name]: clamped }));
    this.error.set(null);
  }

  submitOrder(): void {
    this.error.set(null);
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

    const overstock = items.find((item) => {
      const product = this.availableItems().find((p) => p.name === item.name);
      return !product || item.quantity > product.stock;
    });
    if (overstock) {
      this.error.set(`Stock insuficiente para ${overstock.name}`);
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
      error: (err) => {
        this.saving.set(false);
        const message = err?.error?.message;
        this.error.set(typeof message === 'string' ? message : 'No se pudo enviar el pedido');
      },
    });
  }
}
