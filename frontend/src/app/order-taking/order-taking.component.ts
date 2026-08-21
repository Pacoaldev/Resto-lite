import { Component, Input, OnInit, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Button } from 'primeng/button';
import { OrderItem, OrdersService, Product } from '../core/orders.service';

@Component({
  selector: 'app-order-taking',
  standalone: true,
  imports: [CommonModule, FormsModule, Button],
  templateUrl: './order-taking.component.html',
  styleUrl: './order-taking.component.scss',
})
export class OrderTakingComponent implements OnInit {
  @Input({ required: true }) tableId!: number;
  @Output() orderPlaced = new EventEmitter<void>();

  availableItems: Product[] = [];
  quantities: Record<string, number> = {};
  saving = false;

  constructor(private ordersService: OrdersService) {}

  ngOnInit(): void {
    this.loadProducts();
  }

  loadProducts(): void {
    this.ordersService.getProducts().subscribe({
      next: (data) => (this.availableItems = data),
      error: (err) => console.error('Error al cargar productos:', err),
    });
  }

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
        this.orderPlaced.emit();
      },
      error: () => {
        this.saving = false;
      },
    });
  }
}
