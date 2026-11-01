import { formatMoney } from '@/lib/money';

type MoneyProps = {
    amount: number | bigint | null | undefined;
    currency?: string;
    locale?: string;
    className?: string;
};

export default function Money({
    amount,
    currency = 'INR',
    locale = 'en-IN',
    className = '',
}: MoneyProps) {
    return (
        <span className={`tabular-nums font-mono ${className}`}>
            {formatMoney(amount, currency, locale)}
        </span>
    );
}
