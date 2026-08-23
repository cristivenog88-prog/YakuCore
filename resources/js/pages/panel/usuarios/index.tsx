import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface UsuarioFila {
    id: number;
    name: string;
    email: string;
    rol: string;
}

interface Props {
    jass: { id: number; nombre: string };
    usuarios: UsuarioFila[];
    esSuperadmin: boolean;
}

const etiquetasRol: Record<string, string> = {
    superadmin: 'Superadmin',
    presidente: 'Presidente',
    tesorero: 'Tesorero',
    vocal: 'Vocal',
};

export default function UsuariosIndex({ jass, usuarios, esSuperadmin }: Props) {
    const rutaIndice = esSuperadmin ? route('superadmin.jass.usuarios.index', jass.id) : route('panel.usuarios.index');
    const rutaCrear = esSuperadmin ? route('superadmin.jass.usuarios.create', jass.id) : route('panel.usuarios.create');

    const breadcrumbs: BreadcrumbItem[] = esSuperadmin
        ? [
              { title: 'Superadmin', href: '/superadmin' },
              { title: 'JASS', href: '/superadmin/jass' },
              { title: jass.nombre, href: route('superadmin.jass.edit', jass.id) },
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
            ? route('superadmin.jass.usuarios.destroy', [jass.id, fila.id])
            : route('panel.usuarios.destroy', fila.id);

        router.delete(rutaDestroy);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Usuarios de ${jass.nombre}`} />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">Usuarios de {jass.nombre}</h1>
                    <Button asChild>
                        <Link href={rutaCrear}>Nuevo usuario</Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nombre</th>
                                <th className="px-4 py-2 font-medium">Correo</th>
                                <th className="px-4 py-2 font-medium">Rol</th>
                                <th className="px-4 py-2 font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {usuarios.map((fila) => {
                                const rutaEditar = esSuperadmin
                                    ? route('superadmin.jass.usuarios.edit', [jass.id, fila.id])
                                    : route('panel.usuarios.edit', fila.id);

                                return (
                                    <tr key={fila.id} className="border-t">
                                        <td className="px-4 py-2">{fila.name}</td>
                                        <td className="px-4 py-2">{fila.email}</td>
                                        <td className="px-4 py-2">{etiquetasRol[fila.rol] ?? fila.rol}</td>
                                        <td className="px-4 py-2">
                                            <div className="flex gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={rutaEditar}>Editar</Link>
                                                </Button>
                                                <Button variant="outline" size="sm" onClick={() => eliminar(fila)}>
                                                    Eliminar
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {usuarios.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="text-muted-foreground px-4 py-6 text-center">
                                        Todavía no hay usuarios registrados.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
