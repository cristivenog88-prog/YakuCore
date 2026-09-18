import { Button } from '@/components/ui/button';
import SuperadminLayout from '@/layouts/superadmin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import JassForm, { type JassFormData } from './jass-form';

interface JassProps {
    id: number;
    uuid: string;
    nombre: string;
    comunidad: string;
    distrito: string;
    tiene_medidor: boolean;
    usa_poa: boolean | null;
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
        usa_poa: jass.usa_poa ?? false,
        tarifa_base_m3: jass.tarifa_base_m3 ?? '',
        periodicidad_mantenimiento: jass.periodicidad_mantenimiento,
        dia_vencimiento: String(jass.dia_vencimiento),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('superadmin.jass.update', jass.uuid));
    };

    return (
        <SuperadminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${jass.nombre}`} />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold">Editar {jass.nombre}</h1>
                    <Button variant="outline" asChild>
                        <Link href={route('superadmin.jass.usuarios.index', jass.uuid)}>Usuarios de esta JASS</Link>
                    </Button>
                </div>
                <JassForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Guardar cambios"
                />
            </div>
        </SuperadminLayout>
    );
}
