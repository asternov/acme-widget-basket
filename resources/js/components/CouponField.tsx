import { FormEvent, useState } from 'react';

interface Props {
    coupon: string | null;
    error: string | null;
    onApply: (code: string) => void;
    onRemove: () => void;
}

export function CouponField({ coupon, error, onApply, onRemove }: Props) {
    const [code, setCode] = useState('');

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const trimmed = code.trim().toUpperCase();
        if (trimmed) {
            onApply(trimmed);
            setCode('');
        }
    };

    if (coupon) {
        return (
            <p className="coupon coupon--applied">
                <span>
                    Coupon <strong>{coupon}</strong> applied
                </span>
                <button type="button" aria-label={`Remove coupon ${coupon}`} onClick={onRemove}>
                    Remove
                </button>
            </p>
        );
    }

    return (
        <form className="coupon" onSubmit={submit}>
            <label htmlFor="coupon-code">Coupon code</label>
            <div className="coupon__row">
                <input
                    id="coupon-code"
                    value={code}
                    onChange={(event) => setCode(event.target.value)}
                    placeholder="e.g. WIDGET10"
                    autoComplete="off"
                />
                <button type="submit" disabled={!code.trim()}>
                    Apply
                </button>
            </div>
            {error && (
                <p className="coupon__error" role="alert">
                    {error}
                </p>
            )}
        </form>
    );
}
