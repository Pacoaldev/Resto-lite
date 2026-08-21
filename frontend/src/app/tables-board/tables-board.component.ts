import { Component, OnInit, output, signal } from '@angular/core';
import { OrdersService, RestaurantTable } from '../core/orders.service';

@Component({
  selector: 'app-tables-board',
  standalone: true,
  templateUrl: './tables-board.component.html',
  styleUrl: './tables-board.component.scss',
})
export class TablesBoardComponent implements OnInit {
  readonly tables = signal<RestaurantTable[]>([]);
  readonly loadError = signal<string | null>(null);
  readonly selectTable = output<number>();

  constructor(private ordersService: OrdersService) {}

  ngOnInit(): void {
    this.loadTables();
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
    this.selectTable.emit(tableId);
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
