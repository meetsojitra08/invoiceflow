import { useState, useTransition } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Package, Plus, Search } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import DataTable, { Column, PaginatedData } from '@/components/shared/data-table';
import EmptyState from '@/components/shared/empty-state';
import Money from '@/components/shared/money';
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
import type { Product } from '@/types/product';

type Props = {
    products: PaginatedData<Product>;
    filters: {
        search: string;
    };
    can: {
        create: boolean;
        manage: boolean;
    };
};

export default function ProductsIndex({ products, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [, startTransition] = useTransition();
    const [deleteProduct, setDeleteProduct] = useState<Product | null>(null);

    const handleSearchChange = (value: string) => {
        setSearch(value);
        startTransition(() => {
            router.get(
                '/products',
                { search: value },
                { preserveState: true, replace: true }
            );
        });
    };

    const handleConfirmDelete = () => {
        if (!deleteProduct) return;

        router.delete(`/products/${deleteProduct.id}`, {
            onSuccess: () => setDeleteProduct(null),
        });
    };

    const columns: Column<Product>[] = [
        {
            header: 'Product / Service',
            accessorKey: 'name',
            cell: (product) => (
                <div>
                    <span className="font-medium text-foreground">{product.name}</span>
                    {product.description && (
                        <p className="text-xs text-muted-foreground truncate max-w-md">
                            {product.description}
                        </p>
                    )}
                </div>
            ),
        },
        {
            header: 'Unit Price',
            align: 'right',
            cell: (product) => <Money amount={product.unit_price} />,
        },
        {
            header: 'Tax Rate',
            align: 'right',
            cell: (product) => (
                <span className="font-mono tabular-nums text-muted-foreground">
                    {Number(product.tax_rate).toFixed(2)}%
                </span>
            ),
        },
        {
            header: 'Status',
            cell: (product) => (
                <Badge variant={product.is_active ? 'default' : 'outline'}>
                    {product.is_active ? 'Active' : 'Inactive'}
                </Badge>
            ),
        },
        ...(can.manage
            ? [
                  {
                      header: 'Actions',
                      align: 'right' as const,
                      cell: (product: Product) => (
                          <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                  <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                      <MoreHorizontal className="h-4 w-4" />
                                      <span className="sr-only">Open menu</span>
                                  </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                  <DropdownMenuItem asChild>
                                      <Link href={`/products/${product.id}/edit`}>Edit</Link>
                                  </DropdownMenuItem>
                                  <DropdownMenuItem
                                      onClick={() => setDeleteProduct(product)}
                                      className="text-destructive focus:text-destructive"
                                  >
                                      Delete
                                  </DropdownMenuItem>
                              </DropdownMenuContent>
                          </DropdownMenu>
                      ),
                  },
              ]
            : []),
    ];

    return (
        <div className="space-y-6">
            <Head title="Products & Services" />

            <PageHeader
                title="Products & Services"
                description="Manage your product catalog, default prices, and tax rates."
                action={
                    can.create && (
                        <Button asChild>
                            <Link href="/products/create">
                                <Plus className="mr-1.5 h-4 w-4" />
                                Add Product
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
                        placeholder="Search products..."
                        value={search}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pl-9"
                    />
                </div>
            </div>

            <DataTable
                columns={columns}
                data={products.data}
                pagination={products}
                keyExtractor={(product) => product.id}
                emptyState={
                    <EmptyState
                        icon={<Package className="h-6 w-6" />}
                        title="No products found"
                        description={
                            filters.search
                                ? 'No products match your search query.'
                                : 'Get started by creating your first billable item.'
                        }
                        action={
                            can.create && !filters.search ? (
                                <Button asChild>
                                    <Link href="/products/create">
                                        <Plus className="mr-1.5 h-4 w-4" />
                                        Add Product
                                    </Link>
                                </Button>
                            ) : undefined
                        }
                    />
                }
            />

            <ConfirmDialog
                open={deleteProduct !== null}
                onOpenChange={(open) => !open && setDeleteProduct(null)}
                title="Delete Product"
                description={`Are you sure you want to delete "${deleteProduct?.name}"? It will no longer appear in new invoice pickers, but existing invoices will keep their item snapshots.`}
                confirmLabel="Delete"
                variant="destructive"
                onConfirm={handleConfirmDelete}
            />
        </div>
    );
}

