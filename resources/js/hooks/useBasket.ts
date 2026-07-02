import { useCallback, useEffect, useState } from 'react';
import { ApiError, fetchProducts, fetchTotals } from '../api';
import { Product, Totals } from '../types';

export function useBasket() {
    const [products, setProducts] = useState<Product[]>([]);
    const [items, setItems] = useState<string[]>([]);
    const [coupon, setCoupon] = useState<string | null>(null);
    const [totals, setTotals] = useState<Totals | null>(null);
    const [isPricing, setIsPricing] = useState(false);
    const [catalogueError, setCatalogueError] = useState<string | null>(null);
    const [pricingError, setPricingError] = useState<string | null>(null);
    const [couponError, setCouponError] = useState<string | null>(null);
    const [catalogueAttempt, setCatalogueAttempt] = useState(0);

    useEffect(() => {
        const controller = new AbortController();

        setCatalogueError(null);
        fetchProducts(controller.signal)
            .then(setProducts)
            .catch((cause: unknown) => {
                if (!controller.signal.aborted) {
                    setCatalogueError(`Could not load the product catalogue. ${String(cause)}`);
                }
            });

        return () => controller.abort();
    }, [catalogueAttempt]);

    useEffect(() => {
        const controller = new AbortController();

        setIsPricing(true);
        fetchTotals(items, coupon, controller.signal)
            .then((next) => {
                setTotals(next);
                setPricingError(null);
                setIsPricing(false);
            })
            .catch((cause: unknown) => {
                if (controller.signal.aborted) {
                    return;
                }

                setIsPricing(false);

                if (cause instanceof ApiError && cause.errors.coupon) {
                    setCouponError('That coupon code is not valid.');
                    setCoupon(null);

                    return;
                }

                setPricingError(`Could not price the basket. ${String(cause)}`);
            });

        return () => controller.abort();
    }, [items, coupon]);

    const addItem = useCallback((code: string) => setItems((current) => [...current, code]), []);

    const removeItem = useCallback(
        (code: string) =>
            setItems((current) => {
                const index = current.lastIndexOf(code);

                if (index === -1) {
                    return current;
                }

                return [...current.slice(0, index), ...current.slice(index + 1)];
            }),
        [],
    );

    const applyCoupon = useCallback((code: string) => {
        setCouponError(null);
        setCoupon(code);
    }, []);

    const removeCoupon = useCallback(() => {
        setCoupon(null);
        setCouponError(null);
    }, []);

    const retryCatalogue = useCallback(() => setCatalogueAttempt((attempt) => attempt + 1), []);

    return {
        products,
        items,
        coupon,
        totals,
        isPricing,
        catalogueError,
        pricingError,
        couponError,
        addItem,
        removeItem,
        applyCoupon,
        removeCoupon,
        retryCatalogue,
    };
}
