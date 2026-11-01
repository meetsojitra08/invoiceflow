import { useState, useTransition } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Plus, Search, Users } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import DataTable, { Column, PaginatedData } from '@/components/shared/data-table';
import EmptyState from '@/components/shared/empty-state';
import ConfirmDialog from '@/components/shared/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Client } from '@/types/client';

type Props = {
    clients: PaginatedData<Client>;
    filters: {
        search: string;
    };
    can: {
        create: boolean;
        delete: boolean;
    };
};

export default function ClientsIndex({ clients, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [, startTransition] = useTransition();
    const [archiveClient, setArchiveClient] = useState<Client | null>(null);

    const handleSearchChange = (value: string) => {
        setSearch(value);
        startTransition(() => {
            router.get(
                '/clients',
                { search: value },
                { preserveState: true, replace: true }
            );
        });
    };

    const handleConfirmArchive = () => {
        if (!archiveClient) return;

        router.delete(`/clients/${archiveClient.id}`, {
            onSuccess: () => setArchiveClient(null),
        });
    };

    const columns: Column<Client>[] = [
        {
            header: 'Name',
            accessorKey: 'name',
            cell: (client) => (
                <Link
                    href={`/clients/${client.id}`}
                    className="font-medium text-foreground hover:underline"
                >
                    {client.name}
                </Link>
            ),
        },
        {
            header: 'Email',
            accessorKey: 'email',
            cell: (client) => (
                <span className="text-muted-foreground">{client.email || '—'}</span>
            ),
        },
        {
            header: 'Phone',
            accessorKey: 'phone',
            cell: (client) => (
                <span className="text-muted-foreground">{client.phone || '—'}</span>
            ),
        },
        {
            header: 'Reminders',
            cell: (client) => (
                <Badge variant={client.send_reminders ? 'secondary' : 'outline'}>
                    {client.send_reminders ? 'Enabled' : 'Muted'}
                </Badge>
            ),
        },
        {
            header: 'Actions',
            align: 'right',
            cell: (client) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                            <MoreHorizontal className="h-4 w-4" />
                            <span className="sr-only">Open menu</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={`/clients/${client.id}`}>View details</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/clients/${client.id}/edit`}>Edit</Link>
                        </DropdownMenuItem>
                        {can.delete && (
                            <DropdownMenuItem
                                onClick={() => setArchiveClient(client)}
                                className="text-destructive focus:text-destructive"
                            >
                                Archive
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <Head title="Clients" />

            <PageHeader
                title="Clients"
                description="Manage your customer accounts, billing details, and outstanding balances."
                action={
                    can.create && (
                        <Button asChild>
                            <Link href="/clients/create">
                                <Plus className="mr-1.5 h-4 w-4" />
                                Add Client
                            </Link>
                        </Button>
                    )
                }
            />

            <div className="flex items-center justify-between gap-4">
                <div className="relative w-full max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Search clients..."
                        value={search}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pl-9"
                    />
                </div>
            </div>

            <DataTable
                columns={columns}
                data={clients.data}
                pagination={clients}
                keyExtractor={(client) => client.id}
                emptyState={
                    <EmptyState
                        icon={<Users className="h-6 w-6" />}
                        title="No clients found"
                        description={
                            filters.search
                                ? 'No clients match your search query.'
                                : 'Get started by adding your first client.'
                        }
                        action={
                            can.create && !filters.search ? (
                                <Button asChild>
                                    <Link href="/clients/create">
                                        <Plus className="mr-1.5 h-4 w-4" />
                                        Add Client
                                    </Link>
                                </Button>
                            ) : undefined
                        }
                    />
                }
            />

            <ConfirmDialog
                open={archiveClient !== null}
                onOpenChange={(open) => !open && setArchiveClient(null)}
                title="Archive Client"
                description={`Are you sure you want to archive "${archiveClient?.name}"? Archived clients cannot be selected for new invoices, but existing invoices remain intact.`}
                confirmLabel="Archive"
                variant="destructive"
                onConfirm={handleConfirmArchive}
            />
        </div>
    );
}

