import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TableModule } from 'primeng/table';
import { OrdersService, Product } from '../core/orders.service';

@Component({
  selector: 'app-inventory-view',
  standalone: true,
  imports: [CommonModule, TableModule],
  templateUrl: './inventory-view.component.html',
  styleUrl: './inventory-view.component.scss'
})
export class InventoryViewComponent implements OnInit {
  products: Product[] = [];

  constructor(private ordersService: OrdersService) {}

  ngOnInit(): void {
    this.loadInventory();
  }

  loadInventory(): void {
    this.ordersService.getProducts().subscribe({
      next: (data) => (this.products = data),
      error: (err) => console.error('Error al cargar inventario:', err)
    });
  }
}
