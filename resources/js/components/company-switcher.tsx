import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types/auth';

export function CompanySwitcher() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currentCompany = auth?.company;
    const companies = auth?.companies || [];

    if (!currentCompany) {
        return null;
    }

    if (companies.length <= 1) {
        return (
            <div className="flex items-center gap-2 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground bg-background">
                <Building2 className="h-3.5 w-3.5 text-muted-foreground" />
                <span className="max-w-[140px] truncate">{currentCompany.name}</span>
            </div>
        );
    }

    const handleSwitch = (companyId: number) => {
        if (companyId === currentCompany.id) return;
        router.post('/companies/switch', { company_id: companyId }, { preserveState: false });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="flex h-8 items-center gap-2 px-2.5 text-xs font-medium"
                >
                    <Building2 className="h-3.5 w-3.5 text-muted-foreground" />
                    <span className="max-w-[120px] truncate">{currentCompany.name}</span>
                    <ChevronsUpDown className="h-3 w-3 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Switch Workspace
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {companies.map((company) => {
                    const isActive = company.id === currentCompany.id;
                    return (
                        <DropdownMenuItem
                            key={company.id}
                            onClick={() => handleSwitch(company.id)}
                            className="flex items-center justify-between text-xs cursor-pointer"
                        >
                            <span className="truncate font-medium">{company.name}</span>
                            <div className="flex items-center gap-1.5">
                                <span className="text-[10px] uppercase text-muted-foreground">
                                    {company.role}
                                </span>
                                {isActive && <Check className="h-3.5 w-3.5 text-primary" />}
                            </div>
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
