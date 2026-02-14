import DayAccommodationManagementController from '@/actions/App/Http/Controllers/Manage/DayAccommodationManagementController';
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

interface AccommodationInfo {
    id: number;
    venue_id: number;
    venue_name: string;
}

interface DayItem {
    id: number;
    number: number;
    date: string;
    accommodation: AccommodationInfo | null;
}

interface VenueOption {
    id: number;
    name: string;
    type: string;
}

interface AccommodationsProps {
    project: { id: number; name: string };
    days: DayItem[];
    venues: VenueOption[];
}

export default function Accommodations({ project, days, venues }: AccommodationsProps) {
    const [addingDayId, setAddingDayId] = useState<number | null>(null);
    const [editing, setEditing] = useState<(AccommodationInfo & { dayId: number }) | null>(null);
    const [editVenue, setEditVenue] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Projects', href: '/manage/projects' },
        { title: `${project.name} - Accommodations`, href: `/manage/project/${project.id}/accommodations` },
    ];

    const openEdit = (accommodation: AccommodationInfo, dayId: number) => {
        setEditing({ ...accommodation, dayId });
        setEditVenue(String(accommodation.venue_id));
        setEditErrors({});
    };

    const handleUpdate = (e: FormEvent) => {
        e.preventDefault();

        if (!editing) {
            return;
        }

        router.put(
            `/manage/project/${project.id}/accommodations/${editing.id}`,
            { day_id: editing.dayId, venue_id: editVenue },
            { preserveScroll: true, onSuccess: () => setEditing(null), onError: (errors) => setEditErrors(errors) },
        );
    };

    const handleDelete = (accommodationId: number) => {
        if (confirm('Delete this accommodation?')) {
            router.delete(`/manage/project/${project.id}/accommodations/${accommodationId}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Accommodations`} />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-2xl space-y-6">
                    <HeadingSmall
                        title="Day Accommodations"
                        description={`Manage accommodations for ${project.name}`}
                    />

                    <div className="space-y-2">
                        {days.map((day) => (
                            <div key={day.id} className="rounded-md border px-4 py-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="text-sm font-medium">Day {day.number}</span>
                                        <span className="ml-2 text-xs text-muted-foreground">{day.date}</span>
                                    </div>

                                    {day.accommodation ? (
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm">{day.accommodation.venue_name}</span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openEdit(day.accommodation!, day.id)}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleDelete(day.accommodation!.id)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ) : (
                                        <Button variant="outline" size="sm" onClick={() => setAddingDayId(day.id)}>
                                            Add Accommodation
                                        </Button>
                                    )}
                                </div>

                                {addingDayId === day.id && (
                                    <Form
                                        {...DayAccommodationManagementController.store.form(project.id)}
                                        options={{ preserveScroll: true, onSuccess: () => setAddingDayId(null) }}
                                        className="mt-3 space-y-3 border-t pt-3"
                                    >
                                        {({ processing, recentlySuccessful, errors }) => (
                                            <>
                                                <input type="hidden" name="day_id" value={day.id} />

                                                <div className="grid gap-2">
                                                    <Label>Venue</Label>
                                                    <select
                                                        name="venue_id"
                                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                        required
                                                    >
                                                        <option value="">Select venue</option>
                                                        {venues.map((v) => (
                                                            <option key={v.id} value={v.id}>
                                                                {v.name} ({v.type})
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError message={errors.venue_id} />
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
                                                    <p className="text-sm text-green-600">Accommodation added.</p>
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
                        <DialogTitle>Edit Accommodation</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid gap-2">
                            <Label>Venue</Label>
                            <select
                                value={editVenue}
                                onChange={(e) => setEditVenue(e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                required
                            >
                                <option value="">Select venue</option>
                                {venues.map((v) => (
                                    <option key={v.id} value={v.id}>
                                        {v.name} ({v.type})
                                    </option>
                                ))}
                            </select>
                            <InputError message={editErrors.venue_id} />
                        </div>

                        <Button type="submit">Save Changes</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
