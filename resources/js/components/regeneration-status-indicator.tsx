import { useRegenerationStatus } from '@/hooks/use-regeneration-status';
import { Loader2, RefreshCw } from 'lucide-react';

interface RegenerationStatusIndicatorProps {
    projectId: number;
}

export function RegenerationStatusIndicator({ projectId }: RegenerationStatusIndicatorProps) {
    const { isRegenerating, activeBatches, recentlyCompleted } = useRegenerationStatus(projectId);

    if (!isRegenerating && recentlyCompleted.length === 0) {
        return null;
    }

    if (isRegenerating && activeBatches.length > 0) {
        const totalProgress = Math.round(
            activeBatches.reduce((sum, batch) => sum + batch.progress, 0) / activeBatches.length,
        );

        return (
            <div className="bg-muted/50 mx-2 mb-2 flex items-center gap-3 rounded-md px-3 py-2">
                <Loader2 className="text-primary size-4 animate-spin" />
                <div className="min-w-0 flex-1">
                    <div className="text-sm font-medium">Regenerating...</div>
                    <div className="text-muted-foreground text-xs">{totalProgress}% complete</div>
                </div>
            </div>
        );
    }

    if (recentlyCompleted.length > 0) {
        const latest = recentlyCompleted[0];
        const isSuccess = latest.status === 'completed';

        return (
            <div
                className={`mx-2 mb-2 flex items-center gap-3 rounded-md px-3 py-2 ${isSuccess ? 'bg-green-500/10' : 'bg-red-500/10'}`}
            >
                <RefreshCw className={`size-4 ${isSuccess ? 'text-green-500' : 'text-red-500'}`} />
                <div className="min-w-0 flex-1">
                    <div className="text-sm font-medium">{isSuccess ? 'Regeneration complete' : 'Regeneration failed'}</div>
                    <div className="text-muted-foreground text-xs capitalize">{latest.scope} regeneration</div>
                </div>
            </div>
        );
    }

    return null;
}
