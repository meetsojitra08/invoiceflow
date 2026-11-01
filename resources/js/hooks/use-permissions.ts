import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types/auth';

export function usePermissions() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const role = (auth?.role || '').toLowerCase();

    const isOwner = role === 'owner';
    const isAccountant = role === 'accountant';
    const isStaff = role === 'staff';

    return {
        role,
        isOwner,
        isAccountant,
        isStaff,
        canManageTeam: isOwner,
        canDeleteClient: isOwner,
        canManageProducts: isOwner || isAccountant,
        canSendInvoice: isOwner || isAccountant,
        canVoidInvoice: isOwner || isAccountant,
        canRecordPayment: isOwner || isAccountant,
        canViewReports: isOwner || isAccountant,
        canManageApiTokens: isOwner || isAccountant,
    };
}

