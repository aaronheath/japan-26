import { Input } from '@/components/ui/input';
import { Loader2, Search } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface Suggestion {
    place_id: string;
    description: string;
}

interface AddressDetails {
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
}

interface AddressLookupProps {
    onSelect: (details: AddressDetails) => void;
}

export default function AddressLookup({ onSelect }: AddressLookupProps) {
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isLoadingDetails, setIsLoadingDetails] = useState(false);
    const [showDropdown, setShowDropdown] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(null);

    const fetchSuggestions = useCallback(async (searchQuery: string) => {
        if (searchQuery.length < 3) {
            setSuggestions([]);
            setShowDropdown(false);
            return;
        }

        setIsLoading(true);

        try {
            const response = await fetch(`/api/address-lookup/autocomplete?query=${encodeURIComponent(searchQuery)}`);
            const data = await response.json();

            setSuggestions(data);
            setShowDropdown(data.length > 0);
        } catch {
            setSuggestions([]);
            setShowDropdown(false);
        } finally {
            setIsLoading(false);
        }
    }, []);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setQuery(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => fetchSuggestions(value), 300);
    };

    const handleSelect = async (suggestion: Suggestion) => {
        setShowDropdown(false);
        setQuery(suggestion.description);
        setIsLoadingDetails(true);

        try {
            const response = await fetch(`/api/address-lookup/place/${encodeURIComponent(suggestion.place_id)}`);
            const details: AddressDetails = await response.json();

            onSelect(details);
            setQuery('');
        } catch {
            // Silently fail - user can still manually fill the form
        } finally {
            setIsLoadingDetails(false);
        }
    };

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setShowDropdown(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);

        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    return (
        <div ref={containerRef} className="relative">
            <div className="relative">
                <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input
                    type="text"
                    value={query}
                    onChange={handleInputChange}
                    placeholder="Search for an address..."
                    className="pl-9 pr-9"
                    disabled={isLoadingDetails}
                />
                {(isLoading || isLoadingDetails) && (
                    <Loader2 className="text-muted-foreground absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin" />
                )}
            </div>

            {showDropdown && suggestions.length > 0 && (
                <ul className="border-border bg-popover text-popover-foreground absolute z-50 mt-1 w-full overflow-hidden rounded-md border shadow-md">
                    {suggestions.map((suggestion) => (
                        <li key={suggestion.place_id}>
                            <button
                                type="button"
                                className="hover:bg-accent w-full px-3 py-2 text-left text-sm"
                                onClick={() => handleSelect(suggestion)}
                            >
                                {suggestion.description}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
