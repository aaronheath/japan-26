import { RegenerateButton } from '@/components/regenerate-button';
import { Markdown } from '@/components/ui/markdown';
import AppLayout from '@/layouts/app-layout';
import { show as showProject } from '@/routes/project';
import { show as showDay } from '@/routes/project/day';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Project {
    id: number;
    name: string;
}

interface Day {
    id: number;
    number: number;
    date: string;
}

interface City {
    id: number;
    name: string;
    country_code?: string;
}

interface State {
    id: number;
    name: string;
}

interface CityWithState extends City {
    state: State;
}

interface LlmCall {
    id: number;
    response: string;
    created_at: string;
}

interface Travel {
    id: number;
    start_city: CityWithState;
    end_city: CityWithState;
    llm_call: LlmCall | null;
}

interface Activity {
    id: number;
    type: string;
    city: City | null;
    llm_call: LlmCall | null;
}

interface DayPageProps {
    project: Project;
    day: Day;
    tab: string;
    travel: Travel | Record<string, never>;
    activities: Activity[];
}

export default function DayPage({ project, day, tab, travel, activities }: DayPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: project.name,
            href: showProject(project.id).url,
        },
        {
            title: `Day ${day.number}`,
            href: showDay([project.id, day.number]).url,
        },
    ];

    const hasTravel = 'start_city' in travel;

    const handleRegenerateSuccess = () => {
        setTimeout(() => {
            router.reload();
        }, 3000);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Day ${day.number}`} />

            <div className="p-4">
                <h1 className="mb-4 text-xl font-bold">Day {day.number}</h1>

                <div className="mb-6 flex flex-wrap gap-x-4 gap-y-1">
                    <Link
                        href={`/project/${project.id}/day/${day.number}?tab=overview`}
                        className={`underline ${
                            tab === 'overview' ? 'font-medium text-foreground' : 'text-blue-600 dark:text-blue-400'
                        }`}
                    >
                        Overview
                    </Link>

                    {hasTravel && (
                        <Link
                            href={`/project/${project.id}/day/${day.number}?tab=travel`}
                            className={`underline ${
                                tab === 'travel' ? 'font-medium text-foreground' : 'text-blue-600 dark:text-blue-400'
                            }`}
                        >
                            Travel
                        </Link>
                    )}

                    {activities.map((activity, i) => (
                        <Link
                            key={i}
                            href={`/project/${project.id}/day/${day.number}?tab=activity-${i}`}
                            className={`capitalize underline ${
                                tab === `activity-${i}`
                                    ? 'font-medium text-foreground'
                                    : 'text-blue-600 dark:text-blue-400'
                            }`}
                        >
                            {activity.type}
                        </Link>
                    ))}
                </div>

                <div className="space-y-4">
                    {tab === 'overview' && (
                        <div>
                            <p className="text-muted-foreground">Select a tab above to view details.</p>
                        </div>
                    )}

                    {tab === 'travel' && hasTravel && (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-bold">Travel</h2>
                                <RegenerateButton
                                    projectId={project.id}
                                    type="single"
                                    itemType="travel"
                                    itemId={travel.id}
                                    onSuccess={handleRegenerateSuccess}
                                >
                                    Regenerate
                                </RegenerateButton>
                            </div>

                            <p>
                                {travel.start_city.name}, {travel.start_city.state.name} to {travel.end_city.name},{' '}
                                {travel.end_city.state.name}
                            </p>

                            {travel.llm_call?.response && <Markdown content={travel.llm_call.response} />}
                        </div>
                    )}

                    {tab.startsWith('activity-') && (
                        <>
                            {(() => {
                                const index = parseInt(tab.replace('activity-', ''), 10);
                                const activity = activities[index];

                                if (!activity) {
                                    return <p className="text-muted-foreground">Activity not found.</p>;
                                }

                                return (
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <h2 className="text-lg font-bold capitalize">
                                                {activity.type}
                                                {activity.city && ` in ${activity.city.name}`}
                                            </h2>
                                            <RegenerateButton
                                                projectId={project.id}
                                                type="single"
                                                itemType="activity"
                                                itemId={activity.id}
                                                onSuccess={handleRegenerateSuccess}
                                            >
                                                Regenerate
                                            </RegenerateButton>
                                        </div>

                                        {activity.llm_call?.response && (
                                            <Markdown content={activity.llm_call.response} />
                                        )}
                                    </div>
                                );
                            })()}
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
