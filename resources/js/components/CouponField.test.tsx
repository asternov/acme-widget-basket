import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { CouponField } from './CouponField';

describe('CouponField', () => {
    it('normalises the code to uppercase before applying', async () => {
        const onApply = vi.fn();
        render(<CouponField coupon={null} error={null} onApply={onApply} onRemove={vi.fn()} />);

        await userEvent.type(screen.getByLabelText('Coupon code'), 'widget10');
        await userEvent.click(screen.getByRole('button', { name: 'Apply' }));

        expect(onApply).toHaveBeenCalledWith('WIDGET10');
    });

    it('shows the applied coupon with a way to remove it', async () => {
        const onRemove = vi.fn();
        render(<CouponField coupon="WIDGET10" error={null} onApply={vi.fn()} onRemove={onRemove} />);

        expect(screen.getByText('WIDGET10')).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Remove coupon WIDGET10' }));

        expect(onRemove).toHaveBeenCalled();
    });
});
