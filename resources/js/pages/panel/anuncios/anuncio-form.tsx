import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface AnuncioFormData {
    titulo: string;
    contenido: string;
    fecha_publicacion: string;
    estado: string;
    [key: string]: string;
}

interface Props {
    data: AnuncioFormData;
    setData: (key: keyof AnuncioFormData, value: string) => void;
    errors: Partial<Record<keyof AnuncioFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
}

export default function AnuncioForm({ data, setData, errors, processing, onSubmit, textoBoton }: Props) {
    return (
        <form onSubmit={onSubmit} className="flex max-w-xl flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="titulo">Título</Label>
                <Input id="titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} required />
                <InputError message={errors.titulo} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="contenido">Contenido</Label>
                <Textarea id="contenido" value={data.contenido} onChange={(e) => setData('contenido', e.target.value)} required />
                <InputError message={errors.contenido} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="fecha_publicacion">Fecha de publicación</Label>
                <Input
                    id="fecha_publicacion"
                    type="date"
                    value={data.fecha_publicacion}
                    onChange={(e) => setData('fecha_publicacion', e.target.value)}
                    required
                />
                <InputError message={errors.fecha_publicacion} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="estado">Estado</Label>
                <Select value={data.estado} onValueChange={(valor) => setData('estado', valor)}>
                    <SelectTrigger id="estado">
                        <SelectValue placeholder="Selecciona un estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="visible">Visible</SelectItem>
                        <SelectItem value="inactivo">Inactivo</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.estado} />
            </div>

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
