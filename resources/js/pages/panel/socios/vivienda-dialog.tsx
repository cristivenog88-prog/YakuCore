import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface ViviendaFormData {
    direccion: string;
    activa: boolean;
    [key: string]: string | boolean;
}

interface Props {
    socioId: number;
    vivienda?: { id: number; direccion: string; activa: boolean };
    trigger: React.ReactNode;
}

export default function ViviendaDialog({ socioId, vivienda, trigger }: Props) {
    const [abierto, setAbierto] = useState(false);
    const esEdicion = vivienda !== undefined;

    const { data, setData, post, put, processing, errors, reset } = useForm<ViviendaFormData>({
        direccion: vivienda?.direccion ?? '',
        activa: vivienda?.activa ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const opciones = {
            onSuccess: () => {
                setAbierto(false);
                reset();
            },
        };

        if (esEdicion) {
            put(route('panel.socios.viviendas.update', [socioId, vivienda.id]), opciones);
        } else {
            post(route('panel.socios.viviendas.store', socioId), opciones);
        }
    };

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{esEdicion ? 'Editar vivienda' : 'Nueva vivienda'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="direccion">Dirección</Label>
                        <Input id="direccion" value={data.direccion} onChange={(e) => setData('direccion', e.target.value)} required />
                        <InputError message={errors.direccion} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="activa"
                            checked={data.activa}
                            onCheckedChange={(valor) => setData('activa', valor === true)}
                        />
                        <Label htmlFor="activa">Vivienda activa</Label>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {esEdicion ? 'Guardar cambios' : 'Crear vivienda'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
