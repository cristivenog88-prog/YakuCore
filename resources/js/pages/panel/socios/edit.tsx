import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import SocioForm, { type SocioFormData } from './socio-form';

interface SocioProps {
    id: number;
    uuid: string;
    nombres: string;
    apellidos: string;
    dni: string;
    telefono: string | null;
    direccion: string;
}

interface Props {
    socio: SocioProps;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Socios', href: '/panel/socios' },
    { title: 'Editar', href: '#' },
];

export default function SocioEdit({ socio }: Props) {
    const { data, setData, put, processing, errors } = useForm<SocioFormData>({
        nombres: socio.nombres,
        apellidos: socio.apellidos,
        dni: socio.dni,
        telefono: socio.telefono ?? '',
        direccion: socio.direccion,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('panel.socios.update', socio.uuid));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${socio.nombres} ${socio.apellidos}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">
                    Editar {socio.nombres} {socio.apellidos}
                </h1>
                <SocioForm
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
