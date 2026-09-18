import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface PagoFila {
    id: number;
    tipo: string;
    codigoRecibo: string;
    concepto: string;
    monto: string;
    fecha_pago: string;
    socio: { nombres: string; apellidos: string };
}

interface Estadisticas {
    sociosActivos: number;
    viviendasActivas: number;
    recaudadoMes: { mantenimiento: number; consumo: number };
    recaudadoAnio: { mantenimiento: number; consumo: number };
    gastosMes: number;
    gastosAnio: number;
    balanceAnio: number;
    viviendasMorosas: { cantidad: number; porcentaje: number };
    proximaReunion: { id: number; uuid: string; titulo: string; fecha: string; hora: string } | null;
    ultimosPagos: PagoFila[];
    recaudacionMensual: { mes: number; total: number }[];
}

interface Props {
    supervisando?: { id: number; uuid: string; nombre: string } | null;
    estadisticas: Estadisticas | null;
}

const MESES_ABREV = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

function formatearMonto(monto: number): string {
    return `S/ ${monto.toFixed(2)}`;
}

function TarjetaKpi({ etiqueta, valor, secundario }: { etiqueta: string; valor: string; secundario?: string }) {
    return (
        <div className="rounded-md border p-4">
            <p className="text-muted-foreground text-xs">{etiqueta}</p>
            <p className="font-mono text-2xl">{valor}</p>
            {secundario && <p className="text-muted-foreground mt-1 text-xs">{secundario}</p>}
        </div>
    );
}

export default function PanelDashboard({ supervisando, estadisticas }: Props) {
    const breadcrumbs: BreadcrumbItem[] = supervisando
        ? [
              { title: 'Superadmin', href: '/superadmin' },
              { title: 'JASS', href: '/superadmin/jass' },
              { title: `Supervisar ${supervisando.nombre}`, href: `/supervision/${supervisando.uuid}` },
          ]
        : [{ title: 'Panel', href: '/panel' }];

    const datosGrafico = estadisticas?.recaudacionMensual.map((punto) => ({
        mes: MESES_ABREV[punto.mes - 1],
        total: punto.total,
    }));

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={supervisando ? `Supervisando ${supervisando.nombre}` : 'Panel'} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {supervisando && (
                    <div className="border-input bg-muted rounded-md border px-4 py-2 text-sm">
                        Modo supervisión: estás viendo el panel de <strong>{supervisando.nombre}</strong> de solo lectura.
                    </div>
                )}

                {!estadisticas ? (
                    <p className="text-muted-foreground text-sm">No hay una JASS activa en este contexto.</p>
                ) : (
                    <>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <TarjetaKpi etiqueta="Socios activos" valor={String(estadisticas.sociosActivos)} />
                            <TarjetaKpi etiqueta="Viviendas activas" valor={String(estadisticas.viviendasActivas)} />
                            <TarjetaKpi
                                etiqueta="Viviendas morosas"
                                valor={String(estadisticas.viviendasMorosas.cantidad)}
                                secundario={`${estadisticas.viviendasMorosas.porcentaje}% del total`}
                            />
                            <TarjetaKpi
                                etiqueta="Balance del año"
                                valor={formatearMonto(estadisticas.balanceAnio)}
                                secundario="Recaudado − gastos"
                            />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <TarjetaKpi
                                etiqueta="Recaudado del mes — mantenimiento"
                                valor={formatearMonto(estadisticas.recaudadoMes.mantenimiento)}
                            />
                            <TarjetaKpi etiqueta="Recaudado del mes — consumo" valor={formatearMonto(estadisticas.recaudadoMes.consumo)} />
                            <TarjetaKpi
                                etiqueta="Recaudado del año — mantenimiento"
                                valor={formatearMonto(estadisticas.recaudadoAnio.mantenimiento)}
                            />
                            <TarjetaKpi etiqueta="Recaudado del año — consumo" valor={formatearMonto(estadisticas.recaudadoAnio.consumo)} />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <TarjetaKpi etiqueta="Gastos del mes" valor={formatearMonto(estadisticas.gastosMes)} />
                            <TarjetaKpi etiqueta="Gastos del año" valor={formatearMonto(estadisticas.gastosAnio)} />
                        </div>

                        <div className="rounded-md border p-4">
                            <p className="mb-3 text-sm font-medium">Recaudación mensual del año</p>
                            <div className="h-64 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={datosGrafico}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                                        <XAxis dataKey="mes" tick={{ fontSize: 12 }} />
                                        <YAxis tick={{ fontSize: 12 }} />
                                        <Tooltip formatter={(valor: number) => formatearMonto(valor)} />
                                        <Bar dataKey="total" fill="#065b96" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="rounded-md border p-4">
                                <p className="mb-2 text-sm font-medium">Próxima reunión</p>
                                {estadisticas.proximaReunion ? (
                                    <Link
                                        href={route('panel.reuniones.show', estadisticas.proximaReunion.uuid)}
                                        className="text-sm underline"
                                    >
                                        {estadisticas.proximaReunion.titulo} — {estadisticas.proximaReunion.fecha} {estadisticas.proximaReunion.hora}
                                    </Link>
                                ) : (
                                    <p className="text-muted-foreground text-sm">No hay reuniones programadas.</p>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-col gap-3">
                            <p className="text-sm font-medium">Últimos pagos</p>
                            <div className="divide-y rounded-md border">
                                {estadisticas.ultimosPagos.length === 0 && (
                                    <p className="text-muted-foreground p-4 text-sm">Sin pagos registrados todavía.</p>
                                )}
                                {estadisticas.ultimosPagos.map((pago) => (
                                    <div key={pago.id} className="flex items-center justify-between gap-4 px-4 py-2 text-sm">
                                        <div>
                                            <p className="font-medium">
                                                {pago.socio.apellidos}, {pago.socio.nombres}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {pago.concepto} — Recibo {pago.codigoRecibo} — {pago.fecha_pago}
                                            </p>
                                        </div>
                                        <span className="font-mono">S/ {pago.monto}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </>
                )}
            </div>
        </PanelLayout>
    );
}
