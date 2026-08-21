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

  constructor(private http: HttpClient) {}

  getTables(): Observable<RestaurantTable[]> {
    return this.http.get<RestaurantTable[]>(`${this.baseUrl}/tables`);
  }

  getProducts(): Observable<Product[]> {
    return this.http.get<Product[]>(`${this.baseUrl}/products`);
  }

  createOrder(tableId: number, items: OrderItem[]): Observable<Order> {
    return this.http.post<Order>(`${this.baseUrl}/orders`, { tableId, items });
  }

  updateStatus(orderId: number, status: string): Observable<void> {
    return this.http.patch<void>(`${this.baseUrl}/orders/${orderId}/status`, { status });
  }
}
