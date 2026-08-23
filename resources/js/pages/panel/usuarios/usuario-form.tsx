import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface UsuarioFormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    rol: string;
    [key: string]: string;
}

interface Props {
    data: UsuarioFormData;
    setData: (key: keyof UsuarioFormData, value: string) => void;
    errors: Partial<Record<keyof UsuarioFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
    esSuperadmin: boolean;
    esEdicion: boolean;
}

const rolesPresidente = [
    { value: 'tesorero', label: 'Tesorero' },
    { value: 'vocal', label: 'Vocal' },
];

const rolesSuperadmin = [
    { value: 'presidente', label: 'Presidente' },
    { value: 'tesorero', label: 'Tesorero' },
    { value: 'vocal', label: 'Vocal' },
    { value: 'superadmin', label: 'Superadmin' },
];

export default function UsuarioForm({ data, setData, errors, processing, onSubmit, textoBoton, esSuperadmin, esEdicion }: Props) {
    const opciones = esSuperadmin ? rolesSuperadmin : rolesPresidente;

    return (
        <form onSubmit={onSubmit} className="flex max-w-xl flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Nombre</Label>
                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">Correo electrónico</Label>
                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                <InputError message={errors.email} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="rol">Rol</Label>
                <Select value={data.rol} onValueChange={(valor) => setData('rol', valor)}>
                    <SelectTrigger id="rol">
                        <SelectValue placeholder="Selecciona un rol" />
                    </SelectTrigger>
                    <SelectContent>
                        {opciones.map((opcion) => (
                            <SelectItem key={opcion.value} value={opcion.value}>
                                {opcion.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.rol} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password">{esEdicion ? 'Nueva contraseña (opcional)' : 'Contraseña'}</Label>
                <Input
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    required={!esEdicion}
                />
                <InputError message={errors.password} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    required={!esEdicion}
                />
            </div>

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
