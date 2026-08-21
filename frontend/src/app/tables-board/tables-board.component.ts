import { Component, OnInit, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TableModule } from 'primeng/table';
import { OrdersService, RestaurantTable } from '../core/orders.service';

@Component({
  selector: 'app-tables-board',
  standalone: true,
  imports: [CommonModule, TableModule],
  templateUrl: './tables-board.component.html',
  styleUrl: './tables-board.component.scss',
})
export class TablesBoardComponent implements OnInit {
  tables: RestaurantTable[] = [];

  @Output() selectTable = new EventEmitter<number>();

  constructor(private ordersService: OrdersService) {}

  ngOnInit(): void {
    this.loadTables();
  }

  loadTables(): void {
    this.ordersService.getTables().subscribe({
      next: (data) => (this.tables = data),
      error: (err) => console.error('Error al cargar mesas:', err),
    });
  }

  onSelectTable(tableId: number): void {
    this.selectTable.emit(tableId);
  }

  statusLabel(status: RestaurantTable['status']): string {
    const labels: Record<RestaurantTable['status'], string> = {
      free: 'Libre',
      occupied: 'Ocupada',
      billRequested: 'Cuenta pedida',
    };

    return labels[status];
  }
}
