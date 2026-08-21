import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { TablesBoardComponent } from './tables-board/tables-board.component';
import { OrderTakingComponent } from './order-taking/order-taking.component';
import { InventoryViewComponent } from './inventory-view/inventory-view.component';
import { CommonModule } from '@angular/common';

@Component({
  imports: [CommonModule, TablesBoardComponent, OrderTakingComponent, InventoryViewComponent],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  selectedTableId: number | null = null;

  onSelectTable(tableId: number): void {
    this.selectedTableId = tableId;
  }

  onOrderPlaced(): void {
    this.selectedTableId = null;
  }
}
