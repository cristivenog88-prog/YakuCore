import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface ReunionFormData {
    titulo: string;
    descripcion: string;
    fecha: string;
    hora: string;
    lugar: string;
    puntos_tratar: string;
    [key: string]: string;
}

interface Props {
    data: ReunionFormData;
    setData: (key: keyof ReunionFormData, value: string) => void;
    errors: Partial<Record<keyof ReunionFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
}

export default function ReunionForm({ data, setData, errors, processing, onSubmit, textoBoton }: Props) {
    return (
        <form onSubmit={onSubmit} className="flex max-w-xl flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="titulo">Título</Label>
                <Input id="titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} required />
                <InputError message={errors.titulo} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="descripcion">Descripción (opcional)</Label>
                <Textarea id="descripcion" value={data.descripcion} onChange={(e) => setData('descripcion', e.target.value)} />
                <InputError message={errors.descripcion} />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="fecha">Fecha</Label>
                    <Input id="fecha" type="date" value={data.fecha} onChange={(e) => setData('fecha', e.target.value)} required />
                    <InputError message={errors.fecha} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="hora">Hora</Label>
                    <Input id="hora" type="time" value={data.hora} onChange={(e) => setData('hora', e.target.value)} required />
                    <InputError message={errors.hora} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="lugar">Lugar</Label>
                <Input id="lugar" value={data.lugar} onChange={(e) => setData('lugar', e.target.value)} required />
                <InputError message={errors.lugar} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="puntos_tratar">Puntos a tratar (opcional)</Label>
                <Textarea id="puntos_tratar" value={data.puntos_tratar} onChange={(e) => setData('puntos_tratar', e.target.value)} />
                <InputError message={errors.puntos_tratar} />
            </div>

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
