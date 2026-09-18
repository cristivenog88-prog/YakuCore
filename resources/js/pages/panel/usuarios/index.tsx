import { Button } from '@/components/ui/button';
import BadgeRol from '@/components/yaku/badge-rol';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem, type Rol } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface UsuarioFila {
    id: number;
    uuid: string;
    name: string;
    email: string;
    rol: Rol;
}

interface Props {
    jass: { id: number; uuid: string; nombre: string };
    usuarios: UsuarioFila[];
    esSuperadmin: boolean;
}

export default function UsuariosIndex({ jass, usuarios, esSuperadmin }: Props) {
    const rutaIndice = esSuperadmin ? route('superadmin.jass.usuarios.index', jass.uuid) : route('panel.usuarios.index');
    const rutaCrear = esSuperadmin ? route('superadmin.jass.usuarios.create', jass.uuid) : route('panel.usuarios.create');

    const breadcrumbs: BreadcrumbItem[] = esSuperadmin
        ? [
              { title: 'Superadmin', href: '/superadmin' },
              { title: 'JASS', href: '/superadmin/jass' },
              { title: jass.nombre, href: route('superadmin.jass.edit', jass.uuid) },
              { title: 'Usuarios', href: rutaIndice },
          ]
        : [
              { title: 'Panel', href: '/panel' },
              { title: 'Usuarios', href: rutaIndice },
          ];

    const eliminar = (fila: UsuarioFila) => {
        if (!confirm(`¿Eliminar a "${fila.name}"? Esta acción requiere confirmar tu contraseña.`)) {
            return;
        }

        const rutaDestroy = esSuperadmin
            ? route('superadmin.jass.usuarios.destroy', [jass.uuid, fila.uuid])
            : route('panel.usuarios.destroy', fila.uuid);

        router.delete(rutaDestroy);
    };

    const columnas: ColumnaTabla<UsuarioFila>[] = [
        { clave: 'name', etiqueta: 'Nombre', render: (fila) => fila.name },
        { clave: 'email', etiqueta: 'Correo', render: (fila) => fila.email },
        { clave: 'rol', etiqueta: 'Rol', render: (fila) => <BadgeRol rol={fila.rol} /> },
    ];

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Usuarios de ${jass.nombre}`} />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">Usuarios de {jass.nombre}</h1>
                    <Button asChild>
                        <Link href={rutaCrear}>Nuevo usuario</Link>
                    </Button>
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={usuarios}
                    obtenerLlave={(fila) => fila.id}
                    vacio="Todavía no hay usuarios registrados."
                    acciones={(fila) => {
                        const rutaEditar = esSuperadmin
                            ? route('superadmin.jass.usuarios.edit', [jass.uuid, fila.uuid])
                            : route('panel.usuarios.edit', fila.uuid);

                        return (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={rutaEditar}>Editar</Link>
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => eliminar(fila)}>
                                    Eliminar
                                </Button>
                            </>
                        );
                    }}
                />
            </div>
        </PanelLayout>
    );
}
