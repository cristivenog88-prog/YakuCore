import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { AppSidebarBase } from '@/components/yaku/app-sidebar-base';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { Building2, LayoutGrid } from 'lucide-react';

interface Props {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

/**
 * Layout del dominio administrativo del superadmin (gestión de JASS),
 * distinto del modo supervisión — que usa PanelLayout (docs/design-system.md §7).
 */
export default function SuperadminLayout({ children, breadcrumbs = [] }: Props) {
    const items: NavItem[] = [
        { title: 'Panel', url: '/superadmin', icon: LayoutGrid },
        { title: 'JASS', url: '/superadmin/jass', icon: Building2 },
    ];

    return (
        <AppShell variant="sidebar">
            <AppSidebarBase inicio="/superadmin" items={items} />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
