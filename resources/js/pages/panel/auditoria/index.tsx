import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface RegistroFila {
    id: number;
    usuario: string;
    accion: string;
    entidad: string;
    entidadId: number;
    fecha: string;
}

interface UsuarioOpcion {
    id: number;
    name: string;
}

interface Props {
    registros: RegistroFila[];
    usuarios: UsuarioOpcion[];
    acciones: string[];
    filtros: { usuario_id?: string; accion?: string; desde?: string; hasta?: string };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Auditoría', href: '/panel/auditoria' },
];

const columnas: ColumnaTabla<RegistroFila>[] = [
    { clave: 'fecha', etiqueta: 'Fecha', render: (fila) => fila.fecha },
    { clave: 'usuario', etiqueta: 'Usuario', render: (fila) => fila.usuario },
    { clave: 'accion', etiqueta: 'Acción', render: (fila) => fila.accion },
    { clave: 'entidad', etiqueta: 'Entidad', render: (fila) => `${fila.entidad} #${fila.entidadId}` },
];

const TODOS = '__todos__';

export default function AuditoriaIndex({ registros, usuarios, acciones, filtros }: Props) {
    const [usuarioId, setUsuarioId] = useState(filtros.usuario_id ?? TODOS);
    const [accion, setAccion] = useState(filtros.accion ?? TODOS);
    const [desde, setDesde] = useState(filtros.desde ?? '');
    const [hasta, setHasta] = useState(filtros.hasta ?? '');

    const filtrar: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('panel.auditoria.index'),
            {
                usuario_id: usuarioId === TODOS ? undefined : usuarioId,
                accion: accion === TODOS ? undefined : accion,
                desde: desde || undefined,
                hasta: hasta || undefined,
            },
            { preserveState: true },
        );
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Auditoría" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Auditoría</h1>
                    <p className="text-muted-foreground text-sm">Bitácora de solo lectura de las acciones registradas en esta JASS.</p>
                </div>

                <form onSubmit={filtrar} className="flex flex-wrap items-end gap-3">
                    <div className="grid gap-1.5">
                        <label className="text-xs font-medium">Usuario</label>
                        <Select value={usuarioId} onValueChange={setUsuarioId}>
                            <SelectTrigger className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TODOS}>Todos</SelectItem>
                                {usuarios.map((usuario) => (
                                    <SelectItem key={usuario.id} value={String(usuario.id)}>
                                        {usuario.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <label className="text-xs font-medium">Acción</label>
                        <Select value={accion} onValueChange={setAccion}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TODOS}>Todas</SelectItem>
                                {acciones.map((opcion) => (
                                    <SelectItem key={opcion} value={opcion}>
                                        {opcion}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <label className="text-xs font-medium">Desde</label>
                        <Input type="date" value={desde} onChange={(e) => setDesde(e.target.value)} className="w-40" />
                    </div>

                    <div className="grid gap-1.5">
                        <label className="text-xs font-medium">Hasta</label>
                        <Input type="date" value={hasta} onChange={(e) => setHasta(e.target.value)} className="w-40" />
                    </div>

                    <Button type="submit" variant="outline">
                        Filtrar
                    </Button>
                </form>

                <TablaResponsiva columnas={columnas} filas={registros} obtenerLlave={(fila) => fila.id} vacio="No hay registros de auditoría." />
            </div>
        </PanelLayout>
    );
}
