import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface MobileTable {
  id: number;
  name: string;
  status: 'free' | 'occupied' | 'billRequested';
}

@Injectable({ providedIn: 'root' })
export class MobileTablesService {
  // En producción apuntaría a la IP del servidor Laravel, para demo local usa /api
  private readonly apiUrl = 'http://localhost:8080/api/tables';

  constructor(private http: HttpClient) {}

  getTables(): Observable<MobileTable[]> {
    return this.http.get<MobileTable[]>(this.apiUrl);
  }
}
