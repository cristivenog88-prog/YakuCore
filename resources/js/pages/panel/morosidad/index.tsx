import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import BadgeEstado from '@/components/yaku/badge-estado';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface ViviendaMorosaFila {
    vivienda_id: number;
    socio_id: number;
    socio: string;
    dni: string;
    direccion: string;
    medidor_id: number | null;
    periodosMantenimiento: string[];
    periodosConsumo: string[];
    tipoConsumo: 'consumo_medidor' | 'consumo_directo';
    morosaDesde: string | null;
}

interface Props {
    viviendas: ViviendaMorosaFila[];
    busqueda: string;
    puedeGestionar: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Morosidad', href: '/panel/morosidad' },
];

function antiguedadEnDias(morosaDesde: string | null): number | null {
    if (!morosaDesde) {
        return null;
    }

    const inicio = new Date(`${morosaDesde}T00:00:00`);
    const hoy = new Date();

    return Math.max(0, Math.floor((hoy.getTime() - inicio.getTime()) / (1000 * 60 * 60 * 24)));
}

function rutaCobrarMantenimiento(fila: ViviendaMorosaFila): string {
    const periodo = fila.periodosMantenimiento[0];

    return route('panel.pagos.mantenimiento.create', {
        socio_id: fila.socio_id,
        vivienda_id: fila.vivienda_id,
        periodo,
    });
}

function rutaCobrarConsumo(fila: ViviendaMorosaFila): string | null {
    const periodo = fila.periodosConsumo[0];

    if (fila.tipoConsumo === 'consumo_medidor') {
        if (!fila.medidor_id) {
            return null;
        }

        return route('panel.pagos.consumo-medidor.create', {
            socio_id: fila.socio_id,
            medidor_id: fila.medidor_id,
            periodo,
        });
    }

    return route('panel.pagos.consumo-directo.create', {
        socio_id: fila.socio_id,
        vivienda_id: fila.vivienda_id,
        periodo,
    });
}

const columnas: ColumnaTabla<ViviendaMorosaFila>[] = [
    { clave: 'socio', etiqueta: 'Socio', render: (fila) => fila.socio },
    { clave: 'dni', etiqueta: 'DNI', render: (fila) => <span className="font-mono">{fila.dni}</span> },
    { clave: 'direccion', etiqueta: 'Vivienda', render: (fila) => fila.direccion },
    {
        clave: 'mantenimiento',
        etiqueta: 'Mantenimiento pendiente',
        render: (fila) =>
            fila.periodosMantenimiento.length > 0 ? (
                <span className="font-mono text-xs">{fila.periodosMantenimiento.join(', ')}</span>
            ) : (
                <span className="text-muted-foreground">Al día</span>
            ),
    },
    {
        clave: 'consumo',
        etiqueta: 'Consumo pendiente',
        render: (fila) =>
            fila.periodosConsumo.length > 0 ? (
                <span className="font-mono text-xs">{fila.periodosConsumo.join(', ')}</span>
            ) : (
                <span className="text-muted-foreground">Al día</span>
            ),
    },
    {
        clave: 'antiguedad',
        etiqueta: 'Antigüedad',
        render: (fila) => {
            const dias = antiguedadEnDias(fila.morosaDesde);

            return dias === null ? '—' : <BadgeEstado variante="error">{dias} días</BadgeEstado>;
        },
    },
];

export default function MorosidadIndex({ viviendas, busqueda, puedeGestionar }: Props) {
    const [valorBusqueda, setValorBusqueda] = useState(busqueda);

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('panel.morosidad.index'), { buscar: valorBusqueda }, { preserveState: true });
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Morosidad" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Morosidad</h1>
                    <p className="text-muted-foreground text-sm">Viviendas con periodos de mantenimiento o consumo vencidos e impagos.</p>
                </div>

                <form onSubmit={buscar} className="flex max-w-sm gap-2">
                    <Input
                        value={valorBusqueda}
                        onChange={(e) => setValorBusqueda(e.target.value)}
                        placeholder="Buscar por DNI o apellidos"
                    />
                    <Button type="submit" variant="outline">
                        Buscar
                    </Button>
                </form>

                <TablaResponsiva
                    columnas={columnas}
                    filas={viviendas}
                    obtenerLlave={(fila) => fila.vivienda_id}
                    vacio="No hay viviendas morosas."
                    acciones={
                        puedeGestionar
                            ? (fila) => {
                                  const rutaConsumo = rutaCobrarConsumo(fila);

                                  return (
                                      <>
                                          {fila.periodosMantenimiento.length > 0 && (
                                              <Button variant="outline" size="sm" asChild>
                                                  <Link href={rutaCobrarMantenimiento(fila)}>Cobrar mantenimiento</Link>
                                              </Button>
                                          )}
                                          {fila.periodosConsumo.length > 0 && rutaConsumo && (
                                              <Button variant="outline" size="sm" asChild>
                                                  <Link href={rutaConsumo}>Cobrar consumo</Link>
                                              </Button>
                                          )}
                                      </>
                                  );
                              }
                            : undefined
                    }
                />
            </div>
        </PanelLayout>
    );
}
