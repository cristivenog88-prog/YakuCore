import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

interface EntidadNombre {
    id: number;
    nombre?: string;
    comunidad?: string;
    distrito?: string;
    nombres?: string;
    apellidos?: string;
    dni?: string;
    direccion?: string;
    codigo?: string;
    name?: string;
}

interface PagoProps {
    id: number;
    numero_recibo: number;
    tipo: string;
    periodo: string;
    concepto: string;
    lectura_anterior: string | null;
    lectura_actual: string | null;
    monto: string;
    fecha_pago: string;
    socio: EntidadNombre;
    vivienda: EntidadNombre;
    medidor: EntidadNombre | null;
    usuario: EntidadNombre;
    jass: EntidadNombre;
}

interface Props {
    pago: PagoProps;
}

export default function ReciboShow({ pago }: Props) {
    const consumo =
        pago.lectura_anterior !== null && pago.lectura_actual !== null
            ? (parseFloat(pago.lectura_actual) - parseFloat(pago.lectura_anterior)).toFixed(2)
            : null;

    return (
        <>
            <Head title={`Recibo N° ${pago.numero_recibo}`} />
            <div className="bg-background text-foreground mx-auto flex min-h-screen max-w-2xl flex-col gap-6 p-6 print:p-0">
                <div className="flex items-center justify-between print:hidden">
                    <Link href={route('panel.dashboard')} className="text-muted-foreground text-sm hover:underline">
                        ← Volver al panel
                    </Link>
                    <Button onClick={() => window.print()}>Imprimir</Button>
                </div>

                <div className="rounded-md border p-8 print:border-0 print:p-0 print:text-black">
                    <header className="flex items-start justify-between border-b pb-4">
                        <div>
                            <h1 className="text-lg font-semibold">{pago.jass.nombre}</h1>
                            <p className="text-muted-foreground text-sm">
                                {pago.jass.comunidad}, {pago.jass.distrito}
                            </p>
                        </div>
                        <div className="text-right">
                            <p className="text-muted-foreground text-xs uppercase">Recibo</p>
                            <p className="font-mono text-xl">N° {pago.numero_recibo}</p>
                        </div>
                    </header>

                    <dl className="grid grid-cols-2 gap-x-6 gap-y-4 py-6 text-sm">
                        <div>
                            <dt className="text-muted-foreground text-xs">Socio</dt>
                            <dd>
                                {pago.socio.nombres} {pago.socio.apellidos}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">DNI</dt>
                            <dd className="font-mono">{pago.socio.dni}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Vivienda</dt>
                            <dd>{pago.vivienda.direccion}</dd>
                        </div>
                        {pago.medidor && (
                            <div>
                                <dt className="text-muted-foreground text-xs">Medidor</dt>
                                <dd className="font-mono">{pago.medidor.codigo}</dd>
                            </div>
                        )}
                        <div>
                            <dt className="text-muted-foreground text-xs">Concepto</dt>
                            <dd>{pago.concepto}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Periodo</dt>
                            <dd className="font-mono">{pago.periodo}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Fecha de pago</dt>
                            <dd>{pago.fecha_pago}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Cobrado por</dt>
                            <dd>{pago.usuario.name}</dd>
                        </div>
                    </dl>

                    {consumo !== null && (
                        <div className="grid grid-cols-3 gap-4 border-t pt-4 font-mono text-sm">
                            <div>
                                <dt className="text-muted-foreground text-xs">Lectura anterior</dt>
                                <dd>{pago.lectura_anterior}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Lectura actual</dt>
                                <dd>{pago.lectura_actual}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Consumo</dt>
                                <dd>{consumo} m³</dd>
                            </div>
                        </div>
                    )}

                    <div className="mt-6 flex items-center justify-between border-t pt-6">
                        <span className="text-muted-foreground text-sm">Total</span>
                        <span className="font-mono text-3xl">S/ {pago.monto}</span>
                    </div>

                    <div className="mt-20 flex justify-end">
                        <div className="text-muted-foreground w-48 border-t pt-2 text-center text-xs">Firma</div>
                    </div>
                </div>
            </div>
        </>
    );
}
