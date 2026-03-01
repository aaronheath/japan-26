import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { formatLocalDateTime } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';

interface LlmCall {
    id: number;
    llm_provider_name: string;
    created_at: string;
}

interface PromptData {
    system_prompt_content?: string | null;
    task_prompt_content: string;
    supplementary_content?: string | null;
}

interface PromptDetailsCardProps {
    promptData: PromptData;
    llmCall?: LlmCall | null;
}

function PromptSection({ label, content }: { label: string; content: string }) {
    return (
        <div className="space-y-1">
            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">{label}</p>
            <pre className="whitespace-pre-wrap rounded-md bg-muted p-3 text-xs font-mono">{content}</pre>
        </div>
    );
}

export function PromptDetailsCard({ promptData, llmCall }: PromptDetailsCardProps) {
    const [systemOpen, setSystemOpen] = useState(false);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Prompt Details</CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {promptData.system_prompt_content && (
                    <Collapsible open={systemOpen} onOpenChange={setSystemOpen}>
                        <CollapsibleTrigger className="flex w-full items-center gap-2 text-xs font-medium text-muted-foreground uppercase tracking-wide hover:text-foreground">
                            <ChevronDown
                                className={`size-3 transition-transform ${systemOpen ? 'rotate-0' : '-rotate-90'}`}
                            />
                            System Prompt
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <pre className="mt-1 whitespace-pre-wrap rounded-md bg-muted p-3 text-xs font-mono">
                                {promptData.system_prompt_content}
                            </pre>
                        </CollapsibleContent>
                    </Collapsible>
                )}

                <PromptSection label="Standard Prompt" content={promptData.task_prompt_content} />

                {promptData.supplementary_content && (
                    <PromptSection label="Supplementary Prompt" content={promptData.supplementary_content} />
                )}

                {llmCall && (
                    <p className="text-xs text-muted-foreground">
                        Generated using <span className="font-bold">{llmCall.llm_provider_name}</span> on{' '}
                        <span className="font-bold">{formatLocalDateTime(llmCall.created_at)}</span> (
                        <span className="font-bold">ID: {llmCall.id}</span>)
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
