import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, of, throwError, interval } from 'rxjs';
import { catchError, tap, mergeMap } from 'rxjs/operators';

export interface OrderItem {
  name: string;
  price: number;
  quantity: number;
}

export interface Order {
  id: number;
  tableId: number;
  status: string;
  items: OrderItem[];
  total: number;
}

export interface RestaurantTable {
  id: number;
  name: string;
  status: 'free' | 'occupied' | 'billRequested';
}

export interface Product {
  id: number;
  name: string;
  price: number;
  stock: number;
}

@Injectable({ providedIn: 'root' })
export class OrdersService {
  private readonly baseUrl = '/api';
  private readonly STORAGE_KEY = 'resto_offline_orders';

  constructor(private http: HttpClient) {
    this.startSyncTimer();
  }

  getTables(): Observable<RestaurantTable[]> {
    return this.http.get<RestaurantTable[]>(`${this.baseUrl}/tables`);
  }

  getProducts(): Observable<Product[]> {
    return this.http.get<Product[]>(`${this.baseUrl}/products`);
  }

  createOrder(tableId: number, items: OrderItem[]): Observable<Order> {
    return this.http.post<Order>(`${this.baseUrl}/orders`, { tableId, items }).pipe(
      catchError((error) => {
        // Solo caer a offline ante fallo de red; 4xx/5xx del API deben propagarse
        if (error?.status > 0) {
          return throwError(() => error);
        }

        console.warn('Network error detected. Saving order locally for offline synchronization...', error);
        this.saveOffline({ tableId, items });
        // Simular respuesta exitosa para el flujo UI local
        const mockOrder: Order = {
          id: -Date.now(), // ID negativo temporal
          tableId,
          status: 'offline_pending',
          items,
          total: items.reduce((acc, curr) => acc + curr.price * curr.quantity, 0),
        };
        return of(mockOrder);
      })
    );
  }

  updateStatus(orderId: number, status: string): Observable<void> {
    return this.http.patch<void>(`${this.baseUrl}/orders/${orderId}/status`, { status });
  }

  private saveOffline(orderData: { tableId: number; items: OrderItem[] }): void {
    const orders = this.getOfflineOrders();
    orders.push(orderData);
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(orders));
  }

  private getOfflineOrders(): Array<{ tableId: number; items: OrderItem[] }> {
    const stored = localStorage.getItem(this.STORAGE_KEY);
    return stored ? JSON.parse(stored) : [];
  }

  private clearOfflineOrders(): void {
    localStorage.removeItem(this.STORAGE_KEY);
  }

  private startSyncTimer(): void {
    // Intentar sincronizar cada 30 segundos
    interval(30000).pipe(
      mergeMap(() => {
        const offlineOrders = this.getOfflineOrders();
        if (offlineOrders.length === 0) {
          return of(null);
        }
        console.log(`Attempting to sync ${offlineOrders.length} offline orders...`);
        return this.syncOrdersSequentially(offlineOrders);
      })
    ).subscribe({
      error: (err) => console.error('Offline synchronization cycle error:', err),
    });
  }

  private syncOrdersSequentially(orders: Array<{ tableId: number; items: OrderItem[] }>): Observable<any> {
    if (orders.length === 0) {
      this.clearOfflineOrders();
      return of(true);
    }
    const [current, ...remaining] = orders;
    return this.http.post<Order>(`${this.baseUrl}/orders`, current).pipe(
      tap(() => console.log('Successfully synchronized offline order for table:', current.tableId)),
      mergeMap(() => this.syncOrdersSequentially(remaining)),
      catchError((err) => {
        console.warn('Sync failed, offline orders retained for next retry:', err.message);
        // Volver a escribir las órdenes fallidas
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(orders));
        return throwError(() => err);
      })
    );
  }
}
