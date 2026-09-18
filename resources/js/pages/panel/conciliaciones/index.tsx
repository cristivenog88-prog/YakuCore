import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface ConciliacionFila {
    uuid: string;
    periodo: string;
    pagosTotal: number;
    gastosTotal: number;
    saldoCalculado: number;
    fondosVerificados: number;
    diferencia: number;
    estado: 'conciliado' | 'revisar';
    firmadoTesorero: boolean;
    firmadoFiscal: boolean;
}

interface Props {
    conciliaciones: ConciliacionFila[];
    puedePreparar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Conciliaciones', href: '/panel/conciliaciones' },
];

const formateaMoneda = (v: number): string =>
    'S/ ' + v.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function ConciliacionesIndex({ conciliaciones, puedePreparar }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        periodo: new Date().toISOString().slice(0, 7),
        efectivo_verificado: 0 as number,
        banco_verificado: 0 as number,
        observaciones: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.conciliaciones.store'), { onSuccess: () => reset() });
    };

    const columnas: ColumnaTabla<ConciliacionFila>[] = [
        { clave: 'periodo', etiqueta: 'Periodo', render: (f) => <span className="font-medium">{f.periodo}</span> },
        { clave: 'pagos', etiqueta: 'Pagos', render: (f) => <span className="font-mono">{formateaMoneda(f.pagosTotal)}</span>, claseCelda: 'text-right' },
        { clave: 'gastos', etiqueta: 'Gastos', render: (f) => <span className="font-mono">{formateaMoneda(f.gastosTotal)}</span>, claseCelda: 'text-right' },
        { clave: 'saldo', etiqueta: 'Saldo', render: (f) => <span className="font-mono">{formateaMoneda(f.saldoCalculado)}</span>, claseCelda: 'text-right' },
        { clave: 'fondos', etiqueta: 'Fondos verif.', render: (f) => <span className="font-mono">{formateaMoneda(f.fondosVerificados)}</span>, claseCelda: 'text-right' },
        {
            clave: 'dif',
            etiqueta: 'Diferencia',
            render: (f) => (
                <span className={`font-mono ${Math.abs(f.diferencia) >= 0.01 ? 'text-yaku-rol-tesorero' : ''}`}>
                    {formateaMoneda(f.diferencia)}
                </span>
            ),
            claseCelda: 'text-right',
        },
        {
            clave: 'estado',
            etiqueta: 'Estado',
            render: (f) => (
                <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        f.estado === 'conciliado'
                            ? 'bg-yaku-rol-presidente-suave text-yaku-rol-presidente'
                            : 'bg-yaku-rol-tesorero-suave text-yaku-rol-tesorero'
                    }`}
                >
                    {f.estado === 'conciliado' ? 'Conciliado' : 'Revisar'}
                </span>
            ),
        },
        {
            clave: 'firmas',
            etiqueta: 'Firmas',
            render: (f) => (
                <span className="text-xs">
                    {f.firmadoTesorero ? 'T ✓' : 'T —'} · {f.firmadoFiscal ? 'F ✓' : 'F —'}
                </span>
            ),
        },
    ];

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Conciliaciones" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Conciliaciones mensuales</h1>
                    <p className="text-muted-foreground text-sm">
                        Compara pagos, gastos y fondos verificados. Se cierra con la firma del Tesorero y del Fiscal.
                    </p>
                </div>

                {puedePreparar && (
                    <form onSubmit={submit} className="grid gap-4 rounded-md border p-4 md:grid-cols-4">
                        <div className="grid gap-2">
                            <Label htmlFor="periodo">Periodo (YYYY-MM)</Label>
                            <Input id="periodo" value={data.periodo} onChange={(e) => setData('periodo', e.target.value)} required />
                            {errors.periodo && <span className="text-xs text-destructive">{errors.periodo}</span>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ef">Efectivo verificado (S/)</Label>
                            <Input
                                id="ef"
                                type="number"
                                step="0.01"
                                min={0}
                                value={data.efectivo_verificado}
                                onChange={(e) => setData('efectivo_verificado', Number(e.target.value))}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="bk">Banco/medios digitales (S/)</Label>
                            <Input
                                id="bk"
                                type="number"
                                step="0.01"
                                min={0}
                                value={data.banco_verificado}
                                onChange={(e) => setData('banco_verificado', Number(e.target.value))}
                            />
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Calcular / actualizar
                            </Button>
                        </div>
                    </form>
                )}

                <TablaResponsiva
                    columnas={columnas}
                    filas={conciliaciones}
                    obtenerLlave={(f) => f.uuid}
                    vacio="Aún no has preparado ninguna conciliación."
                    acciones={(f) => (
                        <Link href={route('panel.conciliaciones.show', f.uuid)} className="text-yaku-rol-presidente text-sm underline">
                            Ver detalle
                        </Link>
                    )}
                />
            </div>
        </PanelLayout>
    );
}
