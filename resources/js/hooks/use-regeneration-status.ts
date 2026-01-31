import { useCallback, useEffect, useRef, useState } from 'react';

interface ActiveBatch {
    id: number;
    scope: string;
    generator_type: string | null;
    progress: number;
    total_jobs: number;
    completed_jobs: number;
    failed_jobs: number;
    status: string;
}

interface RecentlyCompletedBatch {
    id: number;
    scope: string;
    generator_type: string | null;
    status: string;
    completed_at: string | null;
}

interface RegenerationStatus {
    is_regenerating: boolean;
    horizon_running: boolean;
    active_batches: ActiveBatch[];
    recently_completed: RecentlyCompletedBatch[];
}

export const REGENERATION_STARTED_EVENT = 'regeneration-started';

export function triggerRegenerationStarted() {
    window.dispatchEvent(new CustomEvent(REGENERATION_STARTED_EVENT));
}

export function useRegenerationStatus(projectId: number) {
    const [isRegenerating, setIsRegenerating] = useState(false);
    const [isHorizonRunning, setIsHorizonRunning] = useState(false);
    const [activeBatches, setActiveBatches] = useState<ActiveBatch[]>([]);
    const [recentlyCompleted, setRecentlyCompleted] = useState<RecentlyCompletedBatch[]>([]);

    const isRegeneratingRef = useRef(false);

    const fetchStatus = useCallback(async () => {
        try {
            const response = await fetch(`/api/regeneration/project/${projectId}/status`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data: RegenerationStatus = await response.json();

            setIsRegenerating(data.is_regenerating);
            setIsHorizonRunning(data.horizon_running);
            setActiveBatches(data.active_batches);
            setRecentlyCompleted(data.recently_completed);

            isRegeneratingRef.current = data.is_regenerating;
        } catch {
            // Silently fail on network errors
        }
    }, [projectId]);

    useEffect(() => {
        let timeoutId: ReturnType<typeof setTimeout>;

        const poll = () => {
            fetchStatus();
            const nextInterval = isRegeneratingRef.current ? 2000 : 10000;
            timeoutId = setTimeout(poll, nextInterval);
        };

        poll();

        return () => clearTimeout(timeoutId);
    }, [projectId, fetchStatus]);

    useEffect(() => {
        const handleRegenerationStarted = () => {
            fetchStatus();
        };

        window.addEventListener(REGENERATION_STARTED_EVENT, handleRegenerationStarted);

        return () => {
            window.removeEventListener(REGENERATION_STARTED_EVENT, handleRegenerationStarted);
        };
    }, [fetchStatus]);

    return {
        isRegenerating,
        isHorizonRunning,
        activeBatches,
        recentlyCompleted,
        refetch: fetchStatus,
    };
}
