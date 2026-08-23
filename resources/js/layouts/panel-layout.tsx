import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { AppSidebarBase } from '@/components/yaku/app-sidebar-base';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, Building2, LayoutGrid, UserRound, Users } from 'lucide-react';

interface Props {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

/**
 * Layout de presidente/tesorero/vocal, de superadmin en modo supervisión, y
 * layout por defecto para páginas de cuenta (Settings) accesibles por
 * cualquier rol (docs/design-system.md §7) — por eso el ítem "inicio" es
 * consciente del rol: nunca enlaza a una ruta que el usuario tiene prohibida.
 */
export default function PanelLayout({ children, breadcrumbs = [] }: Props) {
    const { auth } = usePage<SharedData>().props;
    const esSuperadmin = auth.user.rol === 'superadmin';
    const inicio = esSuperadmin ? '/superadmin' : '/panel';

    const items: NavItem[] = [{ title: esSuperadmin ? 'JASS' : 'Panel', url: inicio, icon: esSuperadmin ? Building2 : LayoutGrid }];

    if (!esSuperadmin) {
        items.push({ title: 'Socios', url: '/panel/socios', icon: UserRound });
        items.push({ title: 'Morosidad', url: '/panel/morosidad', icon: AlertTriangle });
    }

    if (auth.user.rol === 'presidente') {
        items.push({ title: 'Usuarios', url: '/panel/usuarios', icon: Users });
    }

    return (
        <AppShell variant="sidebar">
            <AppSidebarBase inicio={inicio} items={items} />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
