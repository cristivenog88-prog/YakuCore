import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PoaForm, { type PoaFormData } from './poa-form';

interface Props {
    anioActual: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'POA', href: '/panel/poa' },
    { title: 'Nuevo', href: '#' },
];

export default function PoaCreate({ anioActual }: Props) {
    const { data, setData, post, processing, errors } = useForm<PoaFormData>({
        otros_ingresos: '',
        actividades: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.poa.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo POA" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Plan Operativo Anual {anioActual}</h1>
                    <p className="text-muted-foreground text-sm">
                        Registra las actividades que la JASS realizará este año: qué se hará, con qué frecuencia, a qué costo, en qué
                        mes y quién es el responsable.
                    </p>
                </div>
                <PoaForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} textoBoton="Guardar POA" />
            </div>
        </PanelLayout>
    );
}
