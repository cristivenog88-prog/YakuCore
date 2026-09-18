import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface SocioFormData {
    nombres: string;
    apellidos: string;
    dni: string;
    telefono: string;
    direccion: string;
    [key: string]: string;
}

interface Props {
    data: SocioFormData;
    setData: (key: keyof SocioFormData, value: string) => void;
    errors: Partial<Record<keyof SocioFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
}

export default function SocioForm({ data, setData, errors, processing, onSubmit, textoBoton }: Props) {
    return (
        <form onSubmit={onSubmit} className="flex max-w-xl flex-col gap-6">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="nombres">Nombres</Label>
                    <Input id="nombres" value={data.nombres} onChange={(e) => setData('nombres', e.target.value)} required />
                    <InputError message={errors.nombres} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="apellidos">Apellidos</Label>
                    <Input id="apellidos" value={data.apellidos} onChange={(e) => setData('apellidos', e.target.value)} required />
                    <InputError message={errors.apellidos} />
                </div>
            </div>

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

            <div className="grid gap-2">
                <Label htmlFor="telefono">Teléfono (opcional)</Label>
                <Input id="telefono" value={data.telefono} onChange={(e) => setData('telefono', e.target.value)} />
                <InputError message={errors.telefono} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="direccion">Dirección</Label>
                <Input id="direccion" value={data.direccion} onChange={(e) => setData('direccion', e.target.value)} required />
                <InputError message={errors.direccion} />
            </div>

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
