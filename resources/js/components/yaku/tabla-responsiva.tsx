import { cn } from '@/lib/utils';
import { Fragment } from 'react';

export interface ColumnaTabla<T> {
    clave: string;
    etiqueta: string;
    render: (fila: T) => React.ReactNode;
    claseCelda?: string;
}

interface Props<T> {
    columnas: ColumnaTabla<T>[];
    filas: T[];
    obtenerLlave: (fila: T) => string | number;
    acciones?: (fila: T) => React.ReactNode;
    vacio?: React.ReactNode;
}

/**
 * Patrón obligatorio para tablas de datos densas (docs/design-system.md §5,
 * spec §7.3): tabla real en viewport >= md, tarjetas apiladas en < md.
 * Ninguna página implementa su propia versión mobile de una tabla — todas
 * usan este componente.
 */
export default function TablaResponsiva<T>({ columnas, filas, obtenerLlave, acciones, vacio }: Props<T>) {
    if (filas.length === 0) {
        return <div className="text-muted-foreground rounded-md border px-4 py-6 text-center text-sm">{vacio ?? 'No hay registros.'}</div>;
    }

    return (
        <Fragment>
            <div className="hidden overflow-x-auto rounded-md border md:block">
                <table className="w-full text-sm">
                    <thead className="bg-muted text-muted-foreground text-left">
                        <tr>
                            {columnas.map((columna) => (
                                <th key={columna.clave} className="px-4 py-2 font-medium">
                                    {columna.etiqueta}
                                </th>
                            ))}
                            {acciones && <th className="px-4 py-2 font-medium">Acciones</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {filas.map((fila) => (
                            <tr key={obtenerLlave(fila)} className="border-t">
                                {columnas.map((columna) => (
                                    <td key={columna.clave} className={cn('px-4 py-2', columna.claseCelda)}>
                                        {columna.render(fila)}
                                    </td>
                                ))}
                                {acciones && <td className="px-4 py-2">{acciones(fila)}</td>}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-col gap-3 md:hidden">
                {filas.map((fila) => (
                    <div key={obtenerLlave(fila)} className="rounded-md border p-4">
                        <dl className="flex flex-col gap-1">
                            {columnas.map((columna) => (
                                <div key={columna.clave} className="flex items-baseline justify-between gap-2">
                                    <dt className="text-muted-foreground text-xs">{columna.etiqueta}</dt>
                                    <dd className="text-right text-sm">{columna.render(fila)}</dd>
                                </div>
                            ))}
                        </dl>
                        {acciones && <div className="mt-3 flex gap-2">{acciones(fila)}</div>}
                    </div>
                ))}
            </div>
        </Fragment>
    );
}
