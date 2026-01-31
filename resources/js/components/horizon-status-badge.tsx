interface HorizonStatusBadgeProps {
    isRunning: boolean;
    isLoading?: boolean;
}

export function HorizonStatusBadge({ isRunning, isLoading }: HorizonStatusBadgeProps) {
    if (isLoading) {
        return (
            <span className="ml-auto inline-flex items-center rounded-full bg-gray-500/20 px-2 py-0.5 text-xs font-medium text-gray-500">
                ...
            </span>
        );
    }

    if (isRunning) {
        return (
            <span className="ml-auto inline-flex items-center rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-medium text-green-600 dark:text-green-400">
                Active
            </span>
        );
    }

    return (
        <span className="ml-auto inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-600 dark:text-red-400">
            Inactive
        </span>
    );
}
