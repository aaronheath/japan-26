import AppLayout from '@/layouts/app-layout';
import { show as showProject } from '@/routes/project';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Project {
    id: number;
    name: string;
}

interface DayActivity {
    hasLlmCall: boolean;
    url: string;
}

interface Day {
    number: number;
    date: string;
    travel: {
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

export default function ProjectShow({
    project,
    days,
    activityTypes,
}: ProjectShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: project.name,
            href: showProject(project.id).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={project.name} />

            <div className="p-4">
                <h1 className="text-xl font-bold mb-4">Project Overview</h1>

                <div className="overflow-x-auto">
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="border-b">
                                <th className="py-2 px-3 text-left">Day</th>
                                <th className="py-2 px-3 text-left">Travel</th>
                                {activityTypes.map((type) => (
                                    <th
                                        key={type}
                                        className="py-2 px-3 text-left capitalize"
                                    >
                                        {type}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {days.map((day) => (
                                <tr key={day.number} className="border-b">
                                    <td className="py-2 px-3">
                                        <Link
                                            href={`/project/${project.id}/day/${day.number}`}
                                            className="text-blue-600 underline dark:text-blue-400"
                                        >
                                            Day {day.number}
                                        </Link>
                                    </td>
                                    <td className="py-2 px-3">
                                        {day.travel ? (
                                            day.travel.hasLlmCall ? (
                                                <Link
                                                    href={day.travel.url}
                                                    className="text-blue-600 underline dark:text-blue-400"
                                                >
                                                    View
                                                </Link>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Pending
                                                </span>
                                            )
                                        ) : (
                                            <span className="text-muted-foreground/50">
                                                &mdash;
                                            </span>
                                        )}
                                    </td>
                                    {activityTypes.map((type) => (
                                        <td key={type} className="py-2 px-3">
                                            {day.activities[type] ? (
                                                day.activities[type]
                                                    .hasLlmCall ? (
                                                    <Link
                                                        href={
                                                            day.activities[type]
                                                                .url
                                                        }
                                                        className="text-blue-600 underline dark:text-blue-400"
                                                    >
                                                        View
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Pending
                                                    </span>
                                                )
                                            ) : (
                                                <span className="text-muted-foreground/50">
                                                    &mdash;
                                                </span>
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
