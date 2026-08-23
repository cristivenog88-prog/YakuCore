import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { AppSidebarBase } from '@/components/yaku/app-sidebar-base';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { LayoutGrid, Users } from 'lucide-react';

interface Props {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

/**
 * Layout de presidente/tesorero/vocal, y de superadmin en modo supervisión
 * (docs/design-system.md §7). El propio dashboard indica cuando se está
 * supervisando (prop `supervisando` de panel/dashboard.tsx).
 */
export default function PanelLayout({ children, breadcrumbs = [] }: Props) {
    const { auth } = usePage<SharedData>().props;

    const items: NavItem[] = [{ title: 'Panel', url: '/panel', icon: LayoutGrid }];

    if (auth.user.rol === 'presidente') {
        items.push({ title: 'Usuarios', url: '/panel/usuarios', icon: Users });
    }

    return (
        <AppShell variant="sidebar">
            <AppSidebarBase inicio="/panel" items={items} />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
