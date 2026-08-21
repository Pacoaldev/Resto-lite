import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonBadge } from '@ionic/angular';
import { MobileTablesService, MobileTable } from '../mobile-tables.service';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  standalone: true,
  imports: [
    CommonModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
    IonBadge,
  ],
})
export class HomePage implements OnInit {
  tables: MobileTable[] = [];

  constructor(private tablesService: MobileTablesService) {}

  ngOnInit(): void {
    this.loadTables();
  }

  loadTables(): void {
    this.tablesService.getTables().subscribe({
      next: (data) => (this.tables = data),
      error: (err) => console.error('Error al cargar mesas en app móvil:', err),
    });
  }

  statusLabel(status: MobileTable['status']): string {
    const labels: Record<MobileTable['status'], string> = {
      free: 'Libre',
      occupied: 'Ocupada',
      billRequested: 'Cuenta pedida',
    };
    return labels[status];
  }

  statusColor(status: MobileTable['status']): string {
    const colors: Record<MobileTable['status'], string> = {
      free: 'success',
      occupied: 'warning',
      billRequested: 'danger',
    };
    return colors[status];
  }
}
