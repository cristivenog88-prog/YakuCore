import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useMemo } from 'react';

interface SocioOpcion {
    id: number;
    nombres: string;
    apellidos: string;
    dni: string;
}

interface ViviendaOpcion {
    id: number;
    socio_id: number;
    direccion: string;
}

interface Props {
    socios: SocioOpcion[];
    viviendas: ViviendaOpcion[];
}

interface FormData {
    socio_id: string;
    vivienda_id: string;
    periodo: string;
    monto: string;
    fecha_pago: string;
    [key: string]: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Cobrar mantenimiento', href: '/panel/pagos/mantenimiento/crear' },
];

export default function MantenimientoCreate({ socios, viviendas }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        socio_id: '',
        vivienda_id: '',
        periodo: new Date().toISOString().slice(0, 7),
        monto: '',
        fecha_pago: new Date().toISOString().slice(0, 10),
    });

    const viviendasDelSocio = useMemo(
        () => viviendas.filter((vivienda) => String(vivienda.socio_id) === data.socio_id),
        [viviendas, data.socio_id],
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.pagos.mantenimiento.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Cobrar mantenimiento" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Cobrar mantenimiento</h1>

                <form onSubmit={submit} className="flex max-w-xl flex-col gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="socio_id">Socio</Label>
                        <Select
                            value={data.socio_id}
                            onValueChange={(valor) => {
                                setData('socio_id', valor);
                                setData('vivienda_id', '');
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
                        <Label htmlFor="vivienda_id">Vivienda</Label>
                        <Select value={data.vivienda_id} onValueChange={(valor) => setData('vivienda_id', valor)} disabled={!data.socio_id}>
                            <SelectTrigger id="vivienda_id">
                                <SelectValue placeholder={data.socio_id ? 'Selecciona una vivienda' : 'Primero elige un socio'} />
                            </SelectTrigger>
                            <SelectContent>
                                {viviendasDelSocio.map((vivienda) => (
                                    <SelectItem key={vivienda.id} value={String(vivienda.id)}>
                                        {vivienda.direccion}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.vivienda_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="periodo">Periodo</Label>
                        <Input id="periodo" type="month" value={data.periodo} onChange={(e) => setData('periodo', e.target.value)} required />
                        <InputError message={errors.periodo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="monto">Monto (S/)</Label>
                        <Input
                            id="monto"
                            type="number"
                            step="0.01"
                            min="0.01"
                            value={data.monto}
                            onChange={(e) => setData('monto', e.target.value)}
                            required
                        />
                        <InputError message={errors.monto} />
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

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Registrar cobro
                    </Button>
                </form>
            </div>
        </PanelLayout>
    );
}
