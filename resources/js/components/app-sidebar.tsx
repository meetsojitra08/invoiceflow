import { Link } from '@inertiajs/react';
import {
    BarChart3,
    CreditCard,
    FileText,
    LayoutGrid,
    Package,
    Settings,
    UserCheck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { isOwner, isAccountant } = usePermissions();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Invoices',
            href: '/invoices',
            icon: FileText,
        },
        {
            title: 'Clients',
            href: '/clients',
            icon: Users,
        },
        {
            title: 'Products',
            href: '/products',
            icon: Package,
        },
        {
            title: 'Payments',
            href: '/payments',
            icon: CreditCard,
        },
        ...((isOwner || isAccountant)
            ? [
                  {
                      title: 'Reports',
                      href: '/reports',
                      icon: BarChart3,
                  },
              ]
            : []),
        ...(isOwner
            ? [
                  {
                      title: 'Team',
                      href: '/team',
                      icon: UserCheck,
                  },
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        ...(isOwner
            ? [
                  {
                      title: 'Settings',
                      href: '/settings/profile',
                      icon: Settings,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {footerNavItems.length > 0 && (
                    <NavFooter items={footerNavItems} className="mt-auto" />
                )}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
