import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import GastoForm, { type GastoFormData } from './gasto-form';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Gastos', href: '/panel/gastos' },
    { title: 'Nuevo', href: '#' },
];

export default function GastoCreate() {
    const { data, setData, post, processing, errors } = useForm<GastoFormData>({
        descripcion: '',
        monto: '',
        tipo_comprobante: '',
        foto: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.gastos.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo gasto" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Registrar gasto</h1>
                <GastoForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} textoBoton="Registrar gasto" />
            </div>
        </PanelLayout>
    );
}
