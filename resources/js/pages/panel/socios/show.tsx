import { Button } from '@/components/ui/button';
import BadgeEstado from '@/components/yaku/badge-estado';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import HistorialPagos from './historial-pagos';
import MedidorDialog from './medidor-dialog';
import ReemplazarMedidorDialog from './reemplazar-medidor-dialog';
import ViviendaDialog from './vivienda-dialog';

interface MedidorProps {
    id: number;
    uuid: string;
    vivienda_id: number;
    correlativo: number;
    codigo: string;
    lectura_inicial: string;
    activo: boolean;
}

interface ViviendaFila {
    id: number;
    uuid: string;
    socio_id: number;
    direccion: string;
    activa: boolean;
    medidor: MedidorProps | null;
}

interface SocioProps {
    id: number;
    uuid: string;
    nombres: string;
    apellidos: string;
    dni: string;
    telefono: string | null;
    direccion: string;
}

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

interface Props {
    socio: SocioProps;
    viviendas: ViviendaFila[];
    puedeGestionar: boolean;
    puedeCobrar: boolean;
    tieneMedidor: boolean;
    prefijoJass: string | null;
    historialPagos: PagoFila[];
}

export default function SocioShow({ socio, viviendas, puedeGestionar, puedeCobrar, tieneMedidor, prefijoJass, historialPagos }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Panel', href: '/panel' },
        { title: 'Socios', href: '/panel/socios' },
        { title: `${socio.nombres} ${socio.apellidos}`, href: route('panel.socios.show', socio.uuid) },
    ];

    const eliminarVivienda = (vivienda: ViviendaFila) => {
        if (!confirm('¿Eliminar esta vivienda? Esta acción requiere confirmar tu contraseña.')) {
            return;
        }

        router.delete(route('panel.socios.viviendas.destroy', [socio.uuid, vivienda.uuid]));
    };

    const columnas: ColumnaTabla<ViviendaFila>[] = [
        { clave: 'direccion', etiqueta: 'Dirección', render: (fila) => fila.direccion },
        {
            clave: 'activa',
            etiqueta: 'Estado',
            render: (fila) => <BadgeEstado variante={fila.activa ? 'exito' : 'neutro'}>{fila.activa ? 'Activa' : 'Inactiva'}</BadgeEstado>,
        },
    ];

    if (tieneMedidor) {
        columnas.push({
            clave: 'medidor',
            etiqueta: 'Medidor',
            render: (fila) =>
                fila.medidor ? (
                    <span className="font-mono">{fila.medidor.codigo}</span>
                ) : (
                    <span className="text-muted-foreground">Sin medidor</span>
                ),
        });
    }

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`${socio.nombres} ${socio.apellidos}`} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <h1 className="text-lg font-semibold wrap-break-word">
                            {socio.nombres} {socio.apellidos}
                        </h1>
                        <dl className="text-muted-foreground mt-1 flex flex-col gap-0.5 text-sm">
                            <div>
                                DNI: <span className="font-mono">{socio.dni}</span>
                            </div>
                            <div>Teléfono: {socio.telefono ?? '—'}</div>
                            <div>Dirección: {socio.direccion}</div>
                        </dl>
                    </div>
                    {puedeGestionar && (
                        <Button variant="outline" asChild className="w-fit">
                            <Link href={route('panel.socios.edit', socio.uuid)}>Editar datos</Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="font-medium">Viviendas</h2>
                        {puedeGestionar && (
                            <ViviendaDialog socioId={socio.uuid} trigger={<Button size="sm">Nueva vivienda</Button>} />
                        )}
                    </div>

                    <TablaResponsiva
                        columnas={columnas}
                        filas={viviendas}
                        obtenerLlave={(fila) => fila.id}
                        vacio="Este socio todavía no tiene viviendas registradas."
                        acciones={
                            puedeGestionar || puedeCobrar
                                ? (fila) => (
                                      <>
                                          {puedeCobrar && (
                                              <Button variant="outline" size="sm" asChild>
                                                  <Link
                                                      href={route('panel.pagos.mantenimiento.create', {
                                                          socio_id: socio.id,
                                                          vivienda_id: fila.id,
                                                      })}
                                                  >
                                                      Cobrar mantenimiento
                                                  </Link>
                                              </Button>
                                          )}
                                          {puedeCobrar && tieneMedidor && fila.medidor && (
                                              <Button variant="outline" size="sm" asChild>
                                                  <Link
                                                      href={route('panel.pagos.consumo-medidor.create', {
                                                          socio_id: socio.id,
                                                          medidor_id: fila.medidor.id,
                                                      })}
                                                  >
                                                      Cobrar consumo
                                                  </Link>
                                              </Button>
                                          )}
                                          {puedeCobrar && !tieneMedidor && (
                                              <Button variant="outline" size="sm" asChild>
                                                  <Link
                                                      href={route('panel.pagos.consumo-directo.create', {
                                                          socio_id: socio.id,
                                                          vivienda_id: fila.id,
                                                      })}
                                                  >
                                                      Cobrar consumo
                                                  </Link>
                                              </Button>
                                          )}
                                          {puedeGestionar && (
                                              <ViviendaDialog
                                                  socioId={socio.uuid}
                                                  vivienda={fila}
                                                  trigger={
                                                      <Button variant="outline" size="sm">
                                                          Editar
                                                      </Button>
                                                  }
                                              />
                                          )}
                                          {puedeGestionar && (
                                              <Button variant="outline" size="sm" onClick={() => eliminarVivienda(fila)}>
                                                  Eliminar
                                              </Button>
                                          )}
                                          {puedeGestionar &&
                                              tieneMedidor &&
                                              prefijoJass &&
                                              (fila.medidor ? (
                                                  <>
                                                      <MedidorDialog
                                                          viviendaId={fila.uuid}
                                                          prefijoJass={prefijoJass}
                                                          medidor={fila.medidor}
                                                          trigger={
                                                              <Button variant="outline" size="sm">
                                                                  Editar medidor
                                                              </Button>
                                                          }
                                                      />
                                                      <ReemplazarMedidorDialog
                                                          viviendaId={fila.uuid}
                                                          medidorId={fila.medidor.uuid}
                                                          trigger={
                                                              <Button variant="outline" size="sm">
                                                                  Reemplazar medidor
                                                              </Button>
                                                          }
                                                      />
                                                  </>
                                              ) : (
                                                  <MedidorDialog
                                                      viviendaId={fila.uuid}
                                                      prefijoJass={prefijoJass}
                                                      trigger={
                                                          <Button variant="outline" size="sm">
                                                              Agregar medidor
                                                          </Button>
                                                      }
                                                  />
                                              ))}
                                      </>
                                  )
                                : undefined
                        }
                    />
                </div>

                <HistorialPagos pagos={historialPagos} viviendas={viviendas.map((v) => ({ id: v.id, direccion: v.direccion }))} />
            </div>
        </PanelLayout>
    );
}
