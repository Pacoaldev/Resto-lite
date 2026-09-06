import { Component, computed, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TablesBoardComponent } from './tables-board/tables-board.component';
import { OrderTakingComponent } from './order-taking/order-taking.component';
import { InventoryViewComponent } from './inventory-view/inventory-view.component';
import { OrdersService, Order } from './core/orders.service';

type WorkspaceSection = 'sala' | 'inventario';

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
  readonly section = signal<WorkspaceSection>('sala');
  readonly salaBusyCount = signal(0);
  readonly recentOrders = signal<Order[]>([]);

  readonly taxSummary = computed(() => {
    const est = this.ordersService.establishment();
    if (!est) {
      return null;
    }
    return `${est.taxLabel} ${(est.taxRate * 100).toFixed(0)}% · ${est.currency}`;
  });

  constructor(readonly ordersService: OrdersService) {
    this.refreshRecentOrders();
  }

  setSection(section: WorkspaceSection): void {
    this.section.set(section);
    if (section === 'inventario') {
      this.inventoryTick.update((n) => n + 1);
    }
  }

  onSelectTable(tableId: number): void {
    this.section.set('sala');
    this.selectedTableId.set(tableId);
  }

  onOrderPlaced(): void {
    this.selectedTableId.set(null);
    this.inventoryTick.update((n) => n + 1);
    this.tablesTick.update((n) => n + 1);
    this.refreshRecentOrders();
  }

  onBoardChanged(busyCount: number): void {
    this.salaBusyCount.set(busyCount);
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

  private refreshRecentOrders(): void {
    this.ordersService.getRecentOrders().subscribe({
      next: (orders) => this.recentOrders.set(orders),
      error: () => this.recentOrders.set([]),
    });
  }
}
