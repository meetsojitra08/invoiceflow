import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';

export type Column<T> = {
    header: string;
    accessorKey?: keyof T;
    cell?: (row: T) => ReactNode;
    className?: string;
    align?: 'left' | 'center' | 'right';
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedData<T> = {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
};

type DataTableProps<T> = {
    columns: Column<T>[];
    data: T[];
    pagination?: {
        links: PaginationLink[];
        from?: number | null;
        to?: number | null;
        total?: number;
    };
    emptyState?: ReactNode;
    keyExtractor: (row: T) => string | number;
    className?: string;
};

function cleanPaginationLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

export default function DataTable<T>({
    columns,
    data,
    pagination,
    emptyState,
    keyExtractor,
    className = '',
}: DataTableProps<T>) {
    if (data.length === 0 && emptyState) {
        return <div className={className}>{emptyState}</div>;
    }

    return (
        <div className={`space-y-4 ${className}`}>
            <div className="overflow-x-auto rounded-lg border border-border bg-card">
                <Table>
                    <TableHeader className="bg-muted/50">
                        <TableRow>
                            {columns.map((column, index) => (
                                <TableHead
                                    key={index}
                                    className={`whitespace-nowrap ${
                                        column.align === 'right'
                                            ? 'text-right'
                                            : column.align === 'center'
                                              ? 'text-center'
                                              : 'text-left'
                                    } ${column.className || ''}`}
                                >
                                    {column.header}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    No records found.
                                </TableCell>
                            </TableRow>
                        ) : (
                            data.map((row) => (
                                <TableRow
                                    key={keyExtractor(row)}
                                    className="transition-colors hover:bg-muted/50"
                                >
                                    {columns.map((column, index) => (
                                        <TableCell
                                            key={index}
                                            className={`${
                                                column.align === 'right'
                                                    ? 'text-right tabular-nums'
                                                    : column.align === 'center'
                                                      ? 'text-center'
                                                      : 'text-left'
                                            } ${column.className || ''}`}
                                        >
                                            {column.cell
                                                ? column.cell(row)
                                                : column.accessorKey
                                                  ? (row[column.accessorKey] as ReactNode)
                                                  : null}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            {pagination && pagination.links.length > 3 && (
                <div className="flex flex-col items-center justify-between gap-4 px-2 sm:flex-row">
                    <div className="text-sm text-muted-foreground">
                        {pagination.from !== undefined && pagination.to !== undefined && (
                            <>
                                Showing <span className="font-medium text-foreground">{pagination.from}</span> to{' '}
                                <span className="font-medium text-foreground">{pagination.to}</span> of{' '}
                                <span className="font-medium text-foreground">{pagination.total}</span> results
                            </>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-1">
                        {pagination.links.map((link, i) => {
                            const label = cleanPaginationLabel(link.label);
                            if (!link.url) {
                                return (
                                    <span
                                        key={i}
                                        className="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground/60"
                                    >
                                        {label}
                                    </span>
                                );
                            }

                            return (
                                <Button
                                    key={i}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        preserveState
                                        preserveScroll
                                    >
                                        {label}
                                    </Link>
                                </Button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
