import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface SocioOpcion {
    id: number;
    nombres: string;
    apellidos: string;
    dni: string;
}

interface ReunionProps {
    id: number;
    uuid: string;
    titulo: string;
    fecha: string;
}

interface Props {
    reunion: ReunionProps;
    socios: SocioOpcion[];
}

type Estado = 'presente' | 'ausente';

export default function TomarAsistencia({ reunion, socios }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Panel', href: '/panel' },
        { title: 'Reuniones', href: '/panel/reuniones' },
        { title: reunion.titulo, href: route('panel.reuniones.show', reunion.uuid) },
        { title: 'Tomar asistencia', href: '#' },
    ];

    const [estados, setEstados] = useState<Record<number, Estado>>(() => Object.fromEntries(socios.map((socio) => [socio.id, 'presente'])));
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | undefined>();

    const alternar = (socioId: number) => {
        setEstados((previo) => ({ ...previo, [socioId]: previo[socioId] === 'presente' ? 'ausente' : 'presente' }));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!confirm('Al guardar, la asistencia queda bloqueada y la reunión pasa a finalizada. No podrá editarse después. ¿Continuar?')) {
            return;
        }

        const asistencias = socios.map((socio) => ({ socio_id: socio.id, estado: estados[socio.id] }));

        router.post(
            route('panel.reuniones.asistencia.store', reunion.uuid),
            { asistencias },
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errores) => setError(errores.asistencias),
            },
        );
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tomar asistencia — ${reunion.titulo}`} />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Tomar asistencia</h1>
                    <p className="text-muted-foreground text-sm">
                        {reunion.titulo} — {reunion.fecha}
                    </p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-4">
                    <InputError message={error} />

                    <div className="divide-y rounded-md border">
                        {socios.map((socio) => {
                            const presente = estados[socio.id] === 'presente';

                            return (
                                <div key={socio.id} className="flex items-center justify-between gap-4 px-4 py-3">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {socio.apellidos}, {socio.nombres}
                                        </p>
                                        <p className="text-muted-foreground font-mono text-xs">{socio.dni}</p>
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={presente ? 'default' : 'outline'}
                                        onClick={() => alternar(socio.id)}
                                    >
                                        {presente ? 'Presente' : 'Ausente'}
                                    </Button>
                                </div>
                            );
                        })}
                        {socios.length === 0 && <p className="text-muted-foreground p-4 text-sm">Esta JASS no tiene socios registrados.</p>}
                    </div>

                    <Button type="submit" disabled={processing || socios.length === 0} className="w-fit">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Guardar asistencia
                    </Button>
                </form>
            </div>
        </PanelLayout>
    );
}
