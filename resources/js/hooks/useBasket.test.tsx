import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { useBasket } from './useBasket';

const PRODUCTS = [
    { code: 'R01', name: 'Red Widget', price: '32.95' },
    { code: 'B01', name: 'Blue Widget', price: '7.95' },
];

const ZERO_TOTALS = {
    subtotal: '0.00',
    discount: '0.00',
    coupon_discount: '0.00',
    delivery: '0.00',
    total: '0.00',
};

function jsonResponse(body: unknown, status = 200): Response {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(body),
    } as Response;
}

type FetchStub = ReturnType<typeof vi.fn>;

function stubFetch(handler: (url: string, init?: RequestInit) => Response): FetchStub {
    const stub = vi.fn((url: string, init?: RequestInit) => Promise.resolve(handler(url, init)));
    vi.stubGlobal('fetch', stub);

    return stub;
}

function happyPathFetch(): FetchStub {
    return stubFetch((url, init) => {
        if (url === '/api/products') {
            return jsonResponse({ products: PRODUCTS });
        }

        const body = JSON.parse(String(init?.body)) as { items: string[]; coupon?: string };

        if (body.coupon === 'NOPE') {
            return jsonResponse({ message: 'Invalid.', errors: { coupon: ['Invalid coupon.'] } }, 422);
        }

        return jsonResponse({ ...ZERO_TOTALS, total: String(body.items.length) + '.00' });
    });
}

function lastTotalsBody(stub: FetchStub): { items: string[]; coupon?: string } {
    const totalsCalls = stub.mock.calls.filter(([url]) => url === '/api/basket/total');

    return JSON.parse(String((totalsCalls.at(-1)?.[1] as RequestInit).body));
}

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('useBasket', () => {
    it('loads the catalogue on mount', async () => {
        happyPathFetch();

        const { result } = renderHook(() => useBasket());

        await waitFor(() => expect(result.current.products).toHaveLength(2));
    });

    it('reprices when items are added', async () => {
        const stub = happyPathFetch();

        const { result } = renderHook(() => useBasket());
        act(() => result.current.addItem('R01'));

        await waitFor(() => expect(result.current.totals?.total).toBe('1.00'));
        expect(lastTotalsBody(stub).items).toEqual(['R01']);
    });

    it('removes only the last occurrence of a product', async () => {
        const stub = happyPathFetch();

        const { result } = renderHook(() => useBasket());
        act(() => result.current.addItem('R01'));
        act(() => result.current.addItem('B01'));
        act(() => result.current.addItem('R01'));
        act(() => result.current.removeItem('R01'));

        await waitFor(() => expect(result.current.totals?.total).toBe('2.00'));
        expect(lastTotalsBody(stub).items).toEqual(['R01', 'B01']);
    });

    it('keeps the catalogue error visible when pricing succeeds', async () => {
        stubFetch((url) =>
            url === '/api/products'
                ? jsonResponse({ message: 'boom' }, 500)
                : jsonResponse(ZERO_TOTALS),
        );

        const { result } = renderHook(() => useBasket());

        await waitFor(() => expect(result.current.totals).not.toBeNull());
        await waitFor(() => expect(result.current.catalogueError).not.toBeNull());
    });

    it('reports an invalid coupon and drops it from the next request', async () => {
        const stub = happyPathFetch();

        const { result } = renderHook(() => useBasket());
        act(() => result.current.addItem('R01'));
        act(() => result.current.applyCoupon('NOPE'));

        await waitFor(() => expect(result.current.couponError).not.toBeNull());
        expect(result.current.coupon).toBeNull();
        await waitFor(() => expect(lastTotalsBody(stub).coupon).toBeUndefined());
    });

    it('retries the catalogue after a failure', async () => {
        let failures = 1;
        stubFetch((url) => {
            if (url === '/api/products') {
                return failures-- > 0
                    ? jsonResponse({ message: 'boom' }, 500)
                    : jsonResponse({ products: PRODUCTS });
            }

            return jsonResponse(ZERO_TOTALS);
        });

        const { result } = renderHook(() => useBasket());
        await waitFor(() => expect(result.current.catalogueError).not.toBeNull());

        act(() => result.current.retryCatalogue());

        await waitFor(() => expect(result.current.products).toHaveLength(2));
        expect(result.current.catalogueError).toBeNull();
    });
});
