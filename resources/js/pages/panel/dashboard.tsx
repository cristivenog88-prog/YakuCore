import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    supervisando?: { id: number; nombre: string } | null;
}

export default function PanelDashboard({ supervisando }: Props) {
    const breadcrumbs: BreadcrumbItem[] = supervisando
        ? [
              { title: 'Superadmin', href: '/superadmin' },
              { title: 'JASS', href: '/superadmin/jass' },
              { title: `Supervisar ${supervisando.nombre}`, href: `/supervision/${supervisando.id}` },
          ]
        : [{ title: 'Panel', href: '/panel' }];

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={supervisando ? `Supervisando ${supervisando.nombre}` : 'Panel'} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {supervisando && (
                    <div className="border-input bg-muted rounded-md border px-4 py-2 text-sm">
                        Modo supervisión: estás viendo el panel de <strong>{supervisando.nombre}</strong> de solo lectura.
                    </div>
                )}
                <p className="text-muted-foreground text-sm">Los indicadores de la JASS estarán disponibles en una fase posterior.</p>
            </div>
        </PanelLayout>
    );
}
