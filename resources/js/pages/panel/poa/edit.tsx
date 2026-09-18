import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PoaForm, { type ActividadFormData, type PoaFormData } from './poa-form';

interface Props {
    anioActual: number;
    otrosIngresos: number;
    actividades: ActividadFormData[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'POA', href: '/panel/poa' },
    { title: 'Editar', href: '#' },
];

export default function PoaEdit({ anioActual, otrosIngresos, actividades }: Props) {
    const { data, setData, put, processing, errors } = useForm<PoaFormData>({
        otros_ingresos: String(otrosIngresos),
        actividades: actividades.map((actividad) => ({
            ...actividad,
            frecuencia_anual: String(actividad.frecuencia_anual),
            costo_unitario: String(actividad.costo_unitario),
        })),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('panel.poa.update'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Editar POA" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Editar Plan Operativo Anual {anioActual}</h1>
                    <p className="text-muted-foreground text-sm">Los cambios recalculan la cuota de mantenimiento desde el próximo cobro.</p>
                </div>
                <PoaForm
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
