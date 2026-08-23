import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PublicoLayout from '@/layouts/publico-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Welcome() {
    const { data, setData, get, processing } = useForm({ buscar: '' });

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        get(route('buscar-jass', { buscar: data.buscar }));
    };

    return (
        <PublicoLayout>
            <Head title="YakuCore" />
            <div className="flex flex-col gap-10 py-8">
                <section className="flex flex-col gap-4 text-center">
                    <h1 className="text-3xl font-semibold">YakuCore</h1>
                    <p className="text-muted-foreground mx-auto max-w-2xl text-balance">
                        Sistema de gestión para las Juntas Administradoras de Servicios de Saneamiento (JASS) de la zona de Huaraz: cobros,
                        reuniones, anuncios y transparencia de cada organización.
                    </p>
                </section>

                <section className="mx-auto flex w-full max-w-lg flex-col gap-3">
                    <h2 className="text-center text-sm font-medium">Buscar mi JASS</h2>
                    <form onSubmit={buscar} className="flex gap-2">
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
                    <p className="text-muted-foreground text-center text-xs">
                        Encuentra anuncios, reuniones y el portal de transparencia de tu JASS.
                    </p>
                </section>
            </div>
        </PublicoLayout>
    );
}
