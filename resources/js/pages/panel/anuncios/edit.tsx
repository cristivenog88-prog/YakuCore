import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AnuncioForm, { type AnuncioFormData } from './anuncio-form';

interface AnuncioProps {
    id: number;
    uuid: string;
    titulo: string;
    contenido: string;
    fecha_publicacion: string;
    estado: string;
}

interface Props {
    anuncio: AnuncioProps;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Anuncios', href: '/panel/anuncios' },
    { title: 'Editar', href: '#' },
];

export default function AnuncioEdit({ anuncio }: Props) {
    const { data, setData, put, processing, errors } = useForm<AnuncioFormData>({
        titulo: anuncio.titulo,
        contenido: anuncio.contenido,
        fecha_publicacion: anuncio.fecha_publicacion,
        estado: anuncio.estado,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('panel.anuncios.update', anuncio.uuid));
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${anuncio.titulo}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Editar anuncio</h1>
                <AnuncioForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Guardar cambios"
                />
            </div>
        </PanelLayout>
    );
}
