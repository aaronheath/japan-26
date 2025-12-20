<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite('resources/css/app.css')
</head>
<body>
<div class="max-w-5xl px-12 m-auto space-y-4">
    <div class="text-3xl font-bold">
        Travel Itinerary Assistant
    </div>

    <div class="text-lg">
        Project: {{ $project->name }}
    </div>

    <div class="flex gap-x-4 gap-y-1 flex-wrap">
        <div>Day:</div>

        @foreach($project->latestVersion()->days as $day)
            <div>
                <a href="{{ route('project.day.show', ['project' => $project, 'day' => $day->number]) }}" class="text-blue-600 underline">
                    Day {{ $day->number }}
                </a>
            </div>
        @endforeach
    </div>

    <div class="space-y-4 mb-24">
        {{ $slot }}
    </div>
</div>
</body>
</html>
