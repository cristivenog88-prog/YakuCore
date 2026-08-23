import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface JassFila {
    id: number;
    nombre: string;
    comunidad: string;
    distrito: string;
    tiene_medidor: boolean;
    prefijo: string;
}

interface Props {
    jass: JassFila[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin', href: '/superadmin' },
    { title: 'JASS', href: '/superadmin/jass' },
];

export default function JassIndex({ jass }: Props) {
    const eliminar = (fila: JassFila) => {
        if (!confirm(`¿Eliminar la JASS "${fila.nombre}"? Esta acción requiere confirmar tu contraseña.`)) {
            return;
        }

        router.delete(route('superadmin.jass.destroy', fila.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="JASS" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">JASS registradas</h1>
                    <Button asChild>
                        <Link href={route('superadmin.jass.create')}>Nueva JASS</Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nombre</th>
                                <th className="px-4 py-2 font-medium">Comunidad</th>
                                <th className="px-4 py-2 font-medium">Distrito</th>
                                <th className="px-4 py-2 font-medium">Medidor</th>
                                <th className="px-4 py-2 font-medium">Prefijo</th>
                                <th className="px-4 py-2 font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {jass.map((fila) => (
                                <tr key={fila.id} className="border-t">
                                    <td className="px-4 py-2">{fila.nombre}</td>
                                    <td className="px-4 py-2">{fila.comunidad}</td>
                                    <td className="px-4 py-2">{fila.distrito}</td>
                                    <td className="px-4 py-2">{fila.tiene_medidor ? 'Sí' : 'No'}</td>
                                    <td className="px-4 py-2">{fila.prefijo}</td>
                                    <td className="px-4 py-2">
                                        <div className="flex gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('superadmin.jass.edit', fila.id)}>Editar</Link>
                                            </Button>
                                            <Button variant="outline" size="sm" onClick={() => eliminar(fila)}>
                                                Eliminar
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {jass.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-4 py-6 text-center">
                                        Todavía no hay JASS registradas.
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
