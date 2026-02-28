import { RegenerateButton } from '@/components/regenerate-button';
import { RegenerationConfirmDialog } from '@/components/regeneration-confirm-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { show as showProject } from '@/routes/project';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';

interface Project {
    id: number;
    name: string;
}

interface DayActivity {
    id: number;
    hasLlmCall: boolean;
    url: string;
}

interface Day {
    id: number;
    number: number;
    date: string;
    travel: {
        id: number;
        hasLlmCall: boolean;
        url: string;
    } | null;
    activities: Record<string, DayActivity>;
}

interface ProjectShowProps {
    project: Project;
    days: Day[];
    activityTypes: string[];
}

export default function ProjectShow({ project, days, activityTypes }: ProjectShowProps) {
    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        type: 'column' | 'project';
        columnType?: string;
        totalItems: number;
    }>({ open: false, type: 'project', totalItems: 0 });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: project.name,
            href: showProject(project.id).url,
        },
    ];

    const getColumnItemCount = (columnType: string): number => {
        if (columnType === 'travel') {
            return days.filter((d) => d.travel !== null).length;
        }

        return days.filter((d) => d.activities[columnType]).length;
    };

    const getTotalItemCount = (): number => {
        let total = days.filter((d) => d.travel !== null).length;

        for (const type of activityTypes) {
            total += days.filter((d) => d.activities[type]).length;
        }

        return total;
    };

    const openColumnDialog = (columnType: string) => {
        setConfirmDialog({
            open: true,
            type: 'column',
            columnType,
            totalItems: getColumnItemCount(columnType),
        });
    };

    const openProjectDialog = () => {
        setConfirmDialog({
            open: true,
            type: 'project',
            totalItems: getTotalItemCount(),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={project.name} />

            <div className="p-4">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-xl font-bold">Project Overview</h1>
                    <Button variant="outline" onClick={openProjectDialog}>
                        <RefreshCw className="size-4" />
                        Regenerate All
                    </Button>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="border-b">
                                <th className="px-3 py-2 text-left">Day</th>
                                <th className="px-3 py-2 text-left">Travel</th>
                                {activityTypes.map((type) => (
                                    <th key={type} className="px-3 py-2 text-left capitalize">
                                        {type}
                                    </th>
                                ))}
                                <th className="px-3 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {days.map((day) => (
                                <tr key={day.number} className="border-b">
                                    <td className="px-3 py-2">
                                        <Link
                                            href={`/project/${project.id}/day/${day.number}`}
                                            className="text-blue-600 underline dark:text-blue-400"
                                        >
                                            Day {day.number}
                                        </Link>
                                        <span className="text-muted-foreground"> - {formatDate(day.date)}</span>
                                    </td>
                                    <td className="px-3 py-2">
                                        {day.travel ? (
                                            day.travel.hasLlmCall ? (
                                                <Link
                                                    href={day.travel.url}
                                                    className="text-blue-600 underline dark:text-blue-400"
                                                >
                                                    View
                                                </Link>
                                            ) : (
                                                <span className="text-muted-foreground">Pending</span>
                                            )
                                        ) : (
                                            <span className="text-muted-foreground/50">&mdash;</span>
                                        )}
                                    </td>
                                    {activityTypes.map((type) => (
                                        <td key={type} className="px-3 py-2">
                                            {day.activities[type] ? (
                                                day.activities[type].hasLlmCall ? (
                                                    <Link
                                                        href={day.activities[type].url}
                                                        className="text-blue-600 underline dark:text-blue-400"
                                                    >
                                                        View
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">Pending</span>
                                                )
                                            ) : (
                                                <span className="text-muted-foreground/50">&mdash;</span>
                                            )}
                                        </td>
                                    ))}
                                    <td className="px-3 py-2">
                                        <RegenerateButton
                                            projectId={project.id}
                                            type="day"
                                            dayId={day.id}
                                            variant="ghost"
                                            size="icon"
                                        />
                                    </td>
                                </tr>
                            ))}
                            <tr className="bg-muted/30">
                                <td className="px-3 py-2 font-medium">Regenerate Column</td>
                                <td className="px-3 py-2">
                                    {getColumnItemCount('travel') > 0 && (
                                        <Button variant="ghost" size="sm" onClick={() => openColumnDialog('travel')}>
                                            <RefreshCw className="size-4" />
                                        </Button>
                                    )}
                                </td>
                                {activityTypes.map((type) => (
                                    <td key={type} className="px-3 py-2">
                                        {getColumnItemCount(type) > 0 && (
                                            <Button variant="ghost" size="sm" onClick={() => openColumnDialog(type)}>
                                                <RefreshCw className="size-4" />
                                            </Button>
                                        )}
                                    </td>
                                ))}
                                <td className="px-3 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <RegenerationConfirmDialog
                open={confirmDialog.open}
                onOpenChange={(open) => setConfirmDialog((prev) => ({ ...prev, open }))}
                projectId={project.id}
                type={confirmDialog.type}
                columnType={confirmDialog.columnType}
                totalItems={confirmDialog.totalItems}
            />
        </AppLayout>
    );
}
