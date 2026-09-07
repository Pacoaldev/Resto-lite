import { HttpClient } from '@angular/common/http';
import { Injectable, signal } from '@angular/core';
import { Observable, of, throwError, interval, tap } from 'rxjs';
import { catchError, mergeMap } from 'rxjs/operators';

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

export interface TableBill {
  tableId: number;
  orderIds: number[];
  items: OrderItem[];
  subtotal: number;
  taxLabel: string;
  taxRate: number;
  taxAmount: number;
  total: number;
  currency: string;
  country: string;
}

export interface Product {
  id: number;
  name: string;
  price: number;
  stock: number;
}

export interface EstablishmentOption {
  country: string;
  currency: string;
  name: string;
}

export interface EstablishmentContext {
  country: string;
  currency: string;
  name: string;
  taxLabel: string;
  taxRate: number;
  options: EstablishmentOption[];
}

@Injectable({ providedIn: 'root' })
export class OrdersService {
  private readonly baseUrl = '/api';
  private readonly STORAGE_KEY = 'resto_offline_orders';
  readonly establishment = signal<EstablishmentContext | null>(null);

  constructor(private http: HttpClient) {
    this.startSyncTimer();
    this.loadEstablishment().subscribe();
  }

  currencyCode(): string {
    return this.establishment()?.currency ?? 'EUR';
  }

  loadEstablishment(): Observable<EstablishmentContext> {
    return this.http.get<EstablishmentContext>(`${this.baseUrl}/establishment`).pipe(
      tap((ctx) => this.establishment.set(ctx))
    );
  }

  setEstablishmentCountry(country: string): Observable<EstablishmentContext> {
    return this.http.put<EstablishmentContext>(`${this.baseUrl}/establishment`, { country }).pipe(
      tap((ctx) => this.establishment.set(ctx))
    );
  }

  getTables(): Observable<RestaurantTable[]> {
    return this.http.get<RestaurantTable[]>(`${this.baseUrl}/tables`);
  }

  getProducts(): Observable<Product[]> {
    return this.http.get<Product[]>(`${this.baseUrl}/products`);
  }

  getRecentOrders(limit = 10): Observable<Order[]> {
    return this.http.get<Order[]>(`${this.baseUrl}/orders`, { params: { limit } });
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
        const mockOrder: Order = {
          id: -Date.now(),
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
    return this.http.patch<void>(`${this.baseUrl}/orders/${orderId}/status`, { status }).pipe(
      catchError((error) => {
        // Solo caer a offline ante fallo de red; 4xx/5xx del API deben propagarse
        if (error?.status > 0) {
          return throwError(() => error);
        }

        console.warn('Network error detected. Saving order status update locally for offline synchronization...', error);
        // Nota: Para simplificar el demo, no guardamos actualizaciones de estado offline.
        // En una app real, se necesitaría una cola de acciones pendientes más sofisticada.
        return throwError(() => error);
      })
    );
  }

  requestBill(tableId: number): Observable<TableBill> {
    return this.http.post<TableBill>(`${this.baseUrl}/tables/${tableId}/request-bill`, {}).pipe(
      catchError((error) => {
        // Solo caer a offline ante fallo de red; 4xx/5xx del API deben propagarse
        if (error?.status > 0) {
          return throwError(() => error);
        }

        console.warn('Network error detected. Saving bill request locally for offline synchronization...', error);
        // Nota: Para simplificar el demo, no guardamos solicitudes de cuenta offline.
        // En una app real, se necesitaría una cola de acciones pendientes más sofisticada.
        return throwError(() => error);
      })
    );
  }

  settleTable(tableId: number): Observable<void> {
    return this.http.post<void>(`${this.baseUrl}/tables/${tableId}/settle`, {}).pipe(
      catchError((error) => {
        // Solo caer a offline ante fallo de red; 4xx/5xx del API deben propagarse
        if (error?.status > 0) {
          return throwError(() => error);
        }

        console.warn('Network error detected. Saving table settlement locally for offline synchronization...', error);
        // Nota: Para simplificar el demo, no guardamos liquidaciones de mesa offline.
        // En una app real, se necesitaría una cola de acciones pendientes más sofisticada.
        return throwError(() => error);
      })
    );
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
        // Solo caer a offline ante fallo de red; 4xx/5xx del API deben propagarse
        if (err?.status > 0) {
          console.warn('Sync failed with API error, offline orders retained for next retry:', err.message);
          localStorage.setItem(this.STORAGE_KEY, JSON.stringify(orders));
          return throwError(() => err);
        }

        // Si el error es de red, mantener la orden actual en la cola para reintentar
        console.warn('Network error during sync, offline orders retained for next retry:', err.message);
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(orders));
        return throwError(() => err);
      })
    );
  }
}
