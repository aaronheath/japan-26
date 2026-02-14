import DayActivityManagementController from '@/actions/App/Http/Controllers/Manage/DayActivityManagementController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface ActivityInfo {
    id: number;
    type: string;
    venue_id: number | null;
    city_id: number | null;
    venue_name: string | null;
    city_name: string | null;
}

interface DayItem {
    id: number;
    number: number;
    date: string;
    activities: ActivityInfo[];
}

interface OptionItem {
    id: number;
    name: string;
}

interface TypeOption {
    value: string;
    label: string;
}

interface ActivitiesProps {
    project: { id: number; name: string };
    days: DayItem[];
    cities: OptionItem[];
    venues: OptionItem[];
    activityTypes: TypeOption[];
}

export default function Activities({ project, days, cities, venues, activityTypes }: ActivitiesProps) {
    const [addingDayId, setAddingDayId] = useState<number | null>(null);
    const [editing, setEditing] = useState<(ActivityInfo & { dayId: number }) | null>(null);
    const [editType, setEditType] = useState('');
    const [editVenue, setEditVenue] = useState('');
    const [editCity, setEditCity] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Projects', href: '/manage/projects' },
        { title: `${project.name} - Activities`, href: `/manage/project/${project.id}/activities` },
    ];

    const openEdit = (activity: ActivityInfo, dayId: number) => {
        setEditing({ ...activity, dayId });
        setEditType(activity.type);
        setEditVenue(activity.venue_id ? String(activity.venue_id) : '');
        setEditCity(activity.city_id ? String(activity.city_id) : '');
        setEditErrors({});
    };

    const handleUpdate = (e: FormEvent) => {
        e.preventDefault();

        if (!editing) {
            return;
        }

        router.put(
            `/manage/project/${project.id}/activities/${editing.id}`,
            {
                day_id: editing.dayId,
                type: editType,
                venue_id: editVenue || null,
                city_id: editCity || null,
            },
            { preserveScroll: true, onSuccess: () => setEditing(null), onError: (errors) => setEditErrors(errors) },
        );
    };

    const handleDelete = (activityId: number) => {
        if (confirm('Delete this activity?')) {
            router.delete(`/manage/project/${project.id}/activities/${activityId}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Activities`} />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-2xl space-y-6">
                    <HeadingSmall title="Day Activities" description={`Manage activities for ${project.name}`} />

                    <div className="space-y-2">
                        {days.map((day) => (
                            <div key={day.id} className="rounded-md border px-4 py-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="text-sm font-medium">Day {day.number}</span>
                                        <span className="ml-2 text-xs text-muted-foreground">{day.date}</span>
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            ({day.activities.length} activit{day.activities.length === 1 ? 'y' : 'ies'})
                                        </span>
                                    </div>

                                    <Button variant="outline" size="sm" onClick={() => setAddingDayId(day.id)}>
                                        Add Activity
                                    </Button>
                                </div>

                                {day.activities.length > 0 && (
                                    <ul className="mt-2 space-y-1">
                                        {day.activities.map((activity) => (
                                            <li
                                                key={activity.id}
                                                className="flex items-center justify-between rounded bg-muted/50 px-3 py-2"
                                            >
                                                <span className="text-sm">
                                                    <span className="font-medium capitalize">{activity.type}</span>
                                                    {activity.venue_name && ` - ${activity.venue_name}`}
                                                    {activity.city_name && ` (${activity.city_name})`}
                                                </span>
                                                <div className="flex gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEdit(activity, day.id)}
                                                    >
                                                        <Pencil className="h-3 w-3" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(activity.id)}
                                                        className="text-destructive hover:text-destructive"
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </Button>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {addingDayId === day.id && (
                                    <Form
                                        {...DayActivityManagementController.store.form(project.id)}
                                        options={{ preserveScroll: true, onSuccess: () => setAddingDayId(null) }}
                                        className="mt-3 space-y-3 border-t pt-3"
                                    >
                                        {({ processing, recentlySuccessful, errors }) => (
                                            <>
                                                <input type="hidden" name="day_id" value={day.id} />

                                                <div className="grid gap-2">
                                                    <Label>Type</Label>
                                                    <select
                                                        name="type"
                                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                        required
                                                    >
                                                        <option value="">Select type</option>
                                                        {activityTypes.map((t) => (
                                                            <option key={t.value} value={t.value}>
                                                                {t.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError message={errors.type} />
                                                </div>

                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="grid gap-2">
                                                        <Label>Venue (optional)</Label>
                                                        <select
                                                            name="venue_id"
                                                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                        >
                                                            <option value="">None</option>
                                                            {venues.map((v) => (
                                                                <option key={v.id} value={v.id}>
                                                                    {v.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <InputError message={errors.venue_id} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label>City (optional)</Label>
                                                        <select
                                                            name="city_id"
                                                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                        >
                                                            <option value="">None</option>
                                                            {cities.map((c) => (
                                                                <option key={c.id} value={c.id}>
                                                                    {c.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <InputError message={errors.city_id} />
                                                    </div>
                                                </div>

                                                <div className="flex gap-2">
                                                    <Button type="submit" size="sm" disabled={processing}>
                                                        Save
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setAddingDayId(null)}
                                                    >
                                                        Cancel
                                                    </Button>
                                                </div>

                                                <Transition
                                                    show={recentlySuccessful}
                                                    enter="transition ease-in-out"
                                                    enterFrom="opacity-0"
                                                    leave="transition ease-in-out"
                                                    leaveTo="opacity-0"
                                                >
                                                    <p className="text-sm text-green-600">Activity added.</p>
                                                </Transition>
                                            </>
                                        )}
                                    </Form>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <Dialog open={!!editing} onOpenChange={() => setEditing(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Activity</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid gap-2">
                            <Label>Type</Label>
                            <select
                                value={editType}
                                onChange={(e) => setEditType(e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                required
                            >
                                <option value="">Select type</option>
                                {activityTypes.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={editErrors.type} />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Venue (optional)</Label>
                                <select
                                    value={editVenue}
                                    onChange={(e) => setEditVenue(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    <option value="">None</option>
                                    {venues.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={editErrors.venue_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label>City (optional)</Label>
                                <select
                                    value={editCity}
                                    onChange={(e) => setEditCity(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    <option value="">None</option>
                                    {cities.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={editErrors.city_id} />
                            </div>
                        </div>

                        <Button type="submit">Save Changes</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
