import { Product, Totals } from './types';

async function request<T>(url: string, init?: RequestInit): Promise<T> {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        ...init,
    });

    if (!response.ok) {
        throw new Error(`Request to ${url} failed with status ${response.status}`);
    }

    return response.json() as Promise<T>;
}

export async function fetchProducts(signal?: AbortSignal): Promise<Product[]> {
    const data = await request<{ products: Product[] }>('/api/products', { signal });

    return data.products;
}

export function fetchTotals(items: string[], signal?: AbortSignal): Promise<Totals> {
    return request<Totals>('/api/basket/total', {
        method: 'POST',
        body: JSON.stringify({ items }),
        signal,
    });
}
