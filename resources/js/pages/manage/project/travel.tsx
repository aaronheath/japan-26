import DayTravelManagementController from '@/actions/App/Http/Controllers/Manage/DayTravelManagementController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface TravelInfo {
    id: number;
    start_city_id: number;
    end_city_id: number;
    start_city_name: string;
    end_city_name: string;
    overnight: boolean;
}

interface DayItem {
    id: number;
    number: number;
    date: string;
    travel: TravelInfo | null;
}

interface CityOption {
    id: number;
    name: string;
}

interface TravelProps {
    project: { id: number; name: string };
    days: DayItem[];
    cities: CityOption[];
}

export default function Travel({ project, days, cities }: TravelProps) {
    const [addingDayId, setAddingDayId] = useState<number | null>(null);
    const [editing, setEditing] = useState<(TravelInfo & { dayId: number }) | null>(null);
    const [editStartCity, setEditStartCity] = useState('');
    const [editEndCity, setEditEndCity] = useState('');
    const [editOvernight, setEditOvernight] = useState(false);
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Projects', href: '/manage/projects' },
        { title: `${project.name} - Travel`, href: `/manage/project/${project.id}/travel` },
    ];

    const openEdit = (travel: TravelInfo, dayId: number) => {
        setEditing({ ...travel, dayId });
        setEditStartCity(String(travel.start_city_id));
        setEditEndCity(String(travel.end_city_id));
        setEditOvernight(travel.overnight);
        setEditErrors({});
    };

    const handleUpdate = (e: FormEvent) => {
        e.preventDefault();

        if (!editing) {
            return;
        }

        router.put(
            `/manage/project/${project.id}/travel/${editing.id}`,
            {
                day_id: editing.dayId,
                start_city_id: editStartCity,
                end_city_id: editEndCity,
                overnight: editOvernight,
            },
            { preserveScroll: true, onSuccess: () => setEditing(null), onError: (errors) => setEditErrors(errors) },
        );
    };

    const handleDelete = (travelId: number) => {
        if (confirm('Delete this travel entry?')) {
            router.delete(`/manage/project/${project.id}/travel/${travelId}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Travel`} />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-2xl space-y-6">
                    <HeadingSmall title="Day Travel" description={`Manage travel for ${project.name}`} />

                    <div className="space-y-2">
                        {days.map((day) => (
                            <div key={day.id} className="rounded-md border px-4 py-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="text-sm font-medium">Day {day.number}</span>
                                        <span className="ml-2 text-xs text-muted-foreground">{day.date}</span>
                                    </div>

                                    {day.travel ? (
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm">
                                                {day.travel.start_city_name} → {day.travel.end_city_name}
                                                {day.travel.overnight && ' (overnight)'}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openEdit(day.travel!, day.id)}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleDelete(day.travel!.id)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ) : (
                                        <Button variant="outline" size="sm" onClick={() => setAddingDayId(day.id)}>
                                            Add Travel
                                        </Button>
                                    )}
                                </div>

                                {addingDayId === day.id && (
                                    <Form
                                        {...DayTravelManagementController.store.form(project.id)}
                                        options={{ preserveScroll: true, onSuccess: () => setAddingDayId(null) }}
                                        className="mt-3 space-y-3 border-t pt-3"
                                    >
                                        {({ processing, recentlySuccessful, errors }) => (
                                            <>
                                                <input type="hidden" name="day_id" value={day.id} />

                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="grid gap-2">
                                                        <Label>From</Label>
                                                        <select
                                                            name="start_city_id"
                                                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                            required
                                                        >
                                                            <option value="">Select city</option>
                                                            {cities.map((c) => (
                                                                <option key={c.id} value={c.id}>
                                                                    {c.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <InputError message={errors.start_city_id} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label>To</Label>
                                                        <select
                                                            name="end_city_id"
                                                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                            required
                                                        >
                                                            <option value="">Select city</option>
                                                            {cities.map((c) => (
                                                                <option key={c.id} value={c.id}>
                                                                    {c.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <InputError message={errors.end_city_id} />
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <input type="hidden" name="overnight" value="0" />
                                                    <input
                                                        type="checkbox"
                                                        name="overnight"
                                                        value="1"
                                                        className="h-4 w-4"
                                                    />
                                                    <Label>Overnight</Label>
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
                                                    <p className="text-sm text-green-600">Travel added.</p>
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
                        <DialogTitle>Edit Travel</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>From</Label>
                                <select
                                    value={editStartCity}
                                    onChange={(e) => setEditStartCity(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                    required
                                >
                                    <option value="">Select city</option>
                                    {cities.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={editErrors.start_city_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label>To</Label>
                                <select
                                    value={editEndCity}
                                    onChange={(e) => setEditEndCity(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                    required
                                >
                                    <option value="">Select city</option>
                                    {cities.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={editErrors.end_city_id} />
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="edit_overnight"
                                checked={editOvernight}
                                onCheckedChange={(checked) => setEditOvernight(checked === true)}
                            />
                            <Label htmlFor="edit_overnight">Overnight</Label>
                        </div>

                        <Button type="submit">Save Changes</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
