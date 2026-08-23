import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import PanelLayout from '@/layouts/panel-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Apariencia',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Apariencia" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Apariencia" description="Elige cómo se ve el sistema en este dispositivo" />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </PanelLayout>
    );
}
