import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PublicoLayout from '@/layouts/publico-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface JassResultado {
    id: number;
    uuid: string;
    nombre: string;
    comunidad: string;
    distrito: string;
}

interface Props {
    busqueda: string;
    resultados: JassResultado[];
}

export default function BuscarJass({ busqueda, resultados }: Props) {
    const { data, setData, get, processing } = useForm({ buscar: busqueda });

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        get(route('buscar-jass', { buscar: data.buscar }));
    };

    return (
        <PublicoLayout>
            <Head title="Buscar JASS" />
            <div className="flex flex-col gap-6">
                <h1 className="text-lg font-semibold">Buscar mi JASS</h1>

                <form onSubmit={buscar} className="flex max-w-lg gap-2">
                    <Input
                        value={data.buscar}
                        onChange={(e) => setData('buscar', e.target.value)}
                        placeholder="Nombre de la JASS"
                        aria-label="Nombre de la JASS"
                    />
                    <Button type="submit" disabled={processing}>
                        Buscar
                    </Button>
                </form>

                {busqueda !== '' && (
                    <div className="flex flex-col gap-3">
                        {resultados.length === 0 ? (
                            <p className="text-muted-foreground text-sm">No se encontró ninguna JASS con ese nombre.</p>
                        ) : (
                            resultados.map((jass) => (
                                <Link
                                    key={jass.id}
                                    href={route('portal.jass.show', jass.uuid)}
                                    className="rounded-md border p-4 transition-colors hover:bg-muted"
                                >
                                    <p className="font-medium">{jass.nombre}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {jass.comunidad}, {jass.distrito}
                                    </p>
                                </Link>
                            ))
                        )}
                    </div>
                )}
            </div>
        </PublicoLayout>
    );
}
