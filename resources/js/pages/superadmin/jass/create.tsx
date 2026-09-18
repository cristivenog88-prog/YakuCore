import SuperadminLayout from '@/layouts/superadmin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import JassForm, { type JassFormData } from './jass-form';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin', href: '/superadmin' },
    { title: 'JASS', href: '/superadmin/jass' },
    { title: 'Nueva', href: '/superadmin/jass/crear' },
];

export default function JassCreate() {
    const { data, setData, post, processing, errors } = useForm<JassFormData>({
        nombre: '',
        comunidad: '',
        distrito: '',
        tiene_medidor: false,
        usa_poa: false,
        tarifa_base_m3: '',
        periodicidad_mantenimiento: '',
        dia_vencimiento: '15',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('superadmin.jass.store'));
    };

    return (
        <SuperadminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva JASS" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Nueva JASS</h1>
                <JassForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Crear JASS"
                />
            </div>
        </SuperadminLayout>
    );
}
