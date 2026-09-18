import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface JassFormData {
    nombre: string;
    comunidad: string;
    distrito: string;
    tiene_medidor: boolean;
    usa_poa: boolean;
    tarifa_base_m3: string;
    periodicidad_mantenimiento: string;
    dia_vencimiento: string;
    [key: string]: string | boolean;
}

interface Props {
    data: JassFormData;
    setData: (key: keyof JassFormData, value: string | boolean) => void;
    errors: Partial<Record<keyof JassFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
}

const periodicidades = [
    { value: 'mensual', label: 'Mensual' },
    { value: 'trimestral', label: 'Trimestral' },
    { value: 'semestral', label: 'Semestral' },
    { value: 'anual', label: 'Anual' },
];

export default function JassForm({ data, setData, errors, processing, onSubmit, textoBoton }: Props) {
    return (
        <form onSubmit={onSubmit} className="flex max-w-xl flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="nombre">Nombre</Label>
                <Input id="nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} required />
                <InputError message={errors.nombre} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="comunidad">Comunidad / centro poblado</Label>
                <Input id="comunidad" value={data.comunidad} onChange={(e) => setData('comunidad', e.target.value)} required />
                <InputError message={errors.comunidad} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="distrito">Distrito</Label>
                <Input id="distrito" value={data.distrito} onChange={(e) => setData('distrito', e.target.value)} required />
                <InputError message={errors.distrito} />
            </div>

            <div className="flex items-center space-x-3">
                <Checkbox
                    id="tiene_medidor"
                    checked={data.tiene_medidor}
                    onCheckedChange={(valor) => setData('tiene_medidor', valor === true)}
                />
                <Label htmlFor="tiene_medidor">Esta JASS trabaja con medición (medidores)</Label>
            </div>

            {data.tiene_medidor && (
                <div className="grid gap-2">
                    <Label htmlFor="tarifa_base_m3">Tarifa base por m³ (S/)</Label>
                    <Input
                        id="tarifa_base_m3"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.tarifa_base_m3}
                        onChange={(e) => setData('tarifa_base_m3', e.target.value)}
                    />
                    <InputError message={errors.tarifa_base_m3} />
                </div>
            )}

            {!data.tiene_medidor && (
                <div className="flex items-center space-x-3">
                    <Checkbox id="usa_poa" checked={data.usa_poa} onCheckedChange={(valor) => setData('usa_poa', valor === true)} />
                    <div className="grid gap-1">
                        <Label htmlFor="usa_poa">Esta JASS elabora su Plan Operativo Anual (POA)</Label>
                        <p className="text-muted-foreground text-xs">
                            Si lo activas, la cuota de mantenimiento se calculará desde el POA del presidente/tesorero en vez de
                            ingresarse a mano al cobrar.
                        </p>
                        <InputError message={errors.usa_poa} />
                    </div>
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="periodicidad_mantenimiento">Periodicidad de mantenimiento</Label>
                <Select
                    value={data.periodicidad_mantenimiento}
                    onValueChange={(valor) => setData('periodicidad_mantenimiento', valor)}
                >
                    <SelectTrigger id="periodicidad_mantenimiento">
                        <SelectValue placeholder="Selecciona una periodicidad" />
                    </SelectTrigger>
                    <SelectContent>
                        {periodicidades.map((opcion) => (
                            <SelectItem key={opcion.value} value={opcion.value}>
                                {opcion.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.periodicidad_mantenimiento} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="dia_vencimiento">Día de vencimiento (1–28)</Label>
                <Input
                    id="dia_vencimiento"
                    type="number"
                    min="1"
                    max="28"
                    value={data.dia_vencimiento}
                    onChange={(e) => setData('dia_vencimiento', e.target.value)}
                    required
                />
                <InputError message={errors.dia_vencimiento} />
            </div>

            <Button type="submit" disabled={processing} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
