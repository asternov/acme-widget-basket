import { useBasket } from '../hooks/useBasket';
import { ProductList } from './ProductList';
import { BasketSummary } from './BasketSummary';

export function App() {
    const basket = useBasket();

    return (
        <div className="page">
            <header className="page__header">
                <h1>Acme Widget Co</h1>
                <p>Sales system proof of concept</p>
            </header>

            {basket.catalogueError && (
                <p className="page__error" role="alert">
                    {basket.catalogueError}
                    <button type="button" onClick={basket.retryCatalogue}>
                        Retry
                    </button>
                </p>
            )}
            {basket.pricingError && (
                <p className="page__error" role="alert">
                    {basket.pricingError}
                </p>
            )}

            <main className="page__content">
                <ProductList products={basket.products} onAdd={basket.addItem} />
                <BasketSummary
                    items={basket.items}
                    products={basket.products}
                    totals={basket.totals}
                    isPricing={basket.isPricing}
                    coupon={basket.coupon}
                    couponError={basket.couponError}
                    onRemove={basket.removeItem}
                    onApplyCoupon={basket.applyCoupon}
                    onRemoveCoupon={basket.removeCoupon}
                />
            </main>
        </div>
    );
}
