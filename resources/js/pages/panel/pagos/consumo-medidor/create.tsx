import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect, useMemo, useRef } from 'react';

interface SocioOpcion {
    id: number;
    nombres: string;
    apellidos: string;
    dni: string;
}

interface MedidorOpcion {
    id: number;
    codigo: string;
    vivienda: { id: number; socio_id: number; direccion: string };
}

interface Previsualizacion {
    lectura_anterior: number;
    monto: number | null;
    valido: boolean;
}

interface Props {
    socios: SocioOpcion[];
    medidores: MedidorOpcion[];
    previsualizacion: Previsualizacion | null;
}

interface FormData {
    socio_id: string;
    medidor_id: string;
    periodo: string;
    lectura_actual: string;
    fecha_pago: string;
    [key: string]: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Cobrar consumo (medidor)', href: '/panel/pagos/consumo-medidor/crear' },
];

/** Precarga desde la Vista Morosidad (?socio_id=&medidor_id=&periodo=). */
function leerPrecarga() {
    const params = new URLSearchParams(window.location.search);

    return {
        socio_id: params.get('socio_id') ?? '',
        medidor_id: params.get('medidor_id') ?? '',
        periodo: params.get('periodo') ?? new Date().toISOString().slice(0, 7),
    };
}

export default function ConsumoMedidorCreate({ socios, medidores, previsualizacion }: Props) {
    const precarga = useMemo(leerPrecarga, []);
    const { data, setData, post, processing, errors } = useForm<FormData>({
        socio_id: precarga.socio_id,
        medidor_id: precarga.medidor_id,
        periodo: precarga.periodo,
        lectura_actual: '',
        fecha_pago: new Date().toISOString().slice(0, 10),
    });

    const medidoresDelSocio = useMemo(
        () => medidores.filter((medidor) => String(medidor.vivienda.socio_id) === data.socio_id),
        [medidores, data.socio_id],
    );

    // El monto y la lectura anterior siempre se piden al servidor (nunca se
    // calculan en el cliente): cada cambio de medidor o lectura actual
    // dispara una recarga parcial de Inertia que solo trae "previsualizacion".
    const primerRenderRef = useRef(true);
    useEffect(() => {
        if (primerRenderRef.current) {
            primerRenderRef.current = false;
            return;
        }

        const temporizador = setTimeout(() => {
            router.reload({
                only: ['previsualizacion'],
                data: { medidor_id: data.medidor_id, lectura_actual: data.lectura_actual },
            });
        }, 400);

        return () => clearTimeout(temporizador);
    }, [data.medidor_id, data.lectura_actual]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.pagos.consumo-medidor.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Cobrar consumo con medidor" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Cobrar consumo (con medidor)</h1>

                <form onSubmit={submit} className="flex max-w-xl flex-col gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="socio_id">Socio</Label>
                        <Select
                            value={data.socio_id}
                            onValueChange={(valor) => {
                                setData('socio_id', valor);
                                setData('medidor_id', '');
                            }}
                        >
                            <SelectTrigger id="socio_id">
                                <SelectValue placeholder="Selecciona un socio" />
                            </SelectTrigger>
                            <SelectContent>
                                {socios.map((socio) => (
                                    <SelectItem key={socio.id} value={String(socio.id)}>
                                        {socio.apellidos}, {socio.nombres} — {socio.dni}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.socio_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="medidor_id">Medidor</Label>
                        <Select value={data.medidor_id} onValueChange={(valor) => setData('medidor_id', valor)} disabled={!data.socio_id}>
                            <SelectTrigger id="medidor_id">
                                <SelectValue placeholder={data.socio_id ? 'Selecciona un medidor' : 'Primero elige un socio'} />
                            </SelectTrigger>
                            <SelectContent>
                                {medidoresDelSocio.map((medidor) => (
                                    <SelectItem key={medidor.id} value={String(medidor.id)}>
                                        {medidor.codigo} — {medidor.vivienda.direccion}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.medidor_id} />
                    </div>

                    {data.medidor_id && (
                        <div className="grid gap-2">
                            <Label>Lectura anterior</Label>
                            <Input value={previsualizacion ? previsualizacion.lectura_anterior : '…'} readOnly disabled className="font-mono" />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="lectura_actual">Lectura actual</Label>
                        <Input
                            id="lectura_actual"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.lectura_actual}
                            onChange={(e) => setData('lectura_actual', e.target.value)}
                            required
                        />
                        <InputError message={errors.lectura_actual} />
                        {previsualizacion && !previsualizacion.valido && (
                            <p className="text-destructive text-sm">La lectura actual no puede ser menor a la lectura anterior.</p>
                        )}
                    </div>

                    {previsualizacion?.valido && previsualizacion.monto !== null && (
                        <div className="grid gap-1">
                            <Label>Monto a cobrar</Label>
                            <p className="font-mono text-2xl">S/ {previsualizacion.monto.toFixed(2)}</p>
                            <p className="text-muted-foreground text-xs">Calculado por el servidor: no se recalcula en el navegador.</p>
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="periodo">Periodo</Label>
                        <Input id="periodo" type="month" value={data.periodo} onChange={(e) => setData('periodo', e.target.value)} required />
                        <InputError message={errors.periodo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="fecha_pago">Fecha de pago</Label>
                        <Input
                            id="fecha_pago"
                            type="date"
                            value={data.fecha_pago}
                            onChange={(e) => setData('fecha_pago', e.target.value)}
                            required
                        />
                        <InputError message={errors.fecha_pago} />
                    </div>

                    <Button type="submit" disabled={processing || previsualizacion?.valido === false} className="w-fit">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Registrar cobro
                    </Button>
                </form>
            </div>
        </PanelLayout>
    );
}
