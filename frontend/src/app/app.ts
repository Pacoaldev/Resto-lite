import { Component, signal } from '@angular/core';
import { TablesBoardComponent } from './tables-board/tables-board.component';
import { OrderTakingComponent } from './order-taking/order-taking.component';
import { InventoryViewComponent } from './inventory-view/inventory-view.component';

@Component({
  imports: [TablesBoardComponent, OrderTakingComponent, InventoryViewComponent],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  readonly selectedTableId = signal<number | null>(null);
  readonly inventoryTick = signal(0);

  onSelectTable(tableId: number): void {
    this.selectedTableId.set(tableId);
  }

  onOrderPlaced(): void {
    this.selectedTableId.set(null);
    this.inventoryTick.update((n) => n + 1);
  }
}
