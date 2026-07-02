import { Product, Totals } from '../types';
import { CouponField } from './CouponField';

interface Props {
    items: string[];
    products: Product[];
    totals: Totals | null;
    isPricing: boolean;
    coupon: string | null;
    couponError: string | null;
    onRemove: (code: string) => void;
    onApplyCoupon: (code: string) => void;
    onRemoveCoupon: () => void;
}

export function BasketSummary({
    items,
    products,
    totals,
    isPricing,
    coupon,
    couponError,
    onRemove,
    onApplyCoupon,
    onRemoveCoupon,
}: Props) {
    const lines = products
        .map((product) => ({
            product,
            quantity: items.filter((code) => code === product.code).length,
        }))
        .filter((line) => line.quantity > 0);

    return (
        <aside className="basket" aria-label="Basket">
            <h2>Basket</h2>

            {lines.length === 0 && <p className="basket__empty">Your basket is empty.</p>}

            {lines.length > 0 && (
                <ul className="basket__lines">
                    {lines.map(({ product, quantity }) => (
                        <li key={product.code}>
                            <span>
                                {product.name} <span className="basket__quantity">×{quantity}</span>
                            </span>
                            <button
                                type="button"
                                aria-label={`Remove ${product.name} from basket`}
                                onClick={() => onRemove(product.code)}
                            >
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {lines.length > 0 && (
                <CouponField coupon={coupon} error={couponError} onApply={onApplyCoupon} onRemove={onRemoveCoupon} />
            )}

            {totals && (
                <dl
                    className={isPricing ? 'basket__totals basket__totals--pricing' : 'basket__totals'}
                    aria-busy={isPricing}
                >
                    <div>
                        <dt>Subtotal</dt>
                        <dd>${totals.subtotal}</dd>
                    </div>
                    <div>
                        <dt>Discount</dt>
                        <dd className={totals.discount !== '0.00' ? 'basket__discount' : ''}>
                            {totals.discount !== '0.00' ? `−$${totals.discount}` : '$0.00'}
                        </dd>
                    </div>
                    {totals.coupon_discount !== '0.00' && (
                        <div>
                            <dt>Coupon</dt>
                            <dd className="basket__discount">−${totals.coupon_discount}</dd>
                        </div>
                    )}
                    <div>
                        <dt>Delivery</dt>
                        <dd>
                            {totals.subtotal !== '0.00' && totals.delivery === '0.00'
                                ? 'Free'
                                : `$${totals.delivery}`}
                        </dd>
                    </div>
                    <div className="basket__total">
                        <dt>Total</dt>
                        <dd>${totals.total}</dd>
                    </div>
                </dl>
            )}
        </aside>
    );
}
