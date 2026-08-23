import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface PagoProps {
    id: number;
    numero_recibo: number;
    tipo: string;
    periodo: string;
    concepto: string;
    monto: string;
    fecha_pago: string;
}

interface Props {
    pago: PagoProps;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Recibo', href: '#' },
];

export default function ReciboShow({ pago }: Props) {
    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Recibo N° ${pago.numero_recibo}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Recibo N° {pago.numero_recibo}</h1>
                <p className="text-muted-foreground text-sm">{pago.concepto}</p>
                <p className="font-mono text-2xl">S/ {pago.monto}</p>
                <p className="text-muted-foreground text-sm">El diseño imprimible completo llega en una tarea posterior.</p>
            </div>
        </PanelLayout>
    );
}
