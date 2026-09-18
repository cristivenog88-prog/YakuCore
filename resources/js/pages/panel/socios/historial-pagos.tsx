import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface PagoFila {
    id: number;
    uuid: string;
    vivienda_id: number;
    tipo: string;
    codigoRecibo: string;
    periodo: string;
    concepto: string;
    monto: string;
    fecha_pago: string;
}

interface ViviendaOpcion {
    id: number;
    direccion: string;
}

interface Props {
    pagos: PagoFila[];
    viviendas: ViviendaOpcion[];
}

const columnas: ColumnaTabla<PagoFila>[] = [
    { clave: 'codigoRecibo', etiqueta: 'Recibo', render: (fila) => <span className="font-mono">{fila.codigoRecibo}</span> },
    { clave: 'concepto', etiqueta: 'Concepto', render: (fila) => fila.concepto },
    { clave: 'periodo', etiqueta: 'Periodo', render: (fila) => <span className="font-mono">{fila.periodo}</span> },
    { clave: 'fecha_pago', etiqueta: 'Fecha', render: (fila) => fila.fecha_pago },
    { clave: 'monto', etiqueta: 'Monto', render: (fila) => <span className="font-mono">S/ {fila.monto}</span> },
];

function TablaPagos({ pagos }: { pagos: PagoFila[] }) {
    return (
        <TablaResponsiva
            columnas={columnas}
            filas={pagos}
            obtenerLlave={(fila) => fila.id}
            vacio="Sin pagos registrados."
            acciones={(fila) => (
                <Link href={route('panel.recibos.show', fila.uuid)} className="text-sm underline">
                    Ver recibo
                </Link>
            )}
        />
    );
}

/**
 * Historial de pagos por socio y por vivienda (pestañas en la ficha del
 * socio). Ambas vistas parten del mismo listado ya scopeado por JASS y por
 * socio_id que trae el controller — la pestaña "por vivienda" solo filtra
 * en el cliente, sin ida y vuelta al servidor.
 */
export default function HistorialPagos({ pagos, viviendas }: Props) {
    const [viviendaSeleccionada, setViviendaSeleccionada] = useState<number | null>(viviendas[0]?.id ?? null);

    const pagosDeLaVivienda = useMemo(
        () => pagos.filter((pago) => pago.vivienda_id === viviendaSeleccionada),
        [pagos, viviendaSeleccionada],
    );

    return (
        <div className="flex flex-col gap-4">
            <h2 className="font-medium">Historial de pagos</h2>

            <Tabs defaultValue="socio">
                <TabsList>
                    <TabsTrigger value="socio">Por socio</TabsTrigger>
                    <TabsTrigger value="vivienda">Por vivienda</TabsTrigger>
                </TabsList>

                <TabsContent value="socio">
                    <TablaPagos pagos={pagos} />
                </TabsContent>

                <TabsContent value="vivienda">
                    {viviendas.length > 0 ? (
                        <div className="flex flex-col gap-3">
                            <div className="flex flex-wrap gap-2">
                                {viviendas.map((vivienda) => (
                                    <Button
                                        key={vivienda.id}
                                        type="button"
                                        size="sm"
                                        variant={vivienda.id === viviendaSeleccionada ? 'default' : 'outline'}
                                        onClick={() => setViviendaSeleccionada(vivienda.id)}
                                    >
                                        {vivienda.direccion}
                                    </Button>
                                ))}
                            </div>
                            <TablaPagos pagos={pagosDeLaVivienda} />
                        </div>
                    ) : (
                        <p className="text-muted-foreground text-sm">Este socio todavía no tiene viviendas registradas.</p>
                    )}
                </TabsContent>
            </Tabs>
        </div>
    );
}
