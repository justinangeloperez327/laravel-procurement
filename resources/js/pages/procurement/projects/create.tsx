import { Head, Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import ProcurementProjectForm from '@/components/procurement/procurement-project-form';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type Props = ComponentProps<typeof ProcurementProjectForm>;

export default function CreateProcurementProject(props: Props) {
    return (
        <>
            <Head title="Initiate Procurement Project" />
            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-5xl space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <Heading
                            title="Initiate Procurement Project"
                            description="Create a procurement project from an eligible approved APP item while preserving the approved planning source and budget ceiling."
                        />
                        <Button asChild variant="outline">
                            <Link href="/procurement/projects">Back</Link>
                        </Button>
                    </div>
                    <div className="bg-card rounded-xl border p-4 shadow-xs md:p-6">
                        <ProcurementProjectForm {...props} />
                    </div>
                </div>
            </div>
        </>
    );
}
