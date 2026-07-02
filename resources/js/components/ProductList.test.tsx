import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { ProductList } from './ProductList';

const PRODUCTS = [
    { code: 'R01', name: 'Red Widget', price: '32.95' },
    { code: 'G01', name: 'Green Widget', price: '24.95' },
];

describe('ProductList', () => {
    it('renders a card per product and reports adds by product code', async () => {
        const onAdd = vi.fn();
        render(<ProductList products={PRODUCTS} onAdd={onAdd} />);

        expect(screen.getByText('$32.95')).toBeInTheDocument();
        expect(screen.getByText('$24.95')).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Add Green Widget to basket' }));

        expect(onAdd).toHaveBeenCalledWith('G01');
    });
});
