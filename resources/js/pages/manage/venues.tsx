import VenueController from '@/actions/App/Http/Controllers/Manage/VenueController';
import AddressLookup from '@/components/address-lookup';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router, usePage } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface VenueAddress {
    id: number;
    country_id: number;
    state_id: number | null;
    city_id: number;
    postcode: string | null;
    line_1: string;
    line_2: string | null;
    line_3: string | null;
    latitude: number | null;
    longitude: number | null;
}

interface Venue {
    id: number;
    name: string;
    type: string;
    description: string | null;
    city_id: number;
    city_name: string;
    address: VenueAddress | null;
}

interface City {
    id: number;
    name: string;
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

interface VenueType {
    value: string;
    label: string;
}

interface UnattachedAddress {
    id: number;
    label: string;
}

interface VenuesProps {
    venues: Venue[];
    cities: City[];
    countries: Country[];
    states: State[];
    venueTypes: VenueType[];
    unattachedAddresses: UnattachedAddress[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Venues',
        href: '/manage/venues',
    },
];

export default function Venues({
    venues,
    cities: initialCities,
    countries: initialCountries,
    states: initialStates,
    venueTypes,
    unattachedAddresses,
}: VenuesProps) {
    const { googleMapsApiKey } = usePage<SharedData>().props;

    const [countries, setCountries] = useState(initialCountries);
    const [states, setStates] = useState(initialStates);
    const [cities, setCities] = useState(initialCities);

    const [editOpen, setEditOpen] = useState(false);
    const [editVenue, setEditVenue] = useState<Venue | null>(null);
    const [editName, setEditName] = useState('');
    const [editType, setEditType] = useState('');
    const [editCityId, setEditCityId] = useState('');
    const [editDescription, setEditDescription] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const [editAddrCountryId, setEditAddrCountryId] = useState('');
    const [editAddrStateId, setEditAddrStateId] = useState('');
    const [editAddrCityId, setEditAddrCityId] = useState('');
    const [editAddrPostcode, setEditAddrPostcode] = useState('');
    const [editAddrLine1, setEditAddrLine1] = useState('');
    const [editAddrLine2, setEditAddrLine2] = useState('');
    const [editAddrLine3, setEditAddrLine3] = useState('');
    const [editAddrLatitude, setEditAddrLatitude] = useState('');
    const [editAddrLongitude, setEditAddrLongitude] = useState('');

    const [createAddressMode, setCreateAddressMode] = useState<'new' | 'select'>('new');
    const [createCountryId, setCreateCountryId] = useState('');
    const [createLatitude, setCreateLatitude] = useState('');
    const [createLongitude, setCreateLongitude] = useState('');

    const filteredStatesForCreate = useMemo(
        () => (createCountryId ? states.filter((s) => s.country_id === Number(createCountryId)) : states),
        [createCountryId, states],
    );

    const filteredStatesForEdit = useMemo(
        () => (editAddrCountryId ? states.filter((s) => s.country_id === Number(editAddrCountryId)) : states),
        [editAddrCountryId, states],
    );

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const editId = params.get('edit');

        if (editId) {
            const venue = venues.find((v) => v.id === Number(editId));

            if (venue) {
                openEdit(venue);
            }
        }
    }, []);

    useEffect(() => {
        if (editOpen) {
            return;
        }

        const params = new URLSearchParams(window.location.search);

        if (params.has('edit')) {
            params.delete('edit');
            const newUrl = params.toString() ? `${window.location.pathname}?${params}` : window.location.pathname;
            window.history.replaceState({}, '', newUrl);
        }
    }, [editOpen]);

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

        if (venue.address) {
            setEditAddrCountryId(String(venue.address.country_id));
            setEditAddrStateId(venue.address.state_id ? String(venue.address.state_id) : '');
            setEditAddrCityId(String(venue.address.city_id));
            setEditAddrPostcode(venue.address.postcode ?? '');
            setEditAddrLine1(venue.address.line_1);
            setEditAddrLine2(venue.address.line_2 ?? '');
            setEditAddrLine3(venue.address.line_3 ?? '');
            setEditAddrLatitude(venue.address.latitude != null ? String(venue.address.latitude) : '');
            setEditAddrLongitude(venue.address.longitude != null ? String(venue.address.longitude) : '');
        } else {
            setEditAddrCountryId('');
            setEditAddrStateId('');
            setEditAddrCityId('');
            setEditAddrPostcode('');
            setEditAddrLine1('');
            setEditAddrLine2('');
            setEditAddrLine3('');
            setEditAddrLatitude('');
            setEditAddrLongitude('');
        }

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
                address: {
                    country_id: editAddrCountryId,
                    state_id: editAddrStateId || null,
                    city_id: editAddrCityId,
                    postcode: editAddrPostcode || null,
                    line_1: editAddrLine1,
                    line_2: editAddrLine2 || null,
                    line_3: editAddrLine3 || null,
                    latitude: editAddrLatitude || null,
                    longitude: editAddrLongitude || null,
                },
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const clearForm = () => {
        const fields = ['name', 'description', 'addr_postcode', 'addr_line_1', 'addr_line_2', 'addr_line_3'] as const;

        fields.forEach((field) => {
            const el = document.getElementById(field) as HTMLInputElement | HTMLTextAreaElement;

            if (el) {
                el.value = '';
            }
        });

        const selects = ['city_id', 'type', 'addr_country_id', 'addr_state_id', 'addr_city_id', 'address_id'] as const;

        selects.forEach((field) => {
            const el = document.getElementById(field) as HTMLSelectElement;

            if (el) {
                el.value = '';
            }
        });

        setCreateAddressMode('new');
        setCreateCountryId('');
        setCreateLatitude('');
        setCreateLongitude('');
    };

    const handleCreateCountryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setCreateCountryId(e.target.value);

        const stateSelect = document.getElementById('addr_state_id') as HTMLSelectElement;

        if (stateSelect) {
            stateSelect.value = '';
        }
    };

    const handleEditCountryChange = (value: string) => {
        setEditAddrCountryId(value);
        setEditAddrStateId('');
    };

    const mergeGeoRecords = (details: {
        country: { id: number; name: string } | null;
        state: { id: number; name: string; country_id: number } | null;
        city: { id: number; name: string } | null;
    }) => {
        if (details.country && !countries.find((c) => c.id === details.country!.id)) {
            setCountries((prev) => [...prev, details.country!].sort((a, b) => a.name.localeCompare(b.name)));
        }

        if (details.state && !states.find((s) => s.id === details.state!.id)) {
            setStates((prev) => [...prev, details.state!].sort((a, b) => a.name.localeCompare(b.name)));
        }

        if (details.city && !cities.find((c) => c.id === details.city!.id)) {
            setCities((prev) => [...prev, details.city!].sort((a, b) => a.name.localeCompare(b.name)));
        }
    };

    const handleCreateLookupSelect = (details: {
        line_1: string;
        line_2: string | null;
        line_3: string | null;
        postcode: string | null;
        latitude: number | null;
        longitude: number | null;
        country_id: number | null;
        state_id: number | null;
        city_id: number | null;
        country: { id: number; name: string } | null;
        state: { id: number; name: string; country_id: number } | null;
        city: { id: number; name: string } | null;
    }) => {
        mergeGeoRecords(details);

        const setNativeValue = (id: string, value: string) => {
            const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;

            if (el) {
                const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                    id.includes('_id') ? HTMLSelectElement.prototype : HTMLInputElement.prototype,
                    'value',
                )?.set;

                nativeInputValueSetter?.call(el, value);
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        if (details.country_id) {
            setCreateCountryId(String(details.country_id));
            setNativeValue('addr_country_id', String(details.country_id));
        }

        setTimeout(() => {
            if (details.state_id) {
                setNativeValue('addr_state_id', String(details.state_id));
            }

            if (details.city_id) {
                setNativeValue('addr_city_id', String(details.city_id));
            }

            setNativeValue('addr_postcode', details.postcode ?? '');
            setNativeValue('addr_line_1', details.line_1 ?? '');
            setNativeValue('addr_line_2', details.line_2 ?? '');
            setNativeValue('addr_line_3', details.line_3 ?? '');
            setCreateLatitude(details.latitude != null ? String(details.latitude) : '');
            setCreateLongitude(details.longitude != null ? String(details.longitude) : '');
        }, 0);
    };

    const handleEditLookupSelect = (details: {
        line_1: string;
        line_2: string | null;
        line_3: string | null;
        postcode: string | null;
        latitude: number | null;
        longitude: number | null;
        country_id: number | null;
        state_id: number | null;
        city_id: number | null;
        country: { id: number; name: string } | null;
        state: { id: number; name: string; country_id: number } | null;
        city: { id: number; name: string } | null;
    }) => {
        mergeGeoRecords(details);

        if (details.country_id) {
            setEditAddrCountryId(String(details.country_id));
        }

        if (details.state_id) {
            setEditAddrStateId(String(details.state_id));
        }

        if (details.city_id) {
            setEditAddrCityId(String(details.city_id));
        }

        setEditAddrPostcode(details.postcode ?? '');
        setEditAddrLine1(details.line_1 ?? '');
        setEditAddrLine2(details.line_2 ?? '');
        setEditAddrLine3(details.line_3 ?? '');
        setEditAddrLatitude(details.latitude != null ? String(details.latitude) : '');
        setEditAddrLongitude(details.longitude != null ? String(details.longitude) : '');
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

                                <div className="space-y-4 rounded-md border p-4">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-medium">Address</h4>
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                variant={createAddressMode === 'new' ? 'default' : 'outline'}
                                                size="sm"
                                                onClick={() => setCreateAddressMode('new')}
                                            >
                                                Create New
                                            </Button>
                                            <Button
                                                type="button"
                                                variant={createAddressMode === 'select' ? 'default' : 'outline'}
                                                size="sm"
                                                onClick={() => setCreateAddressMode('select')}
                                            >
                                                Select Existing
                                            </Button>
                                        </div>
                                    </div>

                                    <input type="hidden" name="address_mode" value={createAddressMode} />
                                    <InputError message={errors.address_mode} />

                                    {createAddressMode === 'select' ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="address_id">Existing Address</Label>
                                            <select
                                                id="address_id"
                                                name="address_id"
                                                required
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                            >
                                                <option value="">Select an address</option>
                                                {unattachedAddresses.map((addr) => (
                                                    <option key={addr.id} value={addr.id}>
                                                        {addr.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.address_id} />
                                            {unattachedAddresses.length === 0 && (
                                                <p className="text-xs text-muted-foreground">
                                                    No unattached addresses available.
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <>
                                            <AddressLookup onSelect={handleCreateLookupSelect} />
                                            <p className="text-xs text-muted-foreground">
                                                Search for an address to auto-fill the fields below
                                            </p>

                                            <div className="grid grid-cols-3 gap-4">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="addr_country_id">Country</Label>
                                                    <select
                                                        id="addr_country_id"
                                                        name="address[country_id]"
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
                                                    <InputError message={errors['address.country_id']} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="addr_state_id">State</Label>
                                                    <select
                                                        id="addr_state_id"
                                                        name="address[state_id]"
                                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                                    >
                                                        <option value="">Select a state (optional)</option>
                                                        {filteredStatesForCreate.map((state) => (
                                                            <option key={state.id} value={state.id}>
                                                                {state.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError message={errors['address.state_id']} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="addr_city_id">City</Label>
                                                    <select
                                                        id="addr_city_id"
                                                        name="address[city_id]"
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
                                                    <InputError message={errors['address.city_id']} />
                                                </div>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="addr_postcode">Postcode</Label>
                                                <Input
                                                    id="addr_postcode"
                                                    type="text"
                                                    name="address[postcode]"
                                                    placeholder="Postcode"
                                                    required
                                                />
                                                <InputError message={errors['address.postcode']} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="addr_line_1">Address Line 1</Label>
                                                <Input
                                                    id="addr_line_1"
                                                    type="text"
                                                    name="address[line_1]"
                                                    placeholder="Address line 1"
                                                    required
                                                />
                                                <InputError message={errors['address.line_1']} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="addr_line_2">Address Line 2</Label>
                                                <Input
                                                    id="addr_line_2"
                                                    type="text"
                                                    name="address[line_2]"
                                                    placeholder="Address line 2 (optional)"
                                                />
                                                <InputError message={errors['address.line_2']} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="addr_line_3">Address Line 3</Label>
                                                <Input
                                                    id="addr_line_3"
                                                    type="text"
                                                    name="address[line_3]"
                                                    placeholder="Address line 3 (optional)"
                                                />
                                                <InputError message={errors['address.line_3']} />
                                            </div>

                                            <input type="hidden" name="address[latitude]" value={createLatitude} />
                                            <input type="hidden" name="address[longitude]" value={createLongitude} />
                                        </>
                                    )}
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
                                            <th className="px-4 py-2 text-left font-medium">Address</th>
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
                                                <td className="max-w-32 truncate px-4 py-3">
                                                    {venue.address?.line_1 ?? '-'}
                                                </td>
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
                <DialogContent className="max-h-[90vh] overflow-y-auto">
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

                        <div className="space-y-4 rounded-md border p-4">
                            <h4 className="text-sm font-medium">Address</h4>

                            <AddressLookup onSelect={handleEditLookupSelect} />

                            <div className="grid grid-cols-3 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-addr-country_id">Country</Label>
                                    <Select value={editAddrCountryId} onValueChange={handleEditCountryChange}>
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
                                    <InputError message={editErrors['address.country_id']} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="edit-addr-state_id">State</Label>
                                    <Select value={editAddrStateId} onValueChange={setEditAddrStateId}>
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
                                    <InputError message={editErrors['address.state_id']} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="edit-addr-city_id">City</Label>
                                    <Select value={editAddrCityId} onValueChange={setEditAddrCityId}>
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
                                    <InputError message={editErrors['address.city_id']} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-addr-postcode">Postcode</Label>
                                <Input
                                    id="edit-addr-postcode"
                                    type="text"
                                    value={editAddrPostcode}
                                    onChange={(e) => setEditAddrPostcode(e.target.value)}
                                    placeholder="Postcode"
                                    required
                                />
                                <InputError message={editErrors['address.postcode']} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-addr-line_1">Address Line 1</Label>
                                <Input
                                    id="edit-addr-line_1"
                                    type="text"
                                    value={editAddrLine1}
                                    onChange={(e) => setEditAddrLine1(e.target.value)}
                                    required
                                />
                                <InputError message={editErrors['address.line_1']} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-addr-line_2">Address Line 2</Label>
                                <Input
                                    id="edit-addr-line_2"
                                    type="text"
                                    value={editAddrLine2}
                                    onChange={(e) => setEditAddrLine2(e.target.value)}
                                    placeholder="Optional"
                                />
                                <InputError message={editErrors['address.line_2']} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-addr-line_3">Address Line 3</Label>
                                <Input
                                    id="edit-addr-line_3"
                                    type="text"
                                    value={editAddrLine3}
                                    onChange={(e) => setEditAddrLine3(e.target.value)}
                                    placeholder="Optional"
                                />
                                <InputError message={editErrors['address.line_3']} />
                            </div>

                            {(editAddrLatitude || editAddrLongitude) && (
                                <>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label>Latitude</Label>
                                            <p className="text-sm text-muted-foreground">{editAddrLatitude || '—'}</p>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Longitude</Label>
                                            <p className="text-sm text-muted-foreground">{editAddrLongitude || '—'}</p>
                                        </div>
                                    </div>

                                    {googleMapsApiKey && editAddrLatitude && editAddrLongitude && (
                                        <iframe
                                            className="h-[200px] w-full rounded-md border-0"
                                            loading="lazy"
                                            referrerPolicy="no-referrer-when-downgrade"
                                            src={`https://www.google.com/maps/embed/v1/place?key=${googleMapsApiKey}&q=${editAddrLatitude},${editAddrLongitude}&zoom=15`}
                                        />
                                    )}
                                </>
                            )}
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
