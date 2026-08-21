import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ButtonModule } from 'primeng/button';
import { InputNumberModule } from 'primeng/inputnumber';
import { OrderItem, OrdersService } from '../core/orders.service';

@Component({
  selector: 'app-order-taking',
  standalone: true,
  imports: [CommonModule, FormsModule, ButtonModule, InputNumberModule],
  templateUrl: './order-taking.component.html',
  styleUrl: './order-taking.component.scss',
})
export class OrderTakingComponent {
  @Input({ required: true }) tableId!: number;

  availableItems = [
    { name: 'Cafe', price: 2.5 },
    { name: 'Tostada', price: 3.0 },
    { name: 'Zumo natural', price: 4.0 },
  ];

  quantities: Record<string, number> = {};
  saving = false;

  constructor(private ordersService: OrdersService) {}

  submitOrder(): void {
    const items: OrderItem[] = this.availableItems
      .filter((item) => (this.quantities[item.name] ?? 0) > 0)
      .map((item) => ({
        name: item.name,
        price: item.price,
        quantity: this.quantities[item.name],
      }));

    if (items.length === 0) {
      return;
    }

    this.saving = true;

    this.ordersService.createOrder(this.tableId, items).subscribe({
      next: () => {
        this.quantities = {};
        this.saving = false;
      },
      error: () => {
        this.saving = false;
      },
    });
  }
}
