import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface Props {
    viviendaId: string;
    medidorId: string;
    trigger: React.ReactNode;
}

interface ReemplazoFormData {
    motivo: string;
    [key: string]: string;
}

export default function ReemplazarMedidorDialog({ viviendaId, medidorId, trigger }: Props) {
    const [abierto, setAbierto] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<ReemplazoFormData>({
        motivo: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('panel.viviendas.medidor.reemplazar', [viviendaId, medidorId]), {
            onSuccess: () => {
                setAbierto(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reemplazar medidor</DialogTitle>
                    <DialogDescription>
                        La lectura base vuelve a 0 y queda registrado en auditoría. Esta acción requiere confirmar tu contraseña.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="motivo">Motivo del reemplazo</Label>
                        <textarea
                            id="motivo"
                            className="border-input bg-background min-h-24 rounded-md border px-3 py-2 text-sm"
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            required
                        />
                        {errors.motivo && <p className="text-destructive text-sm">{errors.motivo}</p>}
                    </div>

                    <DialogFooter>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Confirmar reemplazo
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
