import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import UsuarioForm, { type UsuarioFormData } from './usuario-form';

interface Props {
    jass: { id: number; nombre: string };
    esSuperadmin: boolean;
}

export default function UsuarioCreate({ jass, esSuperadmin }: Props) {
    const { data, setData, post, processing, errors } = useForm<UsuarioFormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        rol: esSuperadmin ? '' : 'tesorero',
    });

    const rutaIndice = esSuperadmin ? route('superadmin.jass.usuarios.index', jass.id) : route('panel.usuarios.index');
    const rutaStore = esSuperadmin ? route('superadmin.jass.usuarios.store', jass.id) : route('panel.usuarios.store');

    const breadcrumbs: BreadcrumbItem[] = esSuperadmin
        ? [
              { title: 'Superadmin', href: '/superadmin' },
              { title: 'JASS', href: '/superadmin/jass' },
              { title: jass.nombre, href: route('superadmin.jass.edit', jass.id) },
              { title: 'Usuarios', href: rutaIndice },
              { title: 'Nuevo', href: '#' },
          ]
        : [
              { title: 'Panel', href: '/panel' },
              { title: 'Usuarios', href: rutaIndice },
              { title: 'Nuevo', href: '#' },
          ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(rutaStore);
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo usuario" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Nuevo usuario de {jass.nombre}</h1>
                <UsuarioForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Crear usuario"
                    esSuperadmin={esSuperadmin}
                    esEdicion={false}
                />
            </div>
        </PanelLayout>
    );
}
