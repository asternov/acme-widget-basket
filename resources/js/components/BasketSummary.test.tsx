import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { BasketSummary } from './BasketSummary';
import { Totals } from '../types';

const PRODUCTS = [
    { code: 'R01', name: 'Red Widget', price: '32.95' },
    { code: 'B01', name: 'Blue Widget', price: '7.95' },
];

const TOTALS: Totals = {
    subtotal: '114.75',
    discount: '16.48',
    coupon_discount: '0.00',
    delivery: '0.00',
    total: '98.27',
};

function renderSummary(overrides: Partial<Parameters<typeof BasketSummary>[0]> = {}) {
    return render(
        <BasketSummary
            items={['B01', 'B01', 'R01', 'R01', 'R01']}
            products={PRODUCTS}
            totals={TOTALS}
            isPricing={false}
            coupon={null}
            couponError={null}
            onRemove={vi.fn()}
            onApplyCoupon={vi.fn()}
            onRemoveCoupon={vi.fn()}
            {...overrides}
        />,
    );
}

describe('BasketSummary', () => {
    it('groups items into lines with quantities', () => {
        renderSummary();

        expect(screen.getByText('Red Widget')).toBeInTheDocument();
        expect(screen.getByText('×3')).toBeInTheDocument();
        expect(screen.getByText('×2')).toBeInTheDocument();
    });

    it('labels delivery as free only when the server priced a non-empty basket at zero', () => {
        renderSummary();
        expect(screen.getByText('Free')).toBeInTheDocument();
    });

    it('shows zero delivery for an empty basket instead of claiming free shipping', () => {
        renderSummary({
            items: [],
            totals: { subtotal: '0.00', discount: '0.00', coupon_discount: '0.00', delivery: '0.00', total: '0.00' },
        });

        expect(screen.queryByText('Free')).not.toBeInTheDocument();
        expect(screen.getByText('Your basket is empty.')).toBeInTheDocument();
    });

    it('shows offer and coupon discounts as negative amounts', () => {
        renderSummary({
            totals: { ...TOTALS, coupon_discount: '9.82', delivery: '2.95', total: '91.40' },
        });

        expect(screen.getByText('−$16.48')).toBeInTheDocument();
        expect(screen.getByText('−$9.82')).toBeInTheDocument();
    });

    it('dims the breakdown while a repricing request is in flight', () => {
        renderSummary({ isPricing: true });

        expect(screen.getByRole('complementary', { name: 'Basket' }).querySelector('[aria-busy]')).toHaveAttribute(
            'aria-busy',
            'true',
        );
    });
});
