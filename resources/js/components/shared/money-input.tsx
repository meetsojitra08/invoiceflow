import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';
import { parseMoneyToMinor } from '@/lib/money';

type MoneyInputProps = {
    value: number; // minor units
    onChange: (minor: number) => void;
    id?: string;
    name?: string;
    placeholder?: string;
    currency?: string;
    disabled?: boolean;
    required?: boolean;
    className?: string;
};

export default function MoneyInput({
    value,
    onChange,
    id,
    name,
    placeholder = '0.00',
    currency = 'INR',
    disabled = false,
    required = false,
    className = '',
}: MoneyInputProps) {
    const [displayVal, setDisplayVal] = useState<string>(() => {
        return value !== undefined && value !== null && !isNaN(value)
            ? (value / 100).toFixed(2)
            : '';
    });

    useEffect(() => {
        const currentMinor = parseMoneyToMinor(displayVal);
        if (currentMinor !== value) {
            setDisplayVal(value ? (value / 100).toFixed(2) : '');
        }
    }, [value]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const text = e.target.value;
        setDisplayVal(text);
        const minor = parseMoneyToMinor(text);
        onChange(minor);
    };

    return (
        <div className="relative rounded-md shadow-xs">
            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span className="text-muted-foreground text-sm font-semibold">{currency}</span>
            </div>
            <Input
                type="text"
                id={id}
                name={name}
                value={displayVal}
                onChange={handleChange}
                placeholder={placeholder}
                disabled={disabled}
                required={required}
                className={`pl-12 text-right font-mono tabular-nums ${className}`}
            />
        </div>
    );
}

