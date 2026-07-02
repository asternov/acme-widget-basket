import { Product } from '../types';

interface Props {
    products: Product[];
    onAdd: (code: string) => void;
}

export function ProductList({ products, onAdd }: Props) {
    return (
        <section className="products" aria-label="Products">
            {products.map((product) => (
                <article className="product-card" key={product.code}>
                    <div className="product-card__info">
                        <h2>{product.name}</h2>
                        <span className="product-card__code">{product.code}</span>
                    </div>
                    <div className="product-card__actions">
                        <span className="product-card__price">${product.price}</span>
                        <button
                            type="button"
                            aria-label={`Add ${product.name} to basket`}
                            onClick={() => onAdd(product.code)}
                        >
                            Add to basket
                        </button>
                    </div>
                </article>
            ))}
        </section>
    );
}
