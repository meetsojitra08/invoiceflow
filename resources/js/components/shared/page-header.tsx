import { ReactNode } from 'react';

type PageHeaderProps = {
    title: string;
    description?: string;
    breadcrumbs?: ReactNode;
    action?: ReactNode;
    className?: string;
};

export default function PageHeader({
    title,
    description,
    breadcrumbs,
    action,
    className = '',
}: PageHeaderProps) {
    return (
        <div className={`mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between ${className}`}>
            <div>
                {breadcrumbs && <div className="mb-2 text-sm">{breadcrumbs}</div>}
                <h1 className="text-2xl font-bold tracking-tight text-foreground">{title}</h1>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                )}
            </div>
            {action && <div className="flex items-center gap-2">{action}</div>}
        </div>
    );
}
