import { ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';

type StatCardProps = {
    title: string;
    value: ReactNode;
    description?: ReactNode;
    icon?: ReactNode;
    className?: string;
};

export default function StatCard({
    title,
    value,
    description,
    icon,
    className = '',
}: StatCardProps) {
    return (
        <Card className={`overflow-hidden ${className}`}>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <p className="text-sm font-medium text-muted-foreground">{title}</p>
                    {icon && <div className="text-muted-foreground">{icon}</div>}
                </div>
                <div className="mt-2 text-2xl font-bold tracking-tight text-foreground">
                    {value}
                </div>
                {description && (
                    <div className="mt-1 text-xs text-muted-foreground">{description}</div>
                )}
            </CardContent>
        </Card>
    );
}

