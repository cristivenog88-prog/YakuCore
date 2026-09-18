import { Button } from '@/components/ui/button';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

interface Conciliacion {
    uuid: string;
    periodo: string;
    pagosTotal: number;
    gastosTotal: number;
    saldoCalculado: number;
    efectivoVerificado: number;
    bancoVerificado: number;
    fondosVerificados: number;
    diferencia: number;
    estado: 'conciliado' | 'revisar';
    observaciones: string | null;
    tesorero: string | null;
    fiscal: string | null;
    firmadoTesoreroAt: string | null;
    firmadoFiscalAt: string | null;
}

interface Props {
    conciliacion: Conciliacion;
    puedeFirmarTesorero: boolean;
    puedeFirmarFiscal: boolean;
}

const formateaMoneda = (v: number): string =>
    'S/ ' + v.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const formateaFecha = (iso: string | null): string => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('es-PE', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

export default function ConciliacionShow({ conciliacion, puedeFirmarTesorero, puedeFirmarFiscal }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Panel', href: '/panel' },
        { title: 'Conciliaciones', href: '/panel/conciliaciones' },
        { title: conciliacion.periodo, href: route('panel.conciliaciones.show', conciliacion.uuid) },
    ];

    const firmarTesorero = () => router.post(route('panel.conciliaciones.firma-tesorero', conciliacion.uuid));
    const firmarFiscal = () => router.post(route('panel.conciliaciones.firma-fiscal', conciliacion.uuid));

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Conciliación ${conciliacion.periodo}`} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Conciliación — {conciliacion.periodo}</h1>
                        <p className="text-muted-foreground text-sm">
                            Diferencia entre fondos verificados y saldo calculado: {formateaMoneda(conciliacion.diferencia)}
                        </p>
                    </div>
                    <span
                        className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ${
                            conciliacion.estado === 'conciliado'
                                ? 'bg-yaku-rol-presidente-suave text-yaku-rol-presidente'
                                : 'bg-yaku-rol-tesorero-suave text-yaku-rol-tesorero'
                        }`}
                    >
                        {conciliacion.estado === 'conciliado' ? 'CONCILIADO' : 'REVISAR'}
                    </span>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-md border">
                        <div className="border-b bg-muted/40 px-4 py-2 text-sm font-medium">Movimientos del periodo</div>
                        <dl className="divide-y text-sm">
                            <div className="flex justify-between px-4 py-2">
                                <dt>Pagos (ingresos)</dt>
                                <dd className="font-mono">{formateaMoneda(conciliacion.pagosTotal)}</dd>
                            </div>
                            <div className="flex justify-between px-4 py-2">
                                <dt>Gastos (egresos)</dt>
                                <dd className="font-mono">({formateaMoneda(conciliacion.gastosTotal)})</dd>
                            </div>
                            <div className="flex justify-between px-4 py-2 font-medium">
                                <dt>Saldo calculado</dt>
                                <dd className="font-mono">{formateaMoneda(conciliacion.saldoCalculado)}</dd>
                            </div>
                        </dl>
                    </div>
                    <div className="rounded-md border">
                        <div className="border-b bg-muted/40 px-4 py-2 text-sm font-medium">Fondos verificados</div>
                        <dl className="divide-y text-sm">
                            <div className="flex justify-between px-4 py-2">
                                <dt>Efectivo</dt>
                                <dd className="font-mono">{formateaMoneda(conciliacion.efectivoVerificado)}</dd>
                            </div>
                            <div className="flex justify-between px-4 py-2">
                                <dt>Banco / medios digitales</dt>
                                <dd className="font-mono">{formateaMoneda(conciliacion.bancoVerificado)}</dd>
                            </div>
                            <div className="flex justify-between px-4 py-2 font-medium">
                                <dt>Total verificado</dt>
                                <dd className="font-mono">{formateaMoneda(conciliacion.fondosVerificados)}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {conciliacion.observaciones && (
                    <div className="rounded-md border bg-muted/30 px-4 py-3 text-sm">
                        <span className="font-medium">Observaciones: </span>
                        {conciliacion.observaciones}
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-md border p-4">
                        <div className="text-sm font-medium">Firma del Tesorero</div>
                        {conciliacion.firmadoTesoreroAt ? (
                            <p className="text-muted-foreground mt-2 text-sm">
                                Firmado el {formateaFecha(conciliacion.firmadoTesoreroAt)} — {conciliacion.tesorero}
                            </p>
                        ) : (
                            <Button className="mt-3" onClick={firmarTesorero} disabled={!puedeFirmarTesorero}>
                                <CheckCircle2 className="h-4 w-4" />
                                Firmar como Tesorero
                            </Button>
                        )}
                    </div>
                    <div className="rounded-md border p-4">
                        <div className="text-sm font-medium">Firma del Fiscal</div>
                        {conciliacion.firmadoFiscalAt ? (
                            <p className="text-muted-foreground mt-2 text-sm">
                                Firmado el {formateaFecha(conciliacion.firmadoFiscalAt)} — {conciliacion.fiscal}
                            </p>
                        ) : (
                            <Button
                                className="mt-3"
                                onClick={firmarFiscal}
                                disabled={!puedeFirmarFiscal || !conciliacion.firmadoTesoreroAt}
                            >
                                <CheckCircle2 className="h-4 w-4" />
                                Firmar como Fiscal
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </PanelLayout>
    );
}
