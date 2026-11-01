import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    try {
        const cleaned = date.includes('T') ? date : `${date}T12:00:00Z`;
        const d = new Date(cleaned);
        if (isNaN(d.getTime())) return date;
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(d);
    } catch {
        return date;
    }
}
