import { cn } from '@/lib/utils';

type VarianteEstado = 'exito' | 'error' | 'advertencia' | 'neutro';

interface Props {
    children: React.ReactNode;
    variante: VarianteEstado;
    className?: string;
}

const clasesPorVariante: Record<VarianteEstado, string> = {
    exito: 'bg-yaku-exito-suave text-yaku-exito',
    error: 'bg-yaku-error-suave text-yaku-error',
    advertencia: 'bg-yaku-advertencia-suave text-yaku-advertencia',
    neutro: 'bg-muted text-muted-foreground',
};

/**
 * Badge de estado de negocio (docs/design-system.md §5) — reunión
 * programada/cancelada/finalizada, anuncio visible/inactivo, vivienda
 * al día/morosa, etc. No usar el Badge de shadcn con colores por defecto
 * para este propósito.
 */
export default function BadgeEstado({ children, variante, className }: Props) {
    return (
        <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', clasesPorVariante[variante], className)}>
            {children}
        </span>
    );
}
