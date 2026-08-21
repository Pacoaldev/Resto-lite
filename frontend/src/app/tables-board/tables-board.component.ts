import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TableModule } from 'primeng/table';
import { TagModule } from 'primeng/tag';
import { ButtonModule } from 'primeng/button';

interface RestaurantTable {
  id: number;
  name: string;
  status: 'free' | 'occupied' | 'billRequested';
}

@Component({
  selector: 'app-tables-board',
  standalone: true,
  imports: [CommonModule, TableModule, TagModule, ButtonModule],
  templateUrl: './tables-board.component.html',
  styleUrl: './tables-board.component.scss',
})
export class TablesBoardComponent {
  tables: RestaurantTable[] = [
    { id: 1, name: 'Mesa 1', status: 'free' },
    { id: 2, name: 'Mesa 2', status: 'occupied' },
    { id: 3, name: 'Mesa 3', status: 'billRequested' },
  ];

  statusLabel(status: RestaurantTable['status']): string {
    const labels: Record<RestaurantTable['status'], string> = {
      free: 'Libre',
      occupied: 'Ocupada',
      billRequested: 'Cuenta pedida',
    };

    return labels[status];
  }

  statusSeverity(status: RestaurantTable['status']): 'success' | 'warning' | 'danger' {
    const severities: Record<RestaurantTable['status'], 'success' | 'warning' | 'danger'> = {
      free: 'success',
      occupied: 'warning',
      billRequested: 'danger',
    };

    return severities[status];
  }
}
