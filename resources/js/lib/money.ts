/**
 * Money utilities: integer minor units only.
 * No floating point math used.
 */

export function formatMoney(
    minor: number | bigint | null | undefined,
    currency = 'INR',
    locale = 'en-IN',
): string {
    if (minor === null || minor === undefined || isNaN(Number(minor))) {
        minor = 0;
    }

    const minorNum = typeof minor === 'bigint' ? Number(minor) : minor;
    const major = minorNum / 100;

    try {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency || 'INR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(major);
    } catch {
        return `${currency} ${major.toFixed(2)}`;
    }
}

export function parseMoneyToMinor(input: string | number | null | undefined): number {
    if (input === null || input === undefined) {
        return 0;
    }

    if (typeof input === 'number') {
        if (isNaN(input) || !isFinite(input)) {
            return 0;
        }

        return Math.round(input);
    }

    const trimmed = input.toString().trim();
    if (!trimmed) {
        return 0;
    }

    // Clean whitespace and currency symbols/commas, keeping minus and dots
    const cleaned = trimmed.replace(/[^0-9.-]/g, '');
    if (!cleaned || cleaned === '-' || cleaned === '.') {
        return 0;
    }

    const isNegative = cleaned.startsWith('-');
    const unsigned = isNegative ? cleaned.slice(1) : cleaned;

    const parts = unsigned.split('.');
    if (parts.length === 1) {
        const minor = parseInt(parts[0], 10) * 100;
        return isNegative ? -minor : minor;
    }

    const integerPart = parts[0] === '' ? '0' : parts[0];
    const fractionPart = (parts[1] + '00').slice(0, 2);

    const combinedStr = integerPart + fractionPart;
    const parsed = parseInt(combinedStr, 10);
    const minor = isNaN(parsed) ? 0 : parsed;

    return isNegative ? -minor : minor;
}
