import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface ActividadFormData {
    categoria: string;
    descripcion: string;
    frecuencia_anual: string;
    costo_unitario: string;
    mes: string;
    responsable: string;
}

export interface PoaFormData {
    otros_ingresos: string;
    actividades: ActividadFormData[];
    [key: string]: string | ActividadFormData[];
}

interface Props {
    data: PoaFormData;
    setData: (key: keyof PoaFormData, value: string | ActividadFormData[]) => void;
    errors: Record<string, string>;
    processing: boolean;
    onSubmit: FormEventHandler;
    textoBoton: string;
}

const categorias = [
    { value: 'operacion', label: 'Operación' },
    { value: 'mantenimiento', label: 'Mantenimiento' },
    { value: 'administracion', label: 'Administración' },
    { value: 'reposicion_equipos', label: 'Reposición de equipos' },
    { value: 'rehabilitaciones_menores', label: 'Rehabilitaciones menores' },
];

const actividadVacia: ActividadFormData = {
    categoria: '',
    descripcion: '',
    frecuencia_anual: '1',
    costo_unitario: '',
    mes: '',
    responsable: '',
};

function formatearMonto(monto: number): string {
    return `S/ ${monto.toFixed(2)}`;
}

export default function PoaForm({ data, setData, errors, processing, onSubmit, textoBoton }: Props) {
    const actualizarActividad = (indice: number, campo: keyof ActividadFormData, valor: string) => {
        const nuevas = data.actividades.map((actividad, i) => (i === indice ? { ...actividad, [campo]: valor } : actividad));
        setData('actividades', nuevas);
    };

    const agregarActividad = () => {
        setData('actividades', [...data.actividades, { ...actividadVacia }]);
    };

    const quitarActividad = (indice: number) => {
        setData(
            'actividades',
            data.actividades.filter((_, i) => i !== indice),
        );
    };

    const presupuestoEstimado = data.actividades.reduce((total, actividad) => {
        const frecuencia = Number(actividad.frecuencia_anual) || 0;
        const costo = Number(actividad.costo_unitario) || 0;

        return total + frecuencia * costo;
    }, 0);

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-6">
            <div className="flex flex-col gap-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="font-medium">Actividades del POA</h2>
                    <Button type="button" variant="outline" size="sm" onClick={agregarActividad}>
                        <Plus className="h-4 w-4" />
                        Agregar actividad
                    </Button>
                </div>
                <InputError message={errors.actividades} />

                {data.actividades.length === 0 && (
                    <p className="text-muted-foreground rounded-md border px-4 py-6 text-center text-sm">
                        Todavía no agregaste ninguna actividad. Agrega al menos una para poder guardar el POA.
                    </p>
                )}

                <div className="flex flex-col gap-4">
                    {data.actividades.map((actividad, indice) => (
                        <div key={indice} className="flex flex-col gap-4 rounded-md border p-4">
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-muted-foreground text-xs">Actividad {indice + 1}</span>
                                <Button type="button" variant="outline" size="sm" onClick={() => quitarActividad(indice)}>
                                    <Trash2 className="h-4 w-4" />
                                    Quitar
                                </Button>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor={`categoria-${indice}`}>Categoría</Label>
                                    <Select
                                        value={actividad.categoria}
                                        onValueChange={(valor) => actualizarActividad(indice, 'categoria', valor)}
                                    >
                                        <SelectTrigger id={`categoria-${indice}`}>
                                            <SelectValue placeholder="Selecciona una categoría" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categorias.map((opcion) => (
                                                <SelectItem key={opcion.value} value={opcion.value}>
                                                    {opcion.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors[`actividades.${indice}.categoria`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`descripcion-${indice}`}>Descripción</Label>
                                    <Input
                                        id={`descripcion-${indice}`}
                                        value={actividad.descripcion}
                                        onChange={(e) => actualizarActividad(indice, 'descripcion', e.target.value)}
                                        placeholder="Ej. Clorar el agua"
                                        required
                                    />
                                    <InputError message={errors[`actividades.${indice}.descripcion`]} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor={`frecuencia-${indice}`}>Cantidad al año</Label>
                                    <Input
                                        id={`frecuencia-${indice}`}
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={actividad.frecuencia_anual}
                                        onChange={(e) => actualizarActividad(indice, 'frecuencia_anual', e.target.value)}
                                        placeholder="Ej. 12 (meses), 57.5 (kg)"
                                        required
                                    />
                                    <InputError message={errors[`actividades.${indice}.frecuencia_anual`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`costo-${indice}`}>Costo unitario (S/)</Label>
                                    <Input
                                        id={`costo-${indice}`}
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={actividad.costo_unitario}
                                        onChange={(e) => actualizarActividad(indice, 'costo_unitario', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors[`actividades.${indice}.costo_unitario`]} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor={`mes-${indice}`}>Mes (opcional)</Label>
                                    <Input
                                        id={`mes-${indice}`}
                                        value={actividad.mes}
                                        onChange={(e) => actualizarActividad(indice, 'mes', e.target.value)}
                                        placeholder="Ej. Todo el año, Marzo"
                                    />
                                    <InputError message={errors[`actividades.${indice}.mes`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`responsable-${indice}`}>Responsable (opcional)</Label>
                                    <Input
                                        id={`responsable-${indice}`}
                                        value={actividad.responsable}
                                        onChange={(e) => actualizarActividad(indice, 'responsable', e.target.value)}
                                        placeholder="Ej. Tesorero"
                                    />
                                    <InputError message={errors[`actividades.${indice}.responsable`]} />
                                </div>
                            </div>

                            <p className="text-muted-foreground text-xs">
                                Subtotal:{' '}
                                <span className="font-mono">
                                    {formatearMonto((Number(actividad.frecuencia_anual) || 0) * (Number(actividad.costo_unitario) || 0))}
                                </span>
                            </p>
                        </div>
                    ))}
                </div>
            </div>

            <div className="grid max-w-xl gap-2">
                <Label htmlFor="otros_ingresos">Otros ingresos (saldo del año anterior, multas, opcional)</Label>
                <Input
                    id="otros_ingresos"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.otros_ingresos}
                    onChange={(e) => setData('otros_ingresos', e.target.value)}
                />
                <p className="text-muted-foreground text-xs">Se resta del presupuesto para que la cuota no salga más alta de lo necesario.</p>
                <InputError message={errors.otros_ingresos} />
            </div>

            <div className="max-w-xl rounded-md border p-4">
                <p className="text-muted-foreground text-xs">Presupuesto estimado (suma de actividades, sin reserva ni otros ingresos)</p>
                <p className="font-mono text-xl">{formatearMonto(presupuestoEstimado)}</p>
                <p className="text-muted-foreground mt-1 text-xs">
                    El presupuesto neto y la cuota final se calculan en el servidor al guardar, sumando la reserva obligatoria del 10%.
                </p>
            </div>

            <Button type="submit" disabled={processing || data.actividades.length === 0} className="w-fit">
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {textoBoton}
            </Button>
        </form>
    );
}
