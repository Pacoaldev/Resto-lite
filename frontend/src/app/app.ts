import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TablesBoardComponent } from './tables-board/tables-board.component';
import { OrderTakingComponent } from './order-taking/order-taking.component';
import { InventoryViewComponent } from './inventory-view/inventory-view.component';
import { OrdersService } from './core/orders.service';

@Component({
  imports: [CommonModule, TablesBoardComponent, OrderTakingComponent, InventoryViewComponent],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  readonly selectedTableId = signal<number | null>(null);
  readonly inventoryTick = signal(0);
  readonly tablesTick = signal(0);
  readonly switchingCountry = signal(false);

  constructor(readonly ordersService: OrdersService) {}

  onSelectTable(tableId: number): void {
    this.selectedTableId.set(tableId);
  }

  onOrderPlaced(): void {
    this.selectedTableId.set(null);
    this.inventoryTick.update((n) => n + 1);
    this.tablesTick.update((n) => n + 1);
  }

  onCountryChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const country = select.value;
    this.switchingCountry.set(true);
    this.ordersService.setEstablishmentCountry(country).subscribe({
      next: () => this.switchingCountry.set(false),
      error: () => this.switchingCountry.set(false),
    });
  }
}
