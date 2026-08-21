import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

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

@Injectable({ providedIn: 'root' })
export class OrdersService {
  private readonly baseUrl = '/api';

  constructor(private http: HttpClient) {}

  createOrder(tableId: number, items: OrderItem[]): Observable<Order> {
    return this.http.post<Order>(`${this.baseUrl}/orders`, { tableId, items });
  }

  updateStatus(orderId: number, status: string): Observable<void> {
    return this.http.patch<void>(`${this.baseUrl}/orders/${orderId}/status`, { status });
  }
}
