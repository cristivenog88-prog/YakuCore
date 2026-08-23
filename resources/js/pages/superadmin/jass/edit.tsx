import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import JassForm, { type JassFormData } from './jass-form';

interface JassProps {
    id: number;
    nombre: string;
    comunidad: string;
    distrito: string;
    tiene_medidor: boolean;
    tarifa_base_m3: string | null;
    periodicidad_mantenimiento: string;
    dia_vencimiento: number;
}

interface Props {
    jass: JassProps;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin', href: '/superadmin' },
    { title: 'JASS', href: '/superadmin/jass' },
    { title: 'Editar', href: '#' },
];

export default function JassEdit({ jass }: Props) {
    const { data, setData, put, processing, errors } = useForm<JassFormData>({
        nombre: jass.nombre,
        comunidad: jass.comunidad,
        distrito: jass.distrito,
        tiene_medidor: jass.tiene_medidor,
        tarifa_base_m3: jass.tarifa_base_m3 ?? '',
        periodicidad_mantenimiento: jass.periodicidad_mantenimiento,
        dia_vencimiento: String(jass.dia_vencimiento),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('superadmin.jass.update', jass.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${jass.nombre}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Editar {jass.nombre}</h1>
                <JassForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Guardar cambios"
                />
            </div>
        </AppLayout>
    );
}
