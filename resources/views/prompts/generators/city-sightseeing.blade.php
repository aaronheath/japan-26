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

{{--For each list item please format as individual tables with rows as follows:--}}
{{--- Location: Location of attraction (address, neighborhood, etc)--}}
{{--- Description: Brief description (2-3 sentences)--}}
{{--- Optimal Time: Best time to visit--}}
{{--- Fees: Entrance fees (if applicable)--}}
{{--- Considerations: Special considerations or tips (if applicable)--}}
{{--- Rating: An out of 5 ranking of how popular the attraction is with tourists (1 being least popular, 5 being most popular)--}}

{{--The above list must be presented as one row per item detail and not as columns.--}}

{{--The name of each attraction item should be a heading above its respective table.--}}

{{--The table heading row should be omitted.--}}

Group the list by the type of attraction, such as Historical Sites, Museums, Parks, Cultural Experiences, etc. Use appropriate headings for each group.
