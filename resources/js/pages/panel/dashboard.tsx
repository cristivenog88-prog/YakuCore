import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel',
        href: '/panel',
    },
];

export default function PanelDashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <p className="text-muted-foreground text-sm">Los indicadores de la JASS estarán disponibles en una fase posterior.</p>
            </div>
        </AppLayout>
    );
}
