import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import ReunionForm, { type ReunionFormData } from './reunion-form';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Reuniones', href: '/panel/reuniones' },
    { title: 'Nueva', href: '#' },
];

export default function ReunionCreate() {
    const { data, setData, post, processing, errors } = useForm<ReunionFormData>({
        titulo: '',
        descripcion: '',
        fecha: '',
        hora: '',
        lugar: '',
        puntos_tratar: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.reuniones.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva reunión" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Nueva reunión</h1>
                <ReunionForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} textoBoton="Crear reunión" />
            </div>
        </PanelLayout>
    );
}
