import { Button } from '@/components/ui/button';
import BadgeEstado from '@/components/yaku/badge-estado';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import MedidorDialog from './medidor-dialog';
import ReemplazarMedidorDialog from './reemplazar-medidor-dialog';
import ViviendaDialog from './vivienda-dialog';

interface MedidorProps {
    id: number;
    vivienda_id: number;
    correlativo: number;
    codigo: string;
    lectura_inicial: string;
    activo: boolean;
}

interface ViviendaFila {
    id: number;
    socio_id: number;
    direccion: string;
    activa: boolean;
    medidor: MedidorProps | null;
}

interface SocioProps {
    id: number;
    nombres: string;
    apellidos: string;
    dni: string;
    telefono: string | null;
    direccion: string;
}

interface Props {
    socio: SocioProps;
    viviendas: ViviendaFila[];
    puedeGestionar: boolean;
    tieneMedidor: boolean;
    prefijoJass: string | null;
}

export default function SocioShow({ socio, viviendas, puedeGestionar, tieneMedidor, prefijoJass }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Panel', href: '/panel' },
        { title: 'Socios', href: '/panel/socios' },
        { title: `${socio.nombres} ${socio.apellidos}`, href: route('panel.socios.show', socio.id) },
    ];

    const eliminarVivienda = (vivienda: ViviendaFila) => {
        if (!confirm('¿Eliminar esta vivienda? Esta acción requiere confirmar tu contraseña.')) {
            return;
        }

        router.delete(route('panel.socios.viviendas.destroy', [socio.id, vivienda.id]));
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
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-lg font-semibold">
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
                        <Button variant="outline" asChild>
                            <Link href={route('panel.socios.edit', socio.id)}>Editar datos</Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    <div className="flex items-center justify-between">
                        <h2 className="font-medium">Viviendas</h2>
                        {puedeGestionar && (
                            <ViviendaDialog socioId={socio.id} trigger={<Button size="sm">Nueva vivienda</Button>} />
                        )}
                    </div>

                    <TablaResponsiva
                        columnas={columnas}
                        filas={viviendas}
                        obtenerLlave={(fila) => fila.id}
                        vacio="Este socio todavía no tiene viviendas registradas."
                        acciones={
                            puedeGestionar
                                ? (fila) => (
                                      <>
                                          <ViviendaDialog
                                              socioId={socio.id}
                                              vivienda={fila}
                                              trigger={
                                                  <Button variant="outline" size="sm">
                                                      Editar
                                                  </Button>
                                              }
                                          />
                                          <Button variant="outline" size="sm" onClick={() => eliminarVivienda(fila)}>
                                              Eliminar
                                          </Button>
                                          {tieneMedidor &&
                                              prefijoJass &&
                                              (fila.medidor ? (
                                                  <>
                                                      <MedidorDialog
                                                          viviendaId={fila.id}
                                                          prefijoJass={prefijoJass}
                                                          medidor={fila.medidor}
                                                          trigger={
                                                              <Button variant="outline" size="sm">
                                                                  Editar medidor
                                                              </Button>
                                                          }
                                                      />
                                                      <ReemplazarMedidorDialog
                                                          viviendaId={fila.id}
                                                          medidorId={fila.medidor.id}
                                                          trigger={
                                                              <Button variant="outline" size="sm">
                                                                  Reemplazar medidor
                                                              </Button>
                                                          }
                                                      />
                                                  </>
                                              ) : (
                                                  <MedidorDialog
                                                      viviendaId={fila.id}
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
            </div>
        </PanelLayout>
    );
}
