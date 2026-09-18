import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface GastoFormData {
    descripcion: string;
    proveedor: string;
    fecha_compra: string;
    monto: string;
    tipo_comprobante: string;
    foto: File | null;
    [key: string]: string | File | null;
}

interface Props {
    data: GastoFormData;
    setData: (key: keyof GastoFormData, value: string | File | null) => void;
    errors: Partial<Record<keyof GastoFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
    yaTieneFoto?: boolean;
}

const tiposComprobante = [
    { value: 'boleta', label: 'Boleta' },
    { value: 'factura', label: 'Factura' },
    { value: 'ticket', label: 'Ticket de compra' },
    { value: 'declaracion_jurada', label: 'Declaración jurada' },
];

export default function GastoForm({ data, setData, errors, processing, onSubmit, textoBoton, yaTieneFoto }: Props) {
    return (
        <form onSubmit={onSubmit} className="flex max-w-xl flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="descripcion">Descripción</Label>
                <Input id="descripcion" value={data.descripcion} onChange={(e) => setData('descripcion', e.target.value)} required />
                <InputError message={errors.descripcion} />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="proveedor">Proveedor</Label>
                    <Input id="proveedor" value={data.proveedor} onChange={(e) => setData('proveedor', e.target.value)} required />
                    <InputError message={errors.proveedor} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="fecha_compra">Fecha de compra</Label>
                    <Input
                        id="fecha_compra"
                        type="date"
                        value={data.fecha_compra}
                        onChange={(e) => setData('fecha_compra', e.target.value)}
                        required
                    />
                    <InputError message={errors.fecha_compra} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="monto">Monto (S/)</Label>
                <Input
                    id="monto"
                    type="number"
                    step="0.01"
                    min="0.01"
                    value={data.monto}
                    onChange={(e) => setData('monto', e.target.value)}
                    required
                />
                <InputError message={errors.monto} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="tipo_comprobante">Tipo de comprobante</Label>
                <Select value={data.tipo_comprobante} onValueChange={(valor) => setData('tipo_comprobante', valor)}>
                    <SelectTrigger id="tipo_comprobante">
                        <SelectValue placeholder="Selecciona un tipo" />
                    </SelectTrigger>
                    <SelectContent>
                        {tiposComprobante.map((tipo) => (
                            <SelectItem key={tipo.value} value={tipo.value}>
                                {tipo.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.tipo_comprobante} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="foto">Foto del comprobante (opcional)</Label>
                <Input
                    id="foto"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    onChange={(e) => setData('foto', e.target.files?.[0] ?? null)}
                />
                {yaTieneFoto && !data.foto && (
                    <p className="text-muted-foreground text-xs">Ya tiene un comprobante adjunto. Sube uno nuevo para reemplazarlo.</p>
                )}
                <InputError message={errors.foto} />
            </div>

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
