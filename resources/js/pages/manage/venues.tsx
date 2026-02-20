import VenueController from '@/actions/App/Http/Controllers/Manage/VenueController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Venue {
    id: number;
    name: string;
    type: string;
    description: string | null;
    city_id: number;
    city_name: string;
}

interface City {
    id: number;
    name: string;
}

interface VenueType {
    value: string;
    label: string;
}

interface VenuesProps {
    venues: Venue[];
    cities: City[];
    venueTypes: VenueType[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Venues',
        href: '/manage/venues',
    },
];

export default function Venues({ venues, cities, venueTypes }: VenuesProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [editVenue, setEditVenue] = useState<Venue | null>(null);
    const [editName, setEditName] = useState('');
    const [editType, setEditType] = useState('');
    const [editCityId, setEditCityId] = useState('');
    const [editDescription, setEditDescription] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this venue?')) {
            router.delete(VenueController.destroy.url(id), { preserveScroll: true });
        }
    };

    const openEdit = (venue: Venue) => {
        setEditVenue(venue);
        setEditName(venue.name);
        setEditType(venue.type);
        setEditCityId(String(venue.city_id));
        setEditDescription(venue.description ?? '');
        setEditErrors({});
        setEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editVenue) {
            return;
        }

        router.put(
            VenueController.update.url(editVenue.id),
            {
                name: editName,
                type: editType,
                city_id: editCityId,
                description: editDescription || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const clearForm = () => {
        const fields = ['name', 'description'] as const;

        fields.forEach((field) => {
            const el = document.getElementById(field) as HTMLInputElement | HTMLTextAreaElement;

            if (el) {
                el.value = '';
            }
        });

        const citySelect = document.getElementById('city_id') as HTMLSelectElement;
        const typeSelect = document.getElementById('type') as HTMLSelectElement;

        if (citySelect) {
            citySelect.value = '';
        }

        if (typeSelect) {
            typeSelect.value = '';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-2xl space-y-6">
                    <HeadingSmall title="Venues" description="Manage venues for your travel plans" />

                    <Form
                        action={VenueController.store.url()}
                        method="post"
                        options={{ preserveScroll: true, onSuccess: clearForm }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="city_id">City</Label>
                                        <select
                                            id="city_id"
                                            name="city_id"
                                            required
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                        >
                                            <option value="">Select a city</option>
                                            {cities.map((city) => (
                                                <option key={city.id} value={city.id}>
                                                    {city.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.city_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Type</Label>
                                        <select
                                            id="type"
                                            name="type"
                                            required
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                        >
                                            <option value="">Select a type</option>
                                            {venueTypes.map((vt) => (
                                                <option key={vt.value} value={vt.value}>
                                                    {vt.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.type} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" type="text" name="name" placeholder="Venue name" required />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">Description</Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        placeholder="Venue description (optional)"
                                        rows={3}
                                        className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none"
                                    />
                                    <InputError message={errors.description} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Add Venue
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">Venue added.</p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current venues</h4>
                        {venues.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No venues have been added yet.</p>
                        ) : (
                            <div className="divide-border overflow-hidden rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-2 text-left font-medium">Name</th>
                                            <th className="px-4 py-2 text-left font-medium">Type</th>
                                            <th className="px-4 py-2 text-left font-medium">City</th>
                                            <th className="px-4 py-2 text-left font-medium">Description</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {venues.map((venue) => (
                                            <tr key={venue.id}>
                                                <td className="px-4 py-3">{venue.name}</td>
                                                <td className="px-4 py-3">{venue.type}</td>
                                                <td className="px-4 py-3">{venue.city_name}</td>
                                                <td className="max-w-48 truncate px-4 py-3">
                                                    {venue.description ?? '-'}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => openEdit(venue)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(venue.id)}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Venue</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-city_id">City</Label>
                                <Select value={editCityId} onValueChange={setEditCityId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a city" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {cities.map((city) => (
                                            <SelectItem key={city.id} value={String(city.id)}>
                                                {city.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editErrors.city_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-type">Type</Label>
                                <Select value={editType} onValueChange={setEditType}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {venueTypes.map((vt) => (
                                            <SelectItem key={vt.value} value={vt.value}>
                                                {vt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editErrors.type} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                type="text"
                                value={editName}
                                onChange={(e) => setEditName(e.target.value)}
                                required
                            />
                            <InputError message={editErrors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-description">Description</Label>
                            <textarea
                                id="edit-description"
                                value={editDescription}
                                onChange={(e) => setEditDescription(e.target.value)}
                                placeholder="Venue description (optional)"
                                rows={3}
                                className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none"
                            />
                            <InputError message={editErrors.description} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
