import { Component, effect, input, output, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OrdersService, RestaurantTable, TableBill } from '../core/orders.service';

@Component({
  selector: 'app-tables-board',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './tables-board.component.html',
  styleUrl: './tables-board.component.scss',
})
export class TablesBoardComponent {
  readonly refreshKey = input(0);
  readonly tables = signal<RestaurantTable[]>([]);
  readonly loadError = signal<string | null>(null);
  readonly actionError = signal<string | null>(null);
  readonly bill = signal<TableBill | null>(null);
  readonly busyTableId = signal<number | null>(null);
  readonly selectTable = output<number>();

  constructor(private ordersService: OrdersService) {
    effect(() => {
      this.refreshKey();
      this.loadTables();
    });
  }

  loadTables(): void {
    this.loadError.set(null);
    this.ordersService.getTables().subscribe({
      next: (data) => this.tables.set(data ?? []),
      error: (err) => {
        console.error('Error al cargar mesas:', err);
        this.tables.set([]);
        this.loadError.set('No se pudieron cargar las mesas. ¿Está el backend en http://localhost:8080?');
      },
    });
  }

  onSelectTable(tableId: number): void {
    this.bill.set(null);
    this.actionError.set(null);
    this.selectTable.emit(tableId);
  }

  requestBill(tableId: number): void {
    this.actionError.set(null);
    this.busyTableId.set(tableId);
    this.ordersService.requestBill(tableId).subscribe({
      next: (bill) => {
        this.bill.set(bill);
        this.busyTableId.set(null);
        this.loadTables();
      },
      error: (err) => {
        this.busyTableId.set(null);
        const message = err?.error?.message;
        this.actionError.set(typeof message === 'string' ? message : 'No se pudo generar la cuenta');
      },
    });
  }

  settle(tableId: number): void {
    this.actionError.set(null);
    this.busyTableId.set(tableId);
    this.ordersService.settleTable(tableId).subscribe({
      next: () => {
        this.bill.set(null);
        this.busyTableId.set(null);
        this.loadTables();
      },
      error: (err) => {
        this.busyTableId.set(null);
        const message = err?.error?.message;
        this.actionError.set(typeof message === 'string' ? message : 'No se pudo cobrar la mesa');
      },
    });
  }

  statusLabel(status: RestaurantTable['status']): string {
    const labels: Record<RestaurantTable['status'], string> = {
      free: 'Libre',
      occupied: 'Ocupada',
      billRequested: 'Cuenta pedida',
    };

    return labels[status] ?? status;
  }
}
