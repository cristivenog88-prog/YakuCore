import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import GastoForm, { type GastoFormData } from './gasto-form';

interface GastoProps {
    id: number;
    descripcion: string;
    monto: number;
    tipoComprobante: string;
    tieneFoto: boolean;
}

interface Props {
    gasto: GastoProps;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Gastos', href: '/panel/gastos' },
    { title: 'Editar', href: '#' },
];

export default function GastoEdit({ gasto }: Props) {
    const { data, setData, put, processing, errors } = useForm<GastoFormData>({
        descripcion: gasto.descripcion,
        monto: String(gasto.monto),
        tipo_comprobante: gasto.tipoComprobante,
        foto: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('panel.gastos.update', gasto.id));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar gasto — ${gasto.descripcion}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Editar gasto</h1>
                <GastoForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Guardar cambios"
                    yaTieneFoto={gasto.tieneFoto}
                />
            </div>
        </PanelLayout>
    );
}
