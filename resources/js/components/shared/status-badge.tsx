import { Badge } from '@/components/ui/badge';

type StatusBadgeProps = {
    status: 'draft' | 'sent' | 'partially_paid' | 'paid' | 'overdue' | 'void' | string;
    className?: string;
};

export default function StatusBadge({ status, className = '' }: StatusBadgeProps) {
    const normalized = (status || '').toLowerCase();

    switch (normalized) {
        case 'draft':
            return (
                <Badge
                    variant="outline"
                    className={`bg-zinc-100 text-zinc-800 border-zinc-300 dark:bg-zinc-800/80 dark:text-zinc-200 dark:border-zinc-700 font-medium ${className}`}
                >
                    Draft
                </Badge>
            );
        case 'sent':
            return (
                <Badge
                    variant="outline"
                    className={`bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800/60 font-medium ${className}`}
                >
                    Sent
                </Badge>
            );
        case 'partially_paid':
            return (
                <Badge
                    variant="outline"
                    className={`bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/60 font-medium ${className}`}
                >
                    Partially Paid
                </Badge>
            );
        case 'paid':
            return (
                <Badge
                    variant="outline"
                    className={`bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60 font-medium ${className}`}
                >
                    Paid
                </Badge>
            );
        case 'overdue':
            return (
                <Badge
                    variant="outline"
                    className={`bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800/60 font-medium ${className}`}
                >
                    Overdue
                </Badge>
            );
        case 'void':
            return (
                <Badge
                    variant="outline"
                    className={`bg-zinc-100 text-zinc-500 border-zinc-200 line-through dark:bg-zinc-800/50 dark:text-zinc-400 dark:border-zinc-700 font-medium ${className}`}
                >
                    Void
                </Badge>
            );
        default:
            return (
                <Badge variant="outline" className={`font-medium ${className}`}>
                    {status}
                </Badge>
            );
    }
}

