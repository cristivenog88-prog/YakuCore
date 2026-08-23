import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import SocioForm, { type SocioFormData } from './socio-form';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Socios', href: '/panel/socios' },
    { title: 'Nuevo', href: '#' },
];

export default function SocioCreate() {
    const { data, setData, post, processing, errors } = useForm<SocioFormData>({
        nombres: '',
        apellidos: '',
        dni: '',
        telefono: '',
        direccion: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.socios.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo socio" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Nuevo socio</h1>
                <SocioForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} textoBoton="Crear socio" />
            </div>
        </PanelLayout>
    );
}
