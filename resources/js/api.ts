import { Product, Totals } from './types';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

interface ErrorBody {
    message?: string;
    errors?: Record<string, string[]>;
}

async function request<T>(url: string, init?: RequestInit): Promise<T> {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        ...init,
    });

    if (!response.ok) {
        const body = (await response.json().catch(() => null)) as ErrorBody | null;

        throw new ApiError(
            body?.message ?? `Request to ${url} failed with status ${response.status}`,
            response.status,
            body?.errors ?? {},
        );
    }

    return response.json() as Promise<T>;
}

export async function fetchProducts(signal?: AbortSignal): Promise<Product[]> {
    const data = await request<{ products: Product[] }>('/api/products', { signal });

    return data.products;
}

export function fetchTotals(items: string[], coupon: string | null, signal?: AbortSignal): Promise<Totals> {
    return request<Totals>('/api/basket/total', {
        method: 'POST',
        body: JSON.stringify(coupon === null ? { items } : { items, coupon }),
        signal,
    });
}
