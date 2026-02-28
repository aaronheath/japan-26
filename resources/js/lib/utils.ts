import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(url1: NonNullable<InertiaLinkProps['href']>, url2: NonNullable<InertiaLinkProps['href']>) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function getCsrfToken(): string {
    const cookieValue = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return cookieValue ? decodeURIComponent(cookieValue) : '';
}

export function getApiHeaders(): HeadersInit {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrfToken(),
    };
}

export function formatDate(dateString: string): string {
    const [year, month, day] = dateString.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day));

    const parts = new Intl.DateTimeFormat('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: '2-digit',
        timeZone: 'UTC',
    }).formatToParts(date);

    const weekday = parts.find((p) => p.type === 'weekday')?.value;
    const dayNum = parts.find((p) => p.type === 'day')?.value;
    const monthStr = parts.find((p) => p.type === 'month')?.value;
    const yearStr = parts.find((p) => p.type === 'year')?.value;

    return `${weekday} ${dayNum} ${monthStr} '${yearStr}`;
}

export function formatLocalDateTime(utcTimestamp: string): string {
    const date = new Date(utcTimestamp);

    if (isNaN(date.getTime())) {
        return 'Unknown date';
    }

    const dateFormatter = new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: '2-digit',
    });

    const timeFormatter = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    return `${dateFormatter.format(date)} at ${timeFormatter.format(date)}`;
}
