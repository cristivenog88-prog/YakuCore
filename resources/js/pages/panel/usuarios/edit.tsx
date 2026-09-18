import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import UsuarioForm, { type UsuarioFormData } from './usuario-form';

interface UsuarioProps {
    id: number;
    uuid: string;
    name: string;
    email: string;
    rol: string;
}

interface Props {
    jass: { id: number; uuid: string; nombre: string };
    usuario: UsuarioProps;
    esSuperadmin: boolean;
}

export default function UsuarioEdit({ jass, usuario, esSuperadmin }: Props) {
    const { data, setData, put, processing, errors } = useForm<UsuarioFormData>({
        name: usuario.name,
        email: usuario.email,
        password: '',
        password_confirmation: '',
        rol: usuario.rol,
    });

    const rutaIndice = esSuperadmin ? route('superadmin.jass.usuarios.index', jass.uuid) : route('panel.usuarios.index');
    const rutaUpdate = esSuperadmin
        ? route('superadmin.jass.usuarios.update', [jass.uuid, usuario.uuid])
        : route('panel.usuarios.update', usuario.uuid);

    const breadcrumbs: BreadcrumbItem[] = esSuperadmin
        ? [
              { title: 'Superadmin', href: '/superadmin' },
              { title: 'JASS', href: '/superadmin/jass' },
              { title: jass.nombre, href: route('superadmin.jass.edit', jass.uuid) },
              { title: 'Usuarios', href: rutaIndice },
              { title: 'Editar', href: '#' },
          ]
        : [
              { title: 'Panel', href: '/panel' },
              { title: 'Usuarios', href: rutaIndice },
              { title: 'Editar', href: '#' },
          ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(rutaUpdate);
    };

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${usuario.name}`} />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Editar {usuario.name}</h1>
                <UsuarioForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    textoBoton="Guardar cambios"
                    esSuperadmin={esSuperadmin}
                    esEdicion={true}
                />
            </div>
        </PanelLayout>
    );
}
