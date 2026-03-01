import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { triggerRegenerationStarted } from '@/hooks/use-regeneration-status';
import { getApiHeaders } from '@/lib/utils';
import { generate } from '@/actions/App/Http/Controllers/Api/GenerationController';
import { router } from '@inertiajs/react';
import { AlertTriangle, Info, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface PromptData {
    task_prompt_slug: string;
    system_prompt_content?: string | null;
    task_prompt_content: string;
    supplementary_content?: string | null;
}

interface GenerateCardProps {
    projectId: number;
    dayId: number;
    dayNumber: number;
    type: 'travel' | 'activity';
    modelId: number;
    promptData: PromptData;
}

export function GenerateCard({ projectId, dayId, dayNumber, type, modelId, promptData }: GenerateCardProps) {
    const [taskContent, setTaskContent] = useState(promptData.task_prompt_content);
    const [supplementaryContent, setSupplementaryContent] = useState(promptData.supplementary_content ?? '');
    const [isLoading, setIsLoading] = useState(false);

    const taskContentChanged = taskContent !== promptData.task_prompt_content;

    const handleGenerate = async () => {
        setIsLoading(true);

        try {
            const url = generate.url([projectId, dayId]);

            const body: Record<string, unknown> = {
                type,
                model_id: modelId,
                task_prompt_slug: promptData.task_prompt_slug,
            };

            if (taskContentChanged) {
                body.task_prompt_content = taskContent;
            }

            if (supplementaryContent.trim() !== '' || promptData.supplementary_content) {
                body.supplementary_content = supplementaryContent;
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: getApiHeaders(),
                body: JSON.stringify(body),
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to start generation');
            }

            const data = await response.json();

            toast.success('Generation started', {
                description: `Processing ${data.total_jobs} job${data.total_jobs > 1 ? 's' : ''}`,
            });

            triggerRegenerationStarted();
            router.reload();
        } catch {
            toast.error('Failed to start generation');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Generate</CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {promptData.system_prompt_content && (
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            System Prompt (read-only)
                        </p>
                        <pre className="whitespace-pre-wrap rounded-md bg-muted p-3 text-xs font-mono opacity-60">
                            {promptData.system_prompt_content}
                        </pre>
                    </div>
                )}

                <div className="space-y-2">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                        Standard Prompt
                    </p>

                    <Textarea
                        value={taskContent}
                        onChange={(e) => setTaskContent(e.target.value)}
                        rows={10}
                        className="font-mono text-xs"
                    />

                    {taskContentChanged && (
                        <Alert>
                            <AlertTriangle className="size-4" />
                            <AlertTitle>Warning</AlertTitle>
                            <AlertDescription>
                                Changes to this prompt will affect all future generations of this prompt type across all
                                days.
                            </AlertDescription>
                        </Alert>
                    )}
                </div>

                <div className="space-y-2">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                        Supplementary Prompt
                    </p>

                    <Textarea
                        value={supplementaryContent}
                        onChange={(e) => setSupplementaryContent(e.target.value)}
                        rows={4}
                        placeholder="Add day-specific instructions here..."
                        className="font-mono text-xs"
                    />

                    <Alert>
                        <Info className="size-4" />
                        <AlertTitle>Day-specific</AlertTitle>
                        <AlertDescription>
                            This prompt only affects Day {dayNumber}.
                        </AlertDescription>
                    </Alert>
                </div>

                <Button onClick={handleGenerate} disabled={isLoading} className="w-full">
                    <RefreshCw className={`mr-2 size-4 ${isLoading ? 'animate-spin' : ''}`} />
                    {isLoading ? 'Generating...' : 'Generate'}
                </Button>
            </CardContent>
        </Card>
    );
}
