import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicoLayout from '@/layouts/publico-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface JassProps {
    id: number;
    uuid: string;
    nombre: string;
    tiene_medidor: boolean;
}

interface ResultadoVivienda {
    vivienda: string;
    periodosMantenimiento: string[];
    periodosConsumo: string[];
}

interface Props {
    jass: JassProps;
    resultado?: ResultadoVivienda[] | null;
}

export default function ConsultaDeuda({ jass, resultado }: Props) {
    const [sinMedidor, setSinMedidor] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        dni: '',
        correlativo_medidor: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('portal.deuda.store', jass.uuid));
    };

    return (
        <PublicoLayout>
            <Head title={`Consultar deuda — ${jass.nombre}`} />
            <div className="mx-auto flex max-w-lg flex-col gap-6">
                <div>
                    <h1 className="text-lg font-semibold">Consultar mi deuda</h1>
                    <p className="text-muted-foreground text-sm">{jass.nombre}</p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="dni">DNI</Label>
                        <Input
                            id="dni"
                            value={data.dni}
                            onChange={(e) => setData('dni', e.target.value)}
                            maxLength={8}
                            inputMode="numeric"
                            required
                        />
                        <InputError message={errors.dni} />
                    </div>

                    {jass.tiene_medidor && (
                        <div className="grid gap-2">
                            <Label htmlFor="correlativo_medidor">Número de medidor</Label>
                            <Input
                                id="correlativo_medidor"
                                value={data.correlativo_medidor}
                                onChange={(e) => setData('correlativo_medidor', e.target.value)}
                                disabled={sinMedidor}
                            />
                            <label className="text-muted-foreground flex items-center gap-2 text-xs">
                                <input
                                    type="checkbox"
                                    checked={sinMedidor}
                                    onChange={(e) => {
                                        setSinMedidor(e.target.checked);
                                        setData('correlativo_medidor', '');
                                    }}
                                />
                                No tengo medidor
                            </label>
                            <InputError message={errors.correlativo_medidor} />
                        </div>
                    )}

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Consultar
                    </Button>
                </form>

                {resultado && (
                    <div className="flex flex-col gap-3">
                        <h2 className="text-sm font-medium">Estado de cuenta</h2>
                        {resultado.map((fila, indice) => (
                            <div key={indice} className="rounded-md border p-4">
                                <p className="font-medium">{fila.vivienda}</p>
                                <dl className="mt-2 flex flex-col gap-1 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground inline">Mantenimiento pendiente: </dt>
                                        <dd className="inline font-mono">
                                            {fila.periodosMantenimiento.length > 0 ? fila.periodosMantenimiento.join(', ') : 'Al día'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground inline">Consumo pendiente: </dt>
                                        <dd className="inline font-mono">
                                            {fila.periodosConsumo.length > 0 ? fila.periodosConsumo.join(', ') : 'Al día'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        ))}
                        {resultado.length === 0 && (
                            <p className="text-muted-foreground text-sm">Este socio no tiene viviendas activas registradas.</p>
                        )}
                    </div>
                )}
            </div>
        </PublicoLayout>
    );
}
