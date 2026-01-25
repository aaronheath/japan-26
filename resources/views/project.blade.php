<x-layout :project="$project">
    <h1 class="text-xl font-bold">Project Overview</h1>

    <table class="w-full border-collapse">
        <thead>
            <tr class="border-b">
                <th class="py-2 px-3 text-left">Day</th>
                <th class="py-2 px-3 text-left">Travel</th>
                @foreach($activityTypes as $type)
                    <th class="py-2 px-3 text-left capitalize">{{ $type->value }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($days as $day)
                <tr class="border-b">
                    <td class="py-2 px-3">
                        <a
                            href="{{ route('project.day.show', ['project' => $project, 'day' => $day['number']]) }}"
                            class="text-blue-600 underline">
                            Day {{ $day['number'] }}
                        </a>
                    </td>
                    <td class="py-2 px-3">
                        @if($day['travel'])
                            @if($day['travel']['hasLlmCall'])
                                <a href="{{ $day['travel']['url'] }}" class="text-blue-600 underline">View</a>
                            @else
                                <span class="text-gray-400">Pending</span>
                            @endif
                        @else
                            <span class="text-gray-300">&mdash;</span>
                        @endif
                    </td>
                    @foreach($activityTypes as $type)
                        <td class="py-2 px-3">
                            @if(isset($day['activities'][$type->value]))
                                @if($day['activities'][$type->value]['hasLlmCall'])
                                    <a href="{{ $day['activities'][$type->value]['url'] }}" class="text-blue-600 underline">View</a>
                                @else
                                    <span class="text-gray-400">Pending</span>
                                @endif
                            @else
                                <span class="text-gray-300">&mdash;</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</x-layout>
