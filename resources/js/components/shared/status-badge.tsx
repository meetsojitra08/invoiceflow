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
                    className={`bg-gray-100 text-gray-700 border-gray-300 font-medium ${className}`}
                >
                    Draft
                </Badge>
            );
        case 'sent':
            return (
                <Badge
                    variant="outline"
                    className={`bg-blue-50 text-blue-700 border-blue-300 font-medium ${className}`}
                >
                    Sent
                </Badge>
            );
        case 'partially_paid':
            return (
                <Badge
                    variant="outline"
                    className={`bg-amber-50 text-amber-700 border-amber-300 font-medium ${className}`}
                >
                    Partially Paid
                </Badge>
            );
        case 'paid':
            return (
                <Badge
                    variant="outline"
                    className={`bg-green-50 text-green-700 border-green-300 font-medium ${className}`}
                >
                    Paid
                </Badge>
            );
        case 'overdue':
            return (
                <Badge
                    variant="outline"
                    className={`bg-red-50 text-red-700 border-red-300 font-medium ${className}`}
                >
                    Overdue
                </Badge>
            );
        case 'void':
            return (
                <Badge
                    variant="outline"
                    className={`bg-gray-100 text-gray-500 border-gray-200 line-through font-medium ${className}`}
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

