import { useEffect, useState } from 'react';
import { fetchProducts, fetchTotals } from '../api';
import { Product, Totals } from '../types';
import { ProductList } from './ProductList';
import { BasketSummary } from './BasketSummary';

export function App() {
    const [products, setProducts] = useState<Product[]>([]);
    const [items, setItems] = useState<string[]>([]);
    const [totals, setTotals] = useState<Totals | null>(null);
    const [catalogueError, setCatalogueError] = useState<string | null>(null);
    const [pricingError, setPricingError] = useState<string | null>(null);

    useEffect(() => {
        const controller = new AbortController();

        fetchProducts(controller.signal)
            .then(setProducts)
            .catch((cause: unknown) => {
                if (!controller.signal.aborted) {
                    setCatalogueError(`Could not load the product catalogue. ${String(cause)}`);
                }
            });

        return () => controller.abort();
    }, []);

    useEffect(() => {
        const controller = new AbortController();

        fetchTotals(items, controller.signal)
            .then((next) => {
                setTotals(next);
                setPricingError(null);
            })
            .catch((cause: unknown) => {
                if (!controller.signal.aborted) {
                    setPricingError(`Could not price the basket. ${String(cause)}`);
                }
            });

        return () => controller.abort();
    }, [items]);

    const addItem = (code: string) => setItems((current) => [...current, code]);

    const removeItem = (code: string) =>
        setItems((current) => {
            const index = current.lastIndexOf(code);

            if (index === -1) {
                return current;
            }

            return [...current.slice(0, index), ...current.slice(index + 1)];
        });

    return (
        <div className="page">
            <header className="page__header">
                <h1>Acme Widget Co</h1>
                <p>Sales system proof of concept</p>
            </header>

            {catalogueError && (
                <p className="page__error" role="alert">
                    {catalogueError}
                </p>
            )}
            {pricingError && (
                <p className="page__error" role="alert">
                    {pricingError}
                </p>
            )}

            <main className="page__content">
                <ProductList products={products} onAdd={addItem} />
                <BasketSummary items={items} products={products} totals={totals} onRemove={removeItem} />
            </main>
        </div>
    );
}
