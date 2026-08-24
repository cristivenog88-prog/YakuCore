import { Button } from '@/components/ui/button';
import PublicoLayout from '@/layouts/publico-layout';
import { Head, Link, router } from '@inertiajs/react';

interface JassProps {
    id: number;
    nombre: string;
    comunidad: string;
    distrito: string;
}

interface AnuncioFila {
    id: number;
    titulo: string;
    contenido: string;
    fecha_publicacion: string;
}

interface ReunionFila {
    id: number;
    titulo: string;
    fecha: string;
    hora: string;
    lugar: string;
}

interface GastoFila {
    descripcion: string;
    monto: number;
    tipoComprobante: string;
    fecha: string;
}

interface Props {
    jass: JassProps;
    anuncios: AnuncioFila[];
    reuniones: ReunionFila[];
    anio: number;
    transparencia: {
        recaudadoMantenimiento: number;
        recaudadoConsumo: number;
        gastos: GastoFila[];
        totalGastos: number;
    };
}

const etiquetasComprobante: Record<string, string> = {
    boleta: 'Boleta',
    factura: 'Factura',
    declaracion_jurada: 'Declaración jurada',
};

function formatearMonto(monto: number): string {
    return `S/ ${monto.toFixed(2)}`;
}

export default function PortalJassShow({ jass, anuncios, reuniones, anio, transparencia }: Props) {
    const anios = [anio - 1, anio, anio + 1];

    const cambiarAnio = (nuevoAnio: number) => {
        router.get(route('portal.jass.show', jass.id), { anio: nuevoAnio }, { preserveState: true });
    };

    return (
        <PublicoLayout>
            <Head title={jass.nombre} />
            <div className="flex flex-col gap-10">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">{jass.nombre}</h1>
                        <p className="text-muted-foreground text-sm">
                            {jass.comunidad}, {jass.distrito}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route('portal.deuda.create', jass.id)}>Consultar mi deuda</Link>
                    </Button>
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="font-medium">Anuncios</h2>
                    {anuncios.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No hay anuncios publicados.</p>
                    ) : (
                        <div className="flex flex-col gap-3">
                            {anuncios.map((anuncio) => (
                                <div key={anuncio.id} className="rounded-md border p-4">
                                    <p className="font-medium">{anuncio.titulo}</p>
                                    <p className="text-muted-foreground text-xs">{anuncio.fecha_publicacion}</p>
                                    <p className="mt-2 text-sm whitespace-pre-line">{anuncio.contenido}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="flex flex-col gap-3">
                    <h2 className="font-medium">Próximas reuniones</h2>
                    {reuniones.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No hay reuniones programadas.</p>
                    ) : (
                        <div className="flex flex-col gap-3">
                            {reuniones.map((reunion) => (
                                <div key={reunion.id} className="rounded-md border p-4">
                                    <p className="font-medium">{reunion.titulo}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {reunion.fecha} — {reunion.hora.slice(0, 5)} — {reunion.lugar}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="flex flex-col gap-3">
                    <div className="flex items-center justify-between">
                        <h2 className="font-medium">Portal de transparencia</h2>
                        <div className="flex gap-2">
                            {anios.map((opcion) => (
                                <Button
                                    key={opcion}
                                    type="button"
                                    size="sm"
                                    variant={opcion === anio ? 'default' : 'outline'}
                                    onClick={() => cambiarAnio(opcion)}
                                >
                                    {opcion}
                                </Button>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="rounded-md border p-4">
                            <p className="text-muted-foreground text-xs">Recaudado — mantenimiento</p>
                            <p className="font-mono text-xl">{formatearMonto(transparencia.recaudadoMantenimiento)}</p>
                        </div>
                        <div className="rounded-md border p-4">
                            <p className="text-muted-foreground text-xs">Recaudado — consumo</p>
                            <p className="font-mono text-xl">{formatearMonto(transparencia.recaudadoConsumo)}</p>
                        </div>
                        <div className="rounded-md border p-4">
                            <p className="text-muted-foreground text-xs">Gastos del año</p>
                            <p className="font-mono text-xl">{formatearMonto(transparencia.totalGastos)}</p>
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <p className="text-sm font-medium">Gastos {anio}</p>
                        <div className="divide-y rounded-md border">
                            {transparencia.gastos.length === 0 && (
                                <p className="text-muted-foreground p-4 text-sm">No hay gastos registrados en este año.</p>
                            )}
                            {transparencia.gastos.map((gasto, indice) => (
                                <div key={indice} className="flex items-center justify-between gap-4 px-4 py-2 text-sm">
                                    <div>
                                        <p>{gasto.descripcion}</p>
                                        <p className="text-muted-foreground text-xs">
                                            {etiquetasComprobante[gasto.tipoComprobante] ?? gasto.tipoComprobante} — {gasto.fecha}
                                        </p>
                                    </div>
                                    <span className="font-mono">{formatearMonto(gasto.monto)}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </PublicoLayout>
    );
}
