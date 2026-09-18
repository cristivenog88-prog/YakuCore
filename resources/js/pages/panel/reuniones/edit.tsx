import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import ReunionForm, { type ReunionFormData } from './reunion-form';

interface ReunionProps {
    id: number;
    uuid: string;
    titulo: string;
    descripcion: string | null;
    fecha: string;
    hora: string;
    lugar: string;
    puntos_tratar: string | null;
}

interface Props {
    reunion: ReunionProps;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Reuniones', href: '/panel/reuniones' },
    { title: 'Editar', href: '#' },
];

export default function ReunionEdit({ reunion }: Props) {
    const { data, setData, put, processing, errors } = useForm<ReunionFormData>({
        titulo: reunion.titulo,
        descripcion: reunion.descripcion ?? '',
        fecha: reunion.fecha,
        hora: reunion.hora,
        lugar: reunion.lugar,
        puntos_tratar: reunion.puntos_tratar ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('panel.reuniones.update', reunion.uuid));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${reunion.titulo}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Editar reunión</h1>
                <ReunionForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Guardar cambios"
                />
            </div>
        </PanelLayout>
    );
}
