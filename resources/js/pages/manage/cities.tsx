import CityController from '@/actions/App/Http/Controllers/Manage/CityController';
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
import { useMemo, useState } from 'react';

interface City {
    id: number;
    name: string;
    country_id: number;
    state_id: number | null;
    country_name: string;
    state_name: string | null;
    population: number | null;
    timezone: string | null;
    venues_count: number;
}

interface Country {
    id: number;
    name: string;
}

interface State {
    id: number;
    name: string;
    country_id: number;
}

interface CitiesProps {
    cities: City[];
    countries: Country[];
    states: State[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cities',
        href: '/manage/cities',
    },
];

export default function Cities({ cities, countries, states }: CitiesProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [editCity, setEditCity] = useState<City | null>(null);
    const [editName, setEditName] = useState('');
    const [editCountryId, setEditCountryId] = useState('');
    const [editStateId, setEditStateId] = useState('');
    const [editTimezone, setEditTimezone] = useState('');
    const [editPopulation, setEditPopulation] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const [createCountryId, setCreateCountryId] = useState('');

    const filteredStatesForCreate = useMemo(
        () => (createCountryId ? states.filter((s) => s.country_id === Number(createCountryId)) : states),
        [createCountryId, states],
    );

    const filteredStatesForEdit = useMemo(
        () => (editCountryId ? states.filter((s) => s.country_id === Number(editCountryId)) : states),
        [editCountryId, states],
    );

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this city?')) {
            router.delete(CityController.destroy.url(id), { preserveScroll: true });
        }
    };

    const openEdit = (city: City) => {
        setEditCity(city);
        setEditName(city.name);
        setEditCountryId(String(city.country_id));
        setEditStateId(city.state_id ? String(city.state_id) : '');
        setEditTimezone(city.timezone ?? '');
        setEditPopulation(city.population ? String(city.population) : '');
        setEditErrors({});
        setEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editCity) {
            return;
        }

        router.put(
            CityController.update.url(editCity.id),
            {
                name: editName,
                country_id: editCountryId,
                state_id: editStateId || null,
                timezone: editTimezone || null,
                population: editPopulation || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const clearForm = () => {
        const fields = ['name', 'timezone', 'population'] as const;

        fields.forEach((field) => {
            const el = document.getElementById(field) as HTMLInputElement;

            if (el) {
                el.value = '';
            }
        });

        const countrySelect = document.getElementById('country_id') as HTMLSelectElement;
        const stateSelect = document.getElementById('state_id') as HTMLSelectElement;

        if (countrySelect) {
            countrySelect.value = '';
        }

        if (stateSelect) {
            stateSelect.value = '';
        }

        setCreateCountryId('');
    };

    const handleCreateCountryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setCreateCountryId(e.target.value);

        const stateSelect = document.getElementById('state_id') as HTMLSelectElement;

        if (stateSelect) {
            stateSelect.value = '';
        }
    };

    const handleEditCountryChange = (value: string) => {
        setEditCountryId(value);
        setEditStateId('');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cities" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-2xl space-y-6">
                    <HeadingSmall title="Cities" description="Manage cities for your travel plans" />

                    <Form
                        action={CityController.store.url()}
                        method="post"
                        options={{ preserveScroll: true, onSuccess: clearForm }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="country_id">Country</Label>
                                        <select
                                            id="country_id"
                                            name="country_id"
                                            required
                                            onChange={handleCreateCountryChange}
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                        >
                                            <option value="">Select a country</option>
                                            {countries.map((country) => (
                                                <option key={country.id} value={country.id}>
                                                    {country.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.country_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="state_id">State</Label>
                                        <select
                                            id="state_id"
                                            name="state_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                        >
                                            <option value="">Select a state (optional)</option>
                                            {filteredStatesForCreate.map((state) => (
                                                <option key={state.id} value={state.id}>
                                                    {state.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.state_id} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" type="text" name="name" placeholder="City name" required />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="timezone">Timezone</Label>
                                        <Input
                                            id="timezone"
                                            type="text"
                                            name="timezone"
                                            placeholder="e.g. Asia/Tokyo"
                                        />
                                        <InputError message={errors.timezone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="population">Population</Label>
                                        <Input
                                            id="population"
                                            type="number"
                                            name="population"
                                            placeholder="Population"
                                        />
                                        <InputError message={errors.population} />
                                    </div>
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Add City
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">City added.</p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current cities</h4>
                        {cities.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No cities have been added yet.</p>
                        ) : (
                            <div className="divide-border overflow-hidden rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-2 text-left font-medium">Name</th>
                                            <th className="px-4 py-2 text-left font-medium">Country</th>
                                            <th className="px-4 py-2 text-left font-medium">State</th>
                                            <th className="px-4 py-2 text-left font-medium">Timezone</th>
                                            <th className="px-4 py-2 text-left font-medium">Population</th>
                                            <th className="px-4 py-2 text-left font-medium">Venues</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {cities.map((city) => (
                                            <tr key={city.id}>
                                                <td className="px-4 py-3">{city.name}</td>
                                                <td className="px-4 py-3">{city.country_name}</td>
                                                <td className="px-4 py-3">{city.state_name ?? '-'}</td>
                                                <td className="px-4 py-3">{city.timezone ?? '-'}</td>
                                                <td className="px-4 py-3">
                                                    {city.population?.toLocaleString() ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">{city.venues_count}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => openEdit(city)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(city.id)}
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
                        <DialogTitle>Edit City</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-country_id">Country</Label>
                                <Select value={editCountryId} onValueChange={handleEditCountryChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a country" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countries.map((country) => (
                                            <SelectItem key={country.id} value={String(country.id)}>
                                                {country.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editErrors.country_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-state_id">State</Label>
                                <Select value={editStateId} onValueChange={setEditStateId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a state (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filteredStatesForEdit.map((state) => (
                                            <SelectItem key={state.id} value={String(state.id)}>
                                                {state.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editErrors.state_id} />
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

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-timezone">Timezone</Label>
                                <Input
                                    id="edit-timezone"
                                    type="text"
                                    value={editTimezone}
                                    onChange={(e) => setEditTimezone(e.target.value)}
                                    placeholder="e.g. Asia/Tokyo"
                                />
                                <InputError message={editErrors.timezone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-population">Population</Label>
                                <Input
                                    id="edit-population"
                                    type="number"
                                    value={editPopulation}
                                    onChange={(e) => setEditPopulation(e.target.value)}
                                    placeholder="Population"
                                />
                                <InputError message={editErrors.population} />
                            </div>
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
