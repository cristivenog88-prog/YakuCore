import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AnuncioForm, { type AnuncioFormData } from './anuncio-form';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Anuncios', href: '/panel/anuncios' },
    { title: 'Nuevo', href: '#' },
];

export default function AnuncioCreate() {
    const { data, setData, post, processing, errors } = useForm<AnuncioFormData>({
        titulo: '',
        contenido: '',
        fecha_publicacion: new Date().toISOString().slice(0, 10),
        estado: 'visible',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('panel.anuncios.store'));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo anuncio" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Nuevo anuncio</h1>
                <AnuncioForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} textoBoton="Publicar anuncio" />
            </div>
        </PanelLayout>
    );
}
