import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface MedidorExistente {
    uuid: string;
    correlativo: number;
    lectura_inicial: string;
    activo: boolean;
}

interface Props {
    viviendaId: string;
    prefijoJass: string;
    medidor?: MedidorExistente;
    trigger: React.ReactNode;
}

interface MedidorFormData {
    correlativo: string;
    lectura_inicial: string;
    activo: boolean;
    [key: string]: string | boolean;
}

export default function MedidorDialog({ viviendaId, prefijoJass, medidor, trigger }: Props) {
    const [abierto, setAbierto] = useState(false);
    const esEdicion = medidor !== undefined;

    const { data, setData, post, put, processing, errors, reset } = useForm<MedidorFormData>({
        correlativo: medidor ? String(medidor.correlativo) : '',
        lectura_inicial: medidor?.lectura_inicial ?? '0',
        activo: medidor?.activo ?? true,
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
            put(route('panel.viviendas.medidor.update', [viviendaId, medidor.uuid]), opciones);
        } else {
            post(route('panel.viviendas.medidor.store', viviendaId), opciones);
        }
    };

    const codigoEnVivo = data.correlativo ? `${prefijoJass}-MED-${data.correlativo}` : `${prefijoJass}-MED-…`;

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{esEdicion ? 'Editar medidor' : 'Nuevo medidor'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    {!esEdicion && (
                        <div className="grid gap-2">
                            <Label htmlFor="correlativo">Correlativo</Label>
                            <Input
                                id="correlativo"
                                type="number"
                                min={1}
                                value={data.correlativo}
                                onChange={(e) => setData('correlativo', e.target.value)}
                                required
                            />
                            <InputError message={errors.correlativo} />
                            <p className="text-muted-foreground font-mono text-sm">{codigoEnVivo}</p>
                        </div>
                    )}

                    {esEdicion && (
                        <p className="font-mono text-sm">
                            {prefijoJass}-MED-{medidor.correlativo}
                        </p>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="lectura_inicial">Lectura inicial</Label>
                        <Input
                            id="lectura_inicial"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.lectura_inicial}
                            onChange={(e) => setData('lectura_inicial', e.target.value)}
                        />
                        <InputError message={errors.lectura_inicial} />
                    </div>

                    {esEdicion && (
                        <div className="flex items-center space-x-3">
                            <Checkbox id="activo" checked={data.activo} onCheckedChange={(valor) => setData('activo', valor === true)} />
                            <Label htmlFor="activo">Medidor activo</Label>
                        </div>
                    )}

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {esEdicion ? 'Guardar cambios' : 'Crear medidor'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
