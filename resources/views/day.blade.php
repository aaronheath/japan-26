<x-layout :project="$project">
    <h1 class="text-xl font-bold">Day {{ $day->number }}</h1>

    <div class="flex gap-x-4 gap-y-1">
        <a
            href="{{ route('project.day.show', ['project' => $project, 'day' => $day->number, 'tab' => 'overview']) }}"
            class="text-blue-600 underline">
            Overview
        </a>

        @if($day->travel)
            <a
                href="{{ route('project.day.show', ['project' => $project, 'day' => $day->number, 'tab' => 'travel']) }}"
                class="text-blue-600 underline">
                Travel
            </a>
        @endif

        @foreach($activities as $i => $activity)
            <a
                href="{{ route('project.day.show', ['project' => $project, 'day' => $day->number, 'tab' => 'activity-' . $i]) }}"
                class="text-blue-600 underline capitalize">
                {{ $activity['type']->value }}
            </a>
        @endforeach
    </div>

    <div class="space-y-2">
        @if($tab === 'overview')
            Overview tab
        @endif

        @if($tab === 'travel')
            <h2 class="text-lg font-bold">Travel</h2>

            <div>
                <p>{{ $travel['start_city']->name }}, {{ $travel['start_city']->state->name }}  to {{ $travel['end_city']->name }}, {{ $travel['end_city']->state->name }}</p>
            </div>

            @if($travel['llm_call']['response'] ?? false)
                <div class="prose max-w-none">
                    {!! str($travel['llm_call']['response'])->markdown() !!}
                </div>
            @endif
        @endif

        @if(str($tab)->startsWith('activity-'))
            @php($activity = $activities[(int)str($tab)->after('activity-')->toString()])

            <h2 class="text-lg font-bold capitalize">{{ $activity['type']->value }} in {{ $activity['city']['name'] }}</h2>

{{--            <div>--}}
{{--                <p>{{ $travel['start_city']['name'] }} to {{ $travel['end_city']['name'] }}</p>--}}
{{--            </div>--}}

            @if($activity['llm_call']['response'] ?? false)
                <div class="prose max-w-none">
                    {!! str($activity['llm_call']['response'])->markdown() !!}
                </div>
            @endif
        @endif
    </div>
</x-layout>
