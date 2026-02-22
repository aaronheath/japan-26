<?php

namespace Database\Seeders;

use App\Enums\PromptType;
use App\Models\Prompt;
use App\Models\PromptVersion;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    public function run(): void
    {
        $prompts = $this->promptDefinitions();

        foreach ($prompts as $definition) {
            if (Prompt::where('slug', $definition['slug'])->exists()) {
                continue;
            }

            $prompt = Prompt::create([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'description' => $definition['description'],
                'type' => $definition['type'],
            ]);

            $version = PromptVersion::create([
                'prompt_id' => $prompt->id,
                'version' => 1,
                'content' => $definition['content'],
            ]);

            $prompt->update(['active_version_id' => $version->id]);
        }

        $this->linkSystemPrompts();
    }

    protected function linkSystemPrompts(): void
    {
        $systemPrompt = Prompt::where('slug', 'travel-agent-system')->first();

        if (! $systemPrompt) {
            return;
        }

        Prompt::where('type', PromptType::Task)
            ->whereNull('system_prompt_id')
            ->update(['system_prompt_id' => $systemPrompt->id]);
    }

    /**
     * @return array<int, array{name: string, slug: string, description: string, type: PromptType, content: string}>
     */
    protected function promptDefinitions(): array
    {
        return [
            [
                'name' => 'Travel Agent System Prompt',
                'slug' => 'travel-agent-system',
                'description' => 'System prompt for the travel agent persona used across all travel-related generators.',
                'type' => PromptType::System,
                'content' => $this->travelAgentSystemContent(),
            ],
            [
                'name' => 'City Sightseeing',
                'slug' => 'city-sightseeing',
                'description' => 'Task prompt for generating city sightseeing attraction recommendations. Variables: $city (City model), $date (optional date string).',
                'type' => PromptType::Task,
                'content' => $this->citySightseeingContent(),
            ],
            [
                'name' => 'Travel Domestic Japan',
                'slug' => 'travel-domestic-japan',
                'description' => 'Task prompt for generating domestic Japan travel recommendations. Variables: $startCity (City model), $endCity (City model), $overnight (bool), $date (date string).',
                'type' => PromptType::Task,
                'content' => $this->travelDomesticJapanContent(),
            ],
            [
                'name' => 'Travel International',
                'slug' => 'travel-international',
                'description' => 'Task prompt for generating international travel recommendations. Variables: $startCity (City model), $endCity (City model), $overnight (bool), $date (date string).',
                'type' => PromptType::Task,
                'content' => $this->travelInternationalContent(),
            ],
        ];
    }

    protected function travelAgentSystemContent(): string
    {
        return <<<'BLADE'
You're an expert travel agent specializing in international holidays. You're communicating with another travel agent who is working with the client to help craft the perfect holiday.
Your task is to create a detailed travel itinerary based upon the clients preferences and interests. You take the information provided and craft a personalized travel
plan with recommendations for destinations, accommodations, activities, and dining options.

Your responses should be dry, factual, informative, and tailored to the client's desires. There is no need to be overly friendly as your response will be seen only by your colleague and not the client themself.

Make sure your itinerary is easy to follow.

Your output should be in markdown format with clear headings. Where appropriate, especially for comparisons, use
tables to present information clearly. Minimise the use of dot point lists except where absolutely necessary for clarity.
Your headings should start at h3 level and below. Make sure to double check your markdown to ensure it's correctly formatted.



There is no need to document the clients preferences or interests in your response as your colleague is already aware of them.
BLADE;
    }

    protected function citySightseeingContent(): string
    {
        return <<<'BLADE'
You are to provide a list of the top sightseeing attractions in {{ $city->name }}, {{ $city->state->name }}, {{ $city->state->country->name }}.

@if($date)
    The sightseeing is expected to take place around {{ $date }}.
@endif

The sightseeing attractions should include a mix of highly popular, fun, cultural, shopping and historical sites that are popular with tourists. Be sure to also include super popular tourist spots as well as some lesser-known gems.

Please provide a brief description of each attraction, including its significance and what visitors can expect to see or do there.

Provide at least 15 attractions (up to 30 attractions), and ensure that they are suitable for a variety of interests and age groups. You are to only provide a list of attractions and not build single or multiple day itineraries.

Those travelling have an interest in World War 2 history, motorsport, NJPW, model car kits and local drinking and food establishments, so please ensure that some attractions cater to these interests.

Provide information about the best times to visit each attraction, any entrance fees, and any special considerations or tips for visitors.

Where the city is part of a larger metropolitan area like Tokyo or Osaka, please consider the wider metropolitan area instead of the just the special ward.

For each item it shall be presented in the following format.

A heading with the name of the attraction.

Followed by a table with two columns. The first column is titled "Detail" and the second column is titled "Information".

Each row sequentially shall contain the following information:
- Name: Location, Information: Location of attraction (address, neighborhood, etc)
- Name: Coordinates, Information: Latitude and Longitude. Include a link to Google Maps with the coordinates pre-filled. These links should open in a new tab.
- Name: Description, Information: Brief description (2-3 sentences)
- Name: Optimal Time, Information: Best time to visit
- Name: Fees, Information: Entrance fees (if applicable)
- Name: Considerations, Information: Special considerations or tips (if applicable)
- Name: Rating, Information: An out of 5 ranking of how popular the attraction is with tourists (1 being least popular, 5 being most popular)
- Name: Getting There, Information: Best transportation options to reach the attraction. Consider near by public transportation links. Remember that trains are preferable.

Group the list by the type of attraction, such as Historical Sites, Museums, Parks, Cultural Experiences, etc. Use appropriate headings for each group.
BLADE;
    }

    protected function travelDomesticJapanContent(): string
    {
        return <<<'BLADE'
You are to provide recommended travel options for domestic travel between
{{ $startCity->name }}, {{ $startCity->state->name }}, {{ $startCity->state->country->name }} and
{{ $endCity->name }}, {{ $endCity->state->name }}, {{ $endCity->state->country->name }}.

The journey is to start on {{ $date }} and should take place {{ $overnight ? 'overnight' : 'during the day' }}.

You are to recommend the best travel options available, considering factors such as cost, duration, and convenience.

Please provide options including train, bus and domestic flights. High speed rail options are preferred. For distances
that make sense, flying is acceptable. Only consider bus travel for routes where there are no train or flight options.

You are to provide at least 3 travel options making sure to detail all airports, terminals, layovers, durations, and costs.

Provide estimated costs for all classes of travel (e.g. Economy, Premium Economy, Business, First Class) where available. Provide costings in Australian Dollars.

Provide information how tickets are purchased, whether advance booking is required, and any other relevant details.
BLADE;
    }

    protected function travelInternationalContent(): string
    {
        return <<<'BLADE'
You are to provide recommended travel options for international travel between
{{ $startCity->name }}, {{ $startCity->state->name }}, {{ $startCity->state->country->name }} and
{{ $endCity->name }}, {{ $endCity->state->name }}, {{ $endCity->state->country->name }}.

The journey is to start on {{ $date }} and should take place {{ $overnight ? 'overnight' : 'during the day' }}.

You are to recommend the best travel options available, considering factors such as cost, duration, and convenience.

Direct flight or minimal layovers are preferred however where necessary more premium airlines are preferred.

The person taking the travel is a Qantas Club member meaning that they are eligible for certain benefits and upgrades.
Because of this you are to prioritize airlines and routes that offer Qantas Club benefits. Where Qantas is not possible,
airlines or airports that offer single flight lounge access should be prirotied.

You are to provide at least 3 travel options making sure to detail all airports, terminals, layovers, durations, and costs.

Where a destination has multiple airports, you should prioritize the most convenient airport for the traveler. The
traveller priorities easy transfers. No special consideration has to be made for excess baggage or special assistance.

Provide estimated costs for all classes of travel (e.g. Economy, Premium Economy, Business, First Class) where available. Provide costings in Australian Dollars.
BLADE;
    }
}
