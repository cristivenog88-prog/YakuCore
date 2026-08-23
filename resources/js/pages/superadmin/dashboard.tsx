import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Superadmin',
        href: '/superadmin',
    },
];

export default function SuperadminDashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Superadmin" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <p className="text-muted-foreground text-sm">El listado de JASS y la supervisión estarán disponibles en una fase posterior.</p>
            </div>
        </AppLayout>
    );
}
