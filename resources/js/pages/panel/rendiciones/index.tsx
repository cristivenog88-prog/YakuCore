import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface RendicionFila {
    uuid: string;
    trimestre: number;
    anio: number;
    saldoFinal: number;
    estado: 'borrador' | 'aprobada';
    numeroActa: string | null;
}

interface Props {
    rendiciones: RendicionFila[];
    puedeGenerar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Rendiciones', href: '/panel/rendiciones' },
];

const trimestreRoman = (n: number) => ['', 'I', 'II', 'III', 'IV'][n] ?? '';

const formateaMoneda = (v: number) =>
    'S/ ' + v.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function RendicionesIndex({ rendiciones, puedeGenerar }: Props) {
    const now = new Date();
    const trimestreActual = Math.floor(now.getMonth() / 3) + 1;

    const { data, setData, post, processing } = useForm({
        anio: now.getFullYear(),
        trimestre: trimestreActual,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.rendiciones.store'));
    };

    const columnas: ColumnaTabla<RendicionFila>[] = [
        { clave: 'periodo', etiqueta: 'Trimestre', render: (f) => <span className="font-medium">{trimestreRoman(f.trimestre)} — {f.anio}</span> },
        { clave: 'saldo', etiqueta: 'Saldo final', render: (f) => <span className="font-mono">{formateaMoneda(f.saldoFinal)}</span>, claseCelda: 'text-right' },
        {
            clave: 'estado',
            etiqueta: 'Estado',
            render: (f) => (
                <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        f.estado === 'aprobada'
                            ? 'bg-yaku-rol-presidente-suave text-yaku-rol-presidente'
                            : 'bg-yaku-rol-vocal-suave text-yaku-rol-vocal'
                    }`}
                >
                    {f.estado === 'aprobada' ? 'Aprobada' : 'Borrador'}
                </span>
            ),
        },
        { clave: 'acta', etiqueta: 'N.° de acta', render: (f) => f.numeroActa ?? '—' },
    ];

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Rendiciones" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Rendiciones trimestrales</h1>
                    <p className="text-muted-foreground text-sm">
                        Consolida ingresos, egresos y ejecución por categoría. Requiere checklist completo antes de aprobar.
                    </p>
                </div>

                {puedeGenerar && (
                    <form onSubmit={submit} className="grid gap-4 rounded-md border p-4 md:grid-cols-4">
                        <div className="grid gap-2">
                            <Label htmlFor="anio">Año</Label>
                            <Input id="anio" type="number" min={2020} max={2100} value={data.anio} onChange={(e) => setData('anio', Number(e.target.value))} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="trim">Trimestre</Label>
                            <select
                                id="trim"
                                value={data.trimestre}
                                onChange={(e) => setData('trimestre', Number(e.target.value))}
                                className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                            >
                                <option value={1}>I (ene-mar)</option>
                                <option value={2}>II (abr-jun)</option>
                                <option value={3}>III (jul-sep)</option>
                                <option value={4}>IV (oct-dic)</option>
                            </select>
                        </div>
                        <div className="flex items-end md:col-span-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Generar / actualizar rendición
                            </Button>
                        </div>
                    </form>
                )}

                <TablaResponsiva
                    columnas={columnas}
                    filas={rendiciones}
                    obtenerLlave={(f) => f.uuid}
                    vacio="Aún no has generado ninguna rendición."
                    acciones={(f) => (
                        <Link href={route('panel.rendiciones.show', f.uuid)} className="text-yaku-rol-presidente text-sm underline">
                            Ver detalle
                        </Link>
                    )}
                />
            </div>
        </PanelLayout>
    );
}
